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
use Illuminate\Support\Facades\Validator;
use Midtrans\Snap;
use Midtrans\Config;

/**
 * Class CheckInController
 * * Menangani operasional Check-In, Check-Out, dan integrasi pembayaran Midtrans 
 * untuk tamu Walk-In maupun Reservasi.
 * * Controller ini memastikan integritas data antara status kamar, status booking,
 * dan pembayaran tambahan seperti Early Check-In.
 */
class CheckInController extends Controller
{
    /**
     * Inisialisasi konfigurasi Midtrans dari environment.
     * Konfigurasi ini diperlukan untuk generate Snap Token saat pembayaran online.
     */
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

    /**
     * Menghitung biaya Early Check-In berdasarkan jam operasional hotel.
     * Biaya dihitung per jam awal dari waktu check-in standar.
     * * @return int Total biaya early check-in
     */
    private function calculateEarlyCheckInFee()
    {
        $setting = Setting::first();
        if (!$setting || !$setting->early_check_in_fee || $setting->early_check_in_fee == 0) {
            return 0;
        }

        $now = Carbon::now('Asia/Jakarta');
        $checkInTimeSetting = $setting->check_in_time ?? '14:00';
        $feePerHour = $setting->early_check_in_fee;

        // Tentukan batas waktu check-in standar hari ini
        $standardCheckIn = Carbon::createFromFormat('H:i', substr($checkInTimeSetting, 0, 5), 'Asia/Jakarta')
            ->setDate($now->year, $now->month, $now->day);

        // Jika tamu check-in setelah waktu standar, tidak ada biaya tambahan
        if ($now->gte($standardCheckIn)) {
            return 0;
        }

        // Hitung selisih jam dan lakukan pembulatan ke atas jika ada menit tersisa
        $hoursEarly = $now->diffInHours($standardCheckIn);
        $minutesResidue = $now->diffInMinutes($standardCheckIn) % 60;

        if ($minutesResidue > 0) {
            $hoursEarly++;
        }

        return (int) ($hoursEarly * $feePerHour);
    }

    /**
     * Memproses integrasi pembayaran Midtrans.
     * Logika ini menggabungkan Harga Kamar (untuk Walk-In) dan Biaya Early Check-In.
     * * @param Booking $booking
     * @param string $paymentMethod ('cash' atau 'midtrans')
     * @param bool $isWalkIn
     * @return array ['fee' => int, 'snap_token' => string|null]
     */
    private function processCheckInPayment(Booking $booking, string $paymentMethod, bool $isWalkIn)
    {
        $earlyFee = $this->calculateEarlyCheckInFee();
        $roomPrice = $isWalkIn ? $booking->total_price : 0;
        $totalToPay = $roomPrice + $earlyFee;

        $snapToken = null;

        if ($totalToPay > 0) {
            $paymentStatus = ($paymentMethod === 'cash') ? 'paid' : 'unpaid';
            
            // Simpan status pembayaran awal ke database
            $booking->update([
                'early_check_in_fee' => $earlyFee,
                'early_payment_status' => $paymentStatus,
                'payment_status' => $isWalkIn ? $paymentStatus : $booking->payment_status
            ]);

            // Buat transaksi Midtrans jika metode pembayaran adalah non-tunai
            if ($paymentStatus === 'unpaid') {
                try {
                    $prefix = $isWalkIn ? 'WALKIN-' : 'EARLY-';
                    $midtransOrderId = $prefix . $booking->id . '-' . time();
                    
                    $itemDetails = [];
                    if ($isWalkIn) {
                        $itemDetails[] = [
                            'id' => 'ROOM-PAY',
                            'price' => (int) $roomPrice,
                            'quantity' => 1,
                            'name' => 'Pembayaran Kamar Walk-In'
                        ];
                    }
                    if ($earlyFee > 0) {
                        $itemDetails[] = [
                            'id' => 'EARLY-FEE',
                            'price' => (int) $earlyFee,
                            'quantity' => 1,
                            'name' => 'Biaya Early Check-In'
                        ];
                    }

                    $params = [
                        'transaction_details' => [
                            'order_id' => $midtransOrderId,
                            'gross_amount' => (int) $totalToPay,
                        ],
                        'customer_details' => [
                            'first_name' => $booking->guest->name ?? 'Guest',
                            'email' => $booking->guest->email ?? 'guest@example.com',
                        ],
                        'item_details' => $itemDetails
                    ];
                    
                    $snapToken = Snap::getSnapToken($params);
                    $booking->update(['midtrans_order_id' => $midtransOrderId]);
                } catch (\Exception $e) {
                    Log::error("Midtrans Snap Error pada Check-In: " . $e->getMessage());
                }
            }
        }

        return ['fee' => $earlyFee, 'snap_token' => $snapToken];
    }

    /**
     * Endpoint untuk memproses Check-In langsung.
     * Bisa digunakan untuk Walk-In baru maupun memproses booking yang sudah ada.
     */
    public function storeDirect(Request $request)
    {
        // Validasi input dasar
        $validator = Validator::make($request->all(), [
            'room_id'        => 'required|exists:rooms,id',
            'booking_id'     => 'nullable|exists:bookings,id',
            'guest_id'       => 'required_without_all:guest_name,booking_id|nullable|exists:guests,id',
            'guest_name'     => 'required_without_all:guest_id,booking_id|nullable|string',
            'check_out_date' => 'required_without:booking_id|nullable|date',
            'ktp_image'      => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal', 
                'errors' => $validator->errors()
            ], 422);
        }

        $bookingId = $request->booking_id;
        $isWalkIn = empty($bookingId);
        $paymentMethod = $request->payment_method ?? 'cash';

