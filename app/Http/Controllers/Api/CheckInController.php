<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\Room;
use App\Models\Guest;
use App\Models\Order;
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
     * ✅ FUNGSI UTAMA: CHECK-IN DARI BOOKING (Dipanggil dari CheckInModal mode: booking_existing)
     * Method ini yang dipanggil oleh route: /check-in/process-booking
     */
    public function storeFromBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'is_incognito' => 'nullable|boolean',
            'ktp_image'  => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        $booking = Booking::with(['room', 'guest', 'user'])->findOrFail($request->booking_id);

        // 🔍 DEBUG LOG 1
        Log::info('🔍 [CHECK-IN] Starting process', [
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'guest_id' => $booking->guest_id,
            'guest_name' => $booking->guest->name ?? 'N/A',
            'current_status' => $booking->status,
            'room_number' => $booking->room->room_number,
            'room_status' => $booking->room->status
        ]);

        // 1. Validasi Status Pembayaran
        $validStatuses = ['paid', 'confirmed', 'settlement'];
        if (!in_array(strtolower($booking->status), $validStatuses)) {
            Log::warning('⚠️ [CHECK-IN] Invalid booking status', [
                'booking_id' => $booking->id,
                'status' => $booking->status
            ]);
            return response()->json([
                'message' => 'Booking belum lunas atau belum dikonfirmasi. Status: ' . $booking->status
            ], 400);
        }

        // 2. Validasi Tanggal
        $today = Carbon::now()->startOfDay();
        $bookingDate = Carbon::parse($booking->check_in_date)->startOfDay();

        if ($today->lt($bookingDate)) {
            return response()->json([
                'message' => 'Check-in terlalu dini. Jadwal: ' . $bookingDate->format('d M Y')
            ], 400);
        }

        // 3. Validasi Kamar
        if ($booking->room->status === 'occupied') {
            return response()->json([
                'message' => 'Kamar masih terisi. Lakukan check-out dulu.'
            ], 409);
        }

        // 4. Cek apakah sudah pernah check-in
        $existingCheckIn = CheckIn::where('booking_id', $booking->id)
            ->where('is_active', true)
            ->first();

        if ($existingCheckIn) {
            Log::warning('⚠️ [CHECK-IN] Already checked in', [
                'booking_id' => $booking->id,
                'checkin_id' => $existingCheckIn->id
            ]);
            return response()->json([
                'message' => 'Booking ini sudah di-check-in sebelumnya.',
                'checkin_data' => $existingCheckIn
            ], 400);
        }

        try {
            DB::transaction(function () use ($booking, $request) {

                // Update incognito jika ada
                $isIncognito = $request->boolean('is_incognito', false);

                // Handle KTP Image jika ada upload baru
                $finalKtpPath = $booking->ktp_image;
                if ($request->hasFile('ktp_image')) {
                    $finalKtpPath = $request->file('ktp_image')->store('ktp_images', 'public');

                    // Update booking & guest
                    $booking->update(['ktp_image' => $finalKtpPath]);
                    if ($booking->guest) {
                        $booking->guest->update([
                            'ktp_image' => $finalKtpPath,
                            'is_verified' => false
                        ]);
                    }
                }

                // === BUAT CHECK-IN RECORD ===
                $checkIn = CheckIn::create([
                    'booking_id' => $booking->id,
                    'room_id' => $booking->room_id,
                    'guest_id' => $booking->guest_id,
                    'check_in_time' => now(),
                    'is_active' => true,
                    'is_incognito' => $isIncognito,
                ]);

                Log::info('✅ [CHECK-IN] Record created', [
                    'checkin_id' => $checkIn->id,
                    'booking_id' => $checkIn->booking_id,
                    'guest_id' => $checkIn->guest_id,
                    'room_id' => $checkIn->room_id,
                    'is_active' => $checkIn->is_active
                ]);

                // === UPDATE ROOM STATUS ===
                $booking->room->update(['status' => 'occupied']);

                Log::info('✅ [CHECK-IN] Room updated', [
                    'room_id' => $booking->room_id,
                    'new_status' => 'occupied'
                ]);

                // === UPDATE BOOKING STATUS ===
                $booking->update([
                    'status' => 'checked_in',
                    'checked_in_at' => now(),
                    'is_incognito' => $isIncognito
                ]);

                Log::info('✅ [CHECK-IN] Booking updated', [
                    'booking_id' => $booking->id,
                    'new_status' => 'checked_in',
                    'checked_in_at' => $booking->checked_in_at,
                    'user_id' => $booking->user_id
                ]);
            });

            // === VERIFIKASI FINAL ===
            $verifyCheckIn = CheckIn::where('booking_id', $booking->id)
                ->where('is_active', true)
                ->with(['booking.user', 'guest', 'room'])
                ->first();

            $verifyBooking = Booking::find($booking->id);

            Log::info('🎉 [CHECK-IN] Process completed successfully', [
                'booking_id' => $booking->id,
                'checkin_exists' => $verifyCheckIn ? 'YES' : 'NO',
                'checkin_id' => $verifyCheckIn?->id,
                'booking_status' => $verifyBooking->status,
                'user_id' => $verifyBooking->user_id,
                'guest_id' => $verifyBooking->guest_id
            ]);

            return response()->json([
                'message' => 'Check-in berhasil! Selamat datang 🎉',
                'data' => [
                    'checkin' => $verifyCheckIn,
                    'booking' => $verifyBooking
                ],
                'debug' => [
                    'booking_id' => $booking->id,
                    'user_id' => $verifyBooking->user_id,
                    'guest_id' => $verifyBooking->guest_id,
                    'checkin_id' => $verifyCheckIn?->id,
                    'status' => $verifyBooking->status
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ [CHECK-IN] Failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Gagal check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FUNGSI 2: CHECK-IN WALK-IN
     */
    public function storeWalkIn(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_id' => 'required|exists:guests,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'payment_method' => 'required|string|in:cash,midtrans',
            'is_incognito'   => 'nullable|boolean',
            'ktp_image'      => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        $room = Room::findOrFail($validated['room_id']);
        $guest = Guest::findOrFail($validated['guest_id']);

        // Cek Blacklist
        if ($guest->is_blacklisted) {
            return response()->json(['message' => 'Tamu Blacklist: ' . ($guest->blacklist_reason ?? '')], 403);
        }

        // Cek Kamar
        if ($room->status === 'occupied') {
            return response()->json(['message' => 'Kamar sedang terisi.'], 409);
        }

        // Cek Konflik Booking
        $checkInDate = Carbon::parse($validated['check_in_date']);
        $checkOutDate = Carbon::parse($validated['check_out_date']);

        $isBooked = Booking::where('room_id', $room->id)
            ->whereIn('status', ['confirmed', 'paid', 'checked_in'])
            ->where(function ($query) use ($checkInDate, $checkOutDate) {
                $query->where('check_in_date', '<', $checkOutDate)
                    ->where('check_out_date', '>', $checkInDate);
            })
            ->exists();

        if ($isBooked) {
            return response()->json(['message' => 'Kamar sudah dibooking untuk tanggal tersebut.'], 409);
        }

        // Upload KTP
        $ktpPath = null;
        if ($request->hasFile('ktp_image')) {
            $ktpPath = $request->file('ktp_image')->store('ktp_images', 'public');
        }

        // Hitung Harga
        $durationInNights = max(1, $checkOutDate->diffInDays($checkInDate));
        $totalPrice = $room->price_per_night * $durationInNights;

        // --- MIDTRANS (QRIS) ---
        if ($validated['payment_method'] === 'midtrans') {
            $midtransOrderId = 'WALK-' . time() . '-' . rand(100, 999);

            try {
                DB::transaction(function () use ($room, $validated, $request, $totalPrice, $midtransOrderId, $ktpPath) {
                    if ($ktpPath) {
                        Guest::where('id', $validated['guest_id'])->update([
                            'ktp_image' => $ktpPath,
                            'is_verified' => false
                        ]);
                    }

                    Booking::create([
                        'room_id' => $room->id,
                        'guest_id' => $validated['guest_id'],
                        'user_id' => $request->user()->id ?? null,
                        'check_in_date' => $validated['check_in_date'],
                        'check_out_date' => $validated['check_out_date'],
                        'total_price' => $totalPrice,
                        'status' => 'pending',
                        'is_incognito' => $request->boolean('is_incognito'),
                        'payment_method' => 'midtrans',
                        'midtrans_order_id' => $midtransOrderId,
                        'ktp_image' => $ktpPath,
                        'verification_status' => 'pending'
                    ]);
                });

                $params = [
                    'transaction_details' => [
                        'order_id' => $midtransOrderId,
                        'gross_amount' => (int) $totalPrice,
                    ],
                    'customer_details' => [
                        'first_name' => $guest->name,
                        'phone' => $guest->phone_number,
                    ],
                    'item_details' => [[
                        'id' => 'ROOM-' . $room->id,
                        'price' => (int) $room->price_per_night,
                        'quantity' => $durationInNights,
                        'name' => 'Sewa Kamar ' . $room->room_number
                    ]]
                ];

                $snapToken = Snap::getSnapToken($params);

                return response()->json([
                    'status' => 'pending_payment',
                    'message' => 'Booking dibuat. Silakan scan QRIS.',
                    'snap_token' => $snapToken
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Gagal koneksi Midtrans: ' . $e->getMessage()], 500);
            }
        }

        // --- CASH (LANGSUNG) ---
        try {
            DB::transaction(function () use ($validated, $room, $request, $checkInDate, $checkOutDate, $totalPrice, $ktpPath) {

                if ($ktpPath) {
                    Guest::where('id', $validated['guest_id'])->update([
                        'ktp_image' => $ktpPath,
                        'is_verified' => false
                    ]);
                }

                $booking = Booking::create([
                    'room_id' => $room->id,
                    'guest_id' => $validated['guest_id'],
                    'user_id' => $request->user()->id ?? null,
                    'check_in_date' => $checkInDate,
                    'check_out_date' => $checkOutDate,
                    'total_price' => $totalPrice,
                    'status' => 'checked_in',
                    'checked_in_at' => now(),
                    'is_incognito' => $request->boolean('is_incognito'),
                    'payment_method' => 'cash',
                    'midtrans_order_id' => 'CASH-' . time(),
                    'ktp_image' => $ktpPath,
                    'verification_status' => 'pending'
                ]);

                CheckIn::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'guest_id' => $validated['guest_id'],
                    'check_in_time' => now(),
                    'is_active' => true,
                    'is_incognito' => $booking->is_incognito,
                ]);

                $room->update(['status' => 'occupied']);
            });

            return response()->json(['message' => 'Check-in Walk-in berhasil (Cash).']);
        } catch (\Throwable $e) {
            Log::error('Gagal Walk-in Cash: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal check-in.'], 500);
        }
    }

    /**
     * ✅ CHECK-OUT
     */
    public function checkout(Request $request, Room $room)
    {
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
