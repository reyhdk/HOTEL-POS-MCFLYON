<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\Room;
use App\Models\Guest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Midtrans\Snap;
use Midtrans\Config;
use Midtrans\Transaction;

class CheckInController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

    /**
     * Hitung Biaya Early Check-In (Timezone Asia/Jakarta)
     */
    private function calculateEarlyCheckInFee()
    {
        $setting = Setting::first();

        // Cek apakah fitur aktif dan ada nominal biayanya
        if (!$setting || !$setting->early_check_in_fee || $setting->early_check_in_fee == 0) {
            return 0;
        }

        $now = Carbon::now('Asia/Jakarta');
        $checkInTimeSetting = $setting->check_in_time ?? '14:00';
        $feePerHour = $setting->early_check_in_fee;

        // Buat waktu standar check-in hari ini
        $standardCheckIn = Carbon::createFromFormat('H:i', substr($checkInTimeSetting, 0, 5), 'Asia/Jakarta')
            ->setDate($now->year, $now->month, $now->day);

        // Jika sekarang sudah lewat jam check-in, gratis
        if ($now->gte($standardCheckIn)) {
            return 0;
        }

        // Hitung selisih jam
        $hoursEarly = $now->diffInHours($standardCheckIn);
        $minutesResidue = $now->diffInMinutes($standardCheckIn) % 60;

        // Bulatkan ke atas (contoh: 1 jam 5 menit = bayar 2 jam)
        if ($minutesResidue > 0) {
            $hoursEarly++;
        }

        return $hoursEarly * $feePerHour;
    }

    /**
     * [BARU] Proses Logic Early Check-in Tanpa Membuat Order
     * Mengupdate tabel Booking secara langsung & Generate Midtrans Token
     */
    private function processEarlyCheckInFee(Booking $booking, string $paymentMethod)
    {
        $fee = $this->calculateEarlyCheckInFee();
        $snapToken = null;

        if ($fee > 0) {
            // Tentukan status bayar early check-in
            // Cash -> Paid, QRIS -> Unpaid (tunggu bayar)
            $paymentStatus = ($paymentMethod === 'cash') ? 'paid' : 'unpaid';

            // 1. Update Tabel Booking (Menyimpan tagihan)
            $booking->update([
                'early_check_in_fee' => $fee,
                'early_payment_status' => $paymentStatus
            ]);

            Log::info("Early Check-In Fee Recorded for Booking #{$booking->id}: Rp $fee ($paymentStatus)");

            // 2. Generate Midtrans Token jika bukan Cash
            if ($paymentStatus === 'unpaid') {
                try {
                    // ID Transaksi Unik: EARLY-{booking_id}-{timestamp}
                    $midtransOrderId = 'EARLY-' . $booking->id . '-' . time();

                    $params = [
                        'transaction_details' => [
                            'order_id' => $midtransOrderId,
                            'gross_amount' => (int) $fee,
                        ],
                        'customer_details' => [
                            'first_name' => $booking->guest->name ?? 'Guest',
                            'email' => $booking->guest->email ?? 'guest@example.com',
                            'phone' => $booking->guest->phone_number ?? '',
                        ],
                        'item_details' => [
                            [
                                'id' => 'EARLY-FEE',
                                'price' => (int) $fee,
                                'quantity' => 1,
                                'name' => 'Biaya Early Check-In'
                            ]
                        ]
                    ];

                    $snapToken = Snap::getSnapToken($params);
                } catch (\Exception $e) {
                    Log::error("Midtrans Snap Error: " . $e->getMessage());
                }
            }
        }

        return [
            'fee' => $fee,
            'snap_token' => $snapToken
        ];
    }

    /**
     * Check-in dari Booking yang sudah ada (Early Check-in Mode)
     */
    public function storeFromBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'is_incognito' => 'nullable|boolean',
            'ktp_image'  => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'payment_method' => 'nullable|string'
        ]);

        $booking = Booking::with(['room', 'guest'])->findOrFail($request->booking_id);

        // Ambil payment method, default cash jika null
        $paymentMethod = $request->payment_method ?? 'cash';

        if ($booking->status === 'checked_in') {
            return response()->json(['message' => 'Booking ini sudah check-in sebelumnya.'], 409);
        }

        if ($booking->room->status === 'occupied') {
            return response()->json(['message' => 'Kamar masih terisi.'], 409);
        }

        try {
            // Variable untuk menampung hasil proses fee
            $feeResult = ['fee' => 0, 'snap_token' => null];

            DB::transaction(function () use ($booking, $request, $paymentMethod, &$feeResult) {

                // Upload KTP
                if ($request->hasFile('ktp_image')) {
                    if ($booking->guest->ktp_image) {
                        Storage::disk('public')->delete($booking->guest->ktp_image);
                    }
                    $path = $request->file('ktp_image')->store('ktp_images', 'public');
                    $booking->guest->update(['ktp_image' => $path]);
                }

                // [LOGIC BARU] Proses Fee Early Check-in (Update Booking)
                $feeResult = $this->processEarlyCheckInFee($booking, $paymentMethod);

                // Update Status Booking Utama
                $booking->update([
                    'status' => 'checked_in',
                    'check_in_time' => now()
                ]);

                // Update Kamar
                $booking->room->update(['status' => 'occupied']);

                // Buat History CheckIn
                CheckIn::create([
                    'booking_id' => $booking->id,
                    'room_id' => $booking->room_id,
                    'guest_id' => $booking->guest_id,
                    'check_in_time' => now(),
                    'is_active' => true,
                    'is_incognito' => $request->boolean('is_incognito', false),
                ]);
            });

            // Siapkan Response
            $responseData = [
                'message' => 'Check-in berhasil!',
                'status' => 'success',
                'early_check_in_fee' => $feeResult['fee']
            ];

            // Jika ada token QRIS
            if ($feeResult['snap_token']) {
                $responseData['snap_token'] = $feeResult['snap_token'];
                $responseData['message'] = 'Check-in berhasil. Silakan selesaikan pembayaran Early Check-in.';
            }

            return response()->json($responseData);
        } catch (\Throwable $e) {
            Log::error("Check-in Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses check-in: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Direct Check-in (Walk-in atau Select Booking via Walk-in Menu)
     */
    public function storeDirect(Request $request)
    {
        // Logic existing: jika ada booking_id, ambil data tamu dari booking tsb
        if ($request->filled('booking_id')) {
            $booking = Booking::with('guest')->find($request->booking_id);
            if ($booking) {
                $request->merge([
                    'guest_id' => $booking->guest_id,
                    'check_out_date' => $request->check_out_date ?? $booking->check_out_date,
                    'duration' => null
                ]);
            }
        }

        $paymentMethod = $request->payment_method ?? 'cash';

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'room_id'        => 'required|exists:rooms,id',
            'guest_id'       => 'required_without:guest_name|nullable|exists:guests,id',
            'guest_name'     => 'required_without:guest_id|nullable|string',
            'guest_phone'    => 'nullable|string',
            'check_out_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'booking_id'     => 'nullable|exists:bookings,id',
            'notes'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $feeResult = ['fee' => 0, 'snap_token' => null];
            $checkIn = null;

            DB::transaction(function () use ($request, $paymentMethod, &$feeResult, &$checkIn) {
                $room = Room::findOrFail($request->room_id);
                if ($room->status === 'occupied') {
                    throw new \Exception("Kamar ini sedang terisi.");
                }

                // Handle Tamu Baru vs Lama
                $guestId = $request->guest_id;
                if (!$guestId && $request->guest_name) {
                    $newGuest = Guest::create([
                        'name'  => $request->guest_name,
                        'phone_number' => $request->guest_phone,
                        'email' => $request->guest_email,
                    ]);
                    $guestId = $newGuest->id;
                }

                // [LOGIC BARU] Proses Fee HANYA jika ini dari Booking yang sudah ada
                if ($request->booking_id) {
                    $booking = Booking::with('guest')->find($request->booking_id);
                    if ($booking) {
                        $feeResult = $this->processEarlyCheckInFee($booking, $paymentMethod);

                        // Update status booking
                        $booking->update([
                            'status' => 'checked_in',
                            'checked_in_at' => now()
                        ]);
                    }
                }

                // Buat data CheckIn
                $checkIn = CheckIn::create([
                    'room_id'        => $room->id,
                    'guest_id'       => $guestId,
                    'booking_id'     => $request->booking_id,
                    'check_in_time'  => now(),
                    'check_out_time' => null,
                    'is_active'      => true,
                    'notes'          => $request->notes,
                    'is_incognito'   => $request->is_incognito ?? false,
                ]);

                $room->update(['status' => 'occupied']);
            });

            // Siapkan Response
            $responseData = [
                'message' => 'Check-in berhasil!',
                'data' => $checkIn,
                'early_check_in_fee' => $feeResult['fee']
            ];

            if ($feeResult['snap_token']) {
                $responseData['snap_token'] = $feeResult['snap_token'];
                $responseData['message'] = 'Check-in berhasil. Lakukan pembayaran Early Check-in sekarang.';
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error("CheckIn Direct Error: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function checkOut(Request $request)
    {
        $roomId = $request->input('room_id');
        $room = Room::findOrFail($roomId);
        $paymentMethod = $request->input('payment_method', 'cash');

        if ($room->status !== 'occupied') {
            return response()->json(['message' => 'Kamar tidak sedang ditempati.'], 409);
        }

        try {
            DB::transaction(function () use ($room, $paymentMethod) {
                $activeCheckIn = CheckIn::where('room_id', $room->id)
                    ->where('is_active', true)
                    ->first();

                if ($activeCheckIn) {
                    $activeCheckIn->update([
                        'is_active' => false,
                        'check_out_time' => now(),
                    ]);

                    if ($activeCheckIn->booking) {
                        // Update status booking selesai
                        $activeCheckIn->booking->update(['status' => 'completed']);

                        // Jika ada early check in fee yg belum lunas (kasus jarang), lunaskan saat checkout?
                        // Optional: $activeCheckIn->booking->update(['early_payment_status' => 'paid']);
                    }
                }

                $room->update(['status' => 'dirty']);
            });

            return response()->json(['message' => 'Check-out berhasil.']);
        } catch (\Throwable $e) {
            Log::error('Gagal checkout: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat checkout.'], 500);
        }
    }

    public function store(Request $request)
    {
        return response()->json(['message' => 'Gunakan storeDirect atau storeFromBooking'], 501);
    }
}