        try {
            // --- VALIDASI TANGGAL (Pencegahan check-in awal) ---
            if (!$isWalkIn) {
                $bookingCheck = Booking::findOrFail($bookingId);
                $todayStr = Carbon::today('Asia/Jakarta')->format('Y-m-d');
                
                if ($bookingCheck->check_in_date > $todayStr) {
                    return response()->json([
                        'message' => "Booking ini dijadwalkan untuk tanggal " . Carbon::parse($bookingCheck->check_in_date)->format('d M Y') . ". Belum bisa check-in hari ini."
                    ], 422);
                }
            }

            $feeResult = ['fee' => 0, 'snap_token' => null];

            DB::transaction(function () use ($request, $bookingId, $isWalkIn, $paymentMethod, &$feeResult) {
                $room = Room::findOrFail($request->room_id);
                
                // Ambil atau Buat Data Booking
                if (!$isWalkIn) {
                    $booking = Booking::with('guest')->findOrFail($bookingId);
                } else {
                    $checkInDate = Carbon::today('Asia/Jakarta');
                    $checkOutDate = Carbon::parse($request->check_out_date);
                    $nights = $checkInDate->diffInDays($checkOutDate);
                    
                    if ($nights < 1) $nights = 1;

                    // Buat Guest jika tidak ada ID yang dipilih
                    if (!$request->guest_id && $request->guest_name) {
                        $guest = Guest::create([
                            'name' => $request->guest_name,
                            'phone_number' => $request->guest_phone,
                            'email' => $request->guest_email
                        ]);
                    } else {
                        $guest = Guest::findOrFail($request->guest_id);
                    }

                    $totalRoomPrice = $room->price_per_night * $nights;

                    $booking = Booking::create([
                        'room_id' => $room->id,
                        'guest_id' => $guest->id,
                        'check_in_date' => $checkInDate->format('Y-m-d'),
                        'check_out_date' => $checkOutDate->format('Y-m-d'),
                        'status' => 'pending', 
                        'total_price' => $totalRoomPrice,
                        'verification_status' => 'verified',
                        'payment_status' => 'unpaid' 
                    ]);
                }

                // Pengelolaan Foto KTP
                if ($request->hasFile('ktp_image')) {
                    $guest = $booking->guest;
                    if ($guest && $guest->ktp_image) {
                        Storage::disk('public')->delete($guest->ktp_image);
                    }
                    $path = $request->file('ktp_image')->store('ktp_images', 'public');
                    $guest->update(['ktp_image' => $path]);
                }

                // Hitung Pembayaran & Generate Token
                $feeResult = $this->processCheckInPayment($booking, $paymentMethod, $isWalkIn);
                
                // --- LOGIKA ALUR CHECK-IN ---
                // Jika pembayaran tunai, langsung check-in. Jika Midtrans, tunggu webhook.
                if ($paymentMethod === 'cash') {
                    $booking->update([
                        'status' => 'checked_in', 
                        'check_in_time' => now()
                    ]);

                    CheckIn::create([
                        'room_id'        => $room->id,
                        'guest_id'       => $booking->guest_id,
                        'booking_id'     => $booking->id,
                        'check_in_time'  => now(),
                        'is_active'      => true,
                        'is_incognito'   => $request->boolean('is_incognito', false),
                    ]);

                    $room->update(['status' => 'occupied']);
                } else {
                    // Simpan preferensi incognito di notes agar dibaca oleh MidtransController nanti
                    $booking->update([
                        'notes' => $request->boolean('is_incognito', false) ? 'INCOGNITO' : 'NORMAL'
                    ]);
                }
            });

            return response()->json([
                'message' => $paymentMethod === 'cash' ? 'Check-in berhasil diproses!' : 'Menunggu pembayaran via QRIS/Midtrans.',
                'early_check_in_fee' => $feeResult['fee'],
                'snap_token' => $feeResult['snap_token']
            ]);

        } catch (\Exception $e) {
            Log::error("Gagal storeDirect: " . $e->getMessage());
            return response()->json([
                'message' => 'Gagal memproses check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alias untuk konsistensi routing jika diperlukan.
     */
    public function storeFromBooking(Request $request)
    {
        return $this->storeDirect($request);
    }

    /**
     * Menangani proses Check-Out Kamar.
     */
    public function checkOut(Request $request)
    {
        $roomId = $request->input('room_id');
        $room = Room::findOrFail($roomId);

        // Validasi apakah kamar memang sedang ditempati
        if ($room->status !== 'occupied') {
            return response()->json([
                'message' => 'Kamar tidak sedang dalam status Terisi.'
            ], 409);
        }

        try {
            DB::transaction(function () use ($room) {
                // Cari record Check-In yang masih aktif
                $active = CheckIn::where('room_id', $room->id)
                    ->where('is_active', true)
                    ->latest()
                    ->first();

                if ($active) {
                    $active->update([
                        'is_active' => false, 
                        'check_out_time' => now()
                    ]);

                    // Ubah status booking terkait menjadi completed
                    if ($active->booking_id) {
                        Booking::where('id', $active->booking_id)->update(['status' => 'completed']);
                    }
                }

                // Setelah check-out, status kamar otomatis menjadi kotor
                $room->update(['status' => 'dirty']);
            });

            return response()->json([
                'message' => 'Check-out berhasil. Kamar kini berstatus Kotor (Perlu Pembersihan).'
            ]);
        } catch (\Throwable $e) {
            Log::error("Gagal checkOut: " . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat check-out.'
            ], 500);
        }
    }
}