<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\Room;
use App\Models\Guest;
use App\Models\User;
use App\Models\Order;
use App\Models\Setting; // ✅ [BARU] Import Model Setting
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Midtrans\Snap;
use Midtrans\Config;

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
     * ✅ FUNGSI 1: CHECK-IN DARI BOOKING ONLINE
     * (Dipanggil saat Admin menekan Check-In pada booking yang sudah ada)
     */
    public function storeFromBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'is_incognito' => 'nullable|boolean',
            'ktp_image'  => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        $booking = Booking::with(['room', 'guest', 'user'])->findOrFail($request->booking_id);

        // Link User jika email cocok (Fix dashboard tamu)
        if (is_null($booking->user_id) && $booking->guest && $booking->guest->email) {
            $registeredUser = User::where('email', $booking->guest->email)->first();
            if ($registeredUser) {
                $booking->update(['user_id' => $registeredUser->id]);
                $booking->load('user');
            }
        }

        // 1. Validasi Status Pembayaran
        $validStatuses = ['paid', 'confirmed', 'settlement'];
        if (!in_array(strtolower($booking->status), $validStatuses)) {
            return response()->json([
                'message' => 'Booking belum lunas atau belum dikonfirmasi. Status saat ini: ' . $booking->status
            ], 400);
        }

        // 2. Validasi Tanggal
        $bookingDate = Carbon::parse($booking->check_in_date)->startOfDay();

        // 3. Validasi Kamar
        if ($booking->room->status === 'occupied') {
            return response()->json([
                'message' => 'Kamar masih terisi. Harap lakukan Check-out pada tamu sebelumnya.'
            ], 409);
        }

        // 4. Cek apakah sudah pernah check-in
        $existingCheckIn = CheckIn::where('booking_id', $booking->id)
            ->where('is_active', true)
            ->first();

        if ($existingCheckIn) {
            return response()->json([
                'message' => 'Booking ini sudah status Checked In.',
            ], 400);
        }

        try {
            DB::transaction(function () use ($booking, $request) {
                // Update KTP & Incognito
                $isIncognito = $request->boolean('is_incognito', false);

                if ($request->hasFile('ktp_image')) {
                    $path = $request->file('ktp_image')->store('ktp_images', 'public');
                    $booking->update(['ktp_image' => $path]);
                    if ($booking->guest) {
                        $booking->guest->update(['ktp_image' => $path]);
                    }
                }

                // === BUAT CHECK-IN RECORD ===
                CheckIn::create([
                    'booking_id' => $booking->id,
                    'room_id' => $booking->room_id,
                    'guest_id' => $booking->guest_id,
                    'check_in_time' => now(),
                    'is_active' => true,
                    'is_incognito' => $isIncognito,
                ]);

                // === UPDATE STATUS KAMAR & BOOKING ===
                $booking->room->update(['status' => 'occupied']);

                $booking->update([
                    'status' => 'checked_in',
                    'checked_in_at' => now(),
                    'is_incognito' => $isIncognito
                ]);
            });

            return response()->json(['message' => 'Check-in berhasil!']);
        } catch (\Exception $e) {
            Log::error('CheckIn Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses check-in.'], 500);
        }
    }

    /**
     * ✅ FUNGSI 2: WALK-IN (TAMU DATANG LANGSUNG)
     * (Dipanggil oleh route /admin/check-ins/store-direct)
     * UPDATE: Mengambil Jam Check-out dari Database Setting
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_id' => 'nullable|exists:guests,id',
            'guest_name' => 'required_without:guest_id|string',
            'name'       => 'nullable|string',
            'guest_phone' => 'nullable|string',
            'phone'       => 'nullable|string',
            'guest_email' => 'nullable|email',
            'duration'       => 'nullable|integer|min:1',
            'check_out_date' => 'nullable|date|after:today',
            'price_per_night' => 'nullable|numeric',
            'deposit' => 'nullable|numeric',
            'ktp_image' => 'nullable|image|max:4096',
            'is_incognito' => 'nullable|boolean'
        ]);

        if (!$request->filled('duration') && !$request->filled('check_out_date')) {
            return response()->json(['message' => 'Harap tentukan durasi menginap atau tanggal check-out.'], 422);
        }

        return DB::transaction(function () use ($request) {
            // A. Cek Status Kamar
            $room = Room::lockForUpdate()->find($request->room_id);
            if ($room->status === 'occupied') {
                throw new \Exception('Kamar sedang terisi! Silakan check-out tamu sebelumnya.');
            }

            // B. Handle Data Tamu
            if ($request->filled('guest_id')) {
                $guest = Guest::find($request->guest_id);
            } else {
                $phone = $request->guest_phone ?? $request->phone;
                $name  = $request->guest_name ?? $request->name;

                $guest = Guest::updateOrCreate(
                    ['phone_number' => $phone],
                    [
                        'name' => $name,
                        'email' => $request->guest_email,
                        'is_verified' => false
                    ]
                );
            }

            if ($request->hasFile('ktp_image')) {
                $ktpPath = $request->file('ktp_image')->store('ktp_images', 'public');
                $guest->update(['ktp_image' => $ktpPath]);
            }

            // --- [LOGIKA WAKTU DINAMIS] ---
            $checkInTime = Carbon::now();

            // 1. Ambil Setting Jam Checkout dari DB (Default: 12:00)
            $setting = Setting::first();
            $checkoutTimeStr = $setting ? $setting->check_out_time : '12:00';

            // 2. Parse Jam dan Menit (Format H:i)
            $timeParts = explode(':', $checkoutTimeStr);
            $outHour = isset($timeParts[0]) ? (int)$timeParts[0] : 12;
            $outMinute = isset($timeParts[1]) ? (int)$timeParts[1] : 0;

            if ($request->filled('check_out_date')) {
                // Jika input tanggal manual, set jamnya sesuai setting
                $checkOutTime = Carbon::parse($request->check_out_date)->setTime($outHour, $outMinute, 0);

                $duration = max(1, (int) ceil($checkInTime->diffInDays($checkOutTime, false)));
                if ($checkOutTime->isPast()) {
                    $duration = 1;
                    $checkOutTime = Carbon::now()->addDay()->setTime($outHour, $outMinute, 0);
                }
            } else {
                // Jika input durasi, tambah hari lalu set jam sesuai setting
                $duration = (int) $request->duration;
                $checkOutTime = Carbon::now()->addDays($duration)->setTime($outHour, $outMinute, 0);
            }
            // -----------------------------

            $pricePerNight = $request->price_per_night ?? $room->price;
            $totalPrice = $pricePerNight * $duration;

            // D. Buat Booking
            $booking = Booking::create([
                'guest_id' => $guest->id,
                'room_id' => $room->id,
                'check_in_date' => $checkInTime,
                'check_out_date' => $checkOutTime,
                'total_price' => $totalPrice,
                'status' => 'checked_in',
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'source' => 'walk_in',
                'is_incognito' => $request->boolean('is_incognito'),
                'checked_in_at' => now(),
                'midtrans_order_id' => 'WALKIN-' . time()
            ]);

            // E. Buat Data CheckIn
            $checkIn = CheckIn::create([
                'booking_id' => $booking->id,
                'guest_id' => $guest->id,
                'room_id' => $room->id,
                'check_in_time' => $checkInTime,
                'is_active' => true,
                'deposit_amount' => $request->deposit ?? 0,
                'is_incognito' => $request->boolean('is_incognito')
            ]);

            // F. Update Kamar
            $room->update(['status' => 'occupied']);

            return response()->json([
                'message' => 'Check-in Walk-in berhasil.',
                'data' => $checkIn
            ]);
        });
    }

    /**
     * CHECK-OUT
     */
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
                    Order::where('room_id', $room->id)
                        ->where('guest_id', $activeCheckIn->guest_id)
                        ->whereNotIn('status', ['paid', 'cancelled'])
                        ->update([
                            'status' => 'paid',
                            'payment_method' => $paymentMethod,
                            'updated_at' => now()
                        ]);

                    $activeCheckIn->update([
                        'is_active' => false,
                        'check_out_time' => now(),
                    ]);

                    if ($activeCheckIn->booking) {
                        $activeCheckIn->booking->update(['status' => 'completed']);
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
}
