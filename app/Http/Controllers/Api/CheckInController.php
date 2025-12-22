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
use Throwable;

class CheckInController extends Controller
{
    public function __construct()
    {
        // Konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

    /**
     * FUNGSI 1: CHECK-IN DARI BOOKING ONLINE (Yang sudah lunas sebelumnya)
     * ✅ FIXED: Accept 'confirmed' status (booking yang sudah diverifikasi admin)
     */
    public function storeFromBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'ktp_image'  => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        $booking = Booking::with(['room', 'guest'])->findOrFail($request->booking_id);

        // 1. ✅ FIXED: Validasi Status Pembayaran - Terima 'confirmed' status
        $validStatuses = ['paid', 'confirmed', 'settlement'];
        if (!in_array(strtolower($booking->status), $validStatuses)) {
            return response()->json([
                'message' => 'Tamu belum melunasi booking atau belum diverifikasi admin. Status saat ini: ' . $booking->status
            ], 400);
        }

        // 2. Validasi Tanggal (Hanya boleh check-in hari ini atau setelahnya)
        $today = Carbon::now()->startOfDay();
        $bookingDate = Carbon::parse($booking->check_in_date)->startOfDay();

        if ($today->lt($bookingDate)) {
            return response()->json([
                'message' => 'Check-in gagal. Jadwal booking tamu ini adalah tanggal ' . $bookingDate->format('d M Y')
            ], 400);
        }

        // 3. Validasi Fisik Kamar
        if ($booking->room->status === 'occupied') {
            return response()->json([
                'message' => 'Kamar fisik masih terisi tamu lain. Mohon lakukan Check-out pada tamu sebelumnya.'
            ], 409);
        }

        try {
            DB::transaction(function () use ($booking, $request) {

                // [LOGIC GAMBAR - No changes needed]
                $finalKtpPath = null;

                if ($request->hasFile('ktp_image')) {
                    $finalKtpPath = $request->file('ktp_image')->store('ktp_images', 'public');
                } elseif ($booking->ktp_image) {
                    $finalKtpPath = $booking->ktp_image;
                }

                if ($finalKtpPath) {
                    $booking->update([
                        'ktp_image' => $finalKtpPath,
                        'verification_status' => $booking->verification_status // Jangan ubah jika sudah verified
                    ]);

                    if ($booking->guest && $booking->verification_status !== 'verified') {
                        $booking->guest->update([
                            'ktp_image' => $finalKtpPath,
                            'is_verified' => false
                        ]);
                    }
                }

                // A. Buat Record CheckIn Operasional
                CheckIn::create([
                    'booking_id' => $booking->id,
                    'room_id' => $booking->room_id,
                    'guest_id' => $booking->guest_id,
                    'check_in_time' => now(),
                    'is_active' => true,
                    'is_incognito' => $booking->is_incognito,
                ]);

                // B. Update Status Kamar
                $booking->room->update(['status' => 'occupied']);

                // C. Update Status Booking
                $booking->update([
                    'status' => 'checked_in',
                    'checked_in_at' => now()
                ]);
            });

            return response()->json(['message' => 'Proses Check-in berhasil. Selamat datang tamu!']);
        } catch (\Exception $e) {
            Log::error('Checkin Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses check-in: ' . $e->getMessage()], 500);
        }
    }

    /**
     * FUNGSI 2: CHECK-IN WALK-IN (TAMU DATANG LANGSUNG)
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
            'ktp_image'      => 'nullable|image|mimes:jpeg,png,jpg|max:4096', // Foto KTP
        ]);

        $room = Room::findOrFail($validated['room_id']);
        $guest = Guest::findOrFail($validated['guest_id']);

        // Cek Blacklist
        if ($guest->is_blacklisted) {
            return response()->json(['message' => 'Tamu Blacklist: ' . ($guest->blacklist_reason ?? '')], 403);
        }

        // Cek 1: Fisik Kamar
        if ($room->status === 'occupied') {
            return response()->json(['message' => 'Kamar sedang terisi tamu lain.'], 409);
        }

        // Cek 2: Konflik Booking
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
            return response()->json(['message' => 'Maaf, kamar ini sudah dibooking orang lain untuk tanggal tersebut.'], 409);
        }

        // Proses Upload KTP (Simpan path sementara)
        $ktpPath = null;
        if ($request->hasFile('ktp_image')) {
            $ktpPath = $request->file('ktp_image')->store('ktp_images', 'public');
        }

        // Hitung Harga
        $durationInNights = max(1, $checkOutDate->diffInDays($checkInDate));
        $totalPrice = $room->price_per_night * $durationInNights;

        // --- ALUR MIDTRANS (QRIS) ---
        if ($validated['payment_method'] === 'midtrans') {

            $midtransOrderId = 'WALK-' . time() . '-' . rand(100, 999);

            try {
                DB::transaction(function () use ($room, $validated, $request, $totalPrice, $midtransOrderId, $ktpPath) {

                    // Update Guest Profile jika ada KTP baru
                    if ($ktpPath) {
                        Guest::where('id', $validated['guest_id'])->update([
                            'ktp_image' => $ktpPath,
                            'is_verified' => false
                        ]);
                    }

                    // Create Booking Pending
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
                        'verification_status' => 'pending' // Penting agar masuk admin verifikasi
                    ]);
                });

                // Request ke Midtrans
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
                return response()->json(['message' => 'Gagal koneksi ke Midtrans: ' . $e->getMessage()], 500);
            }
        }

        // --- ALUR CASH (LANGSUNG MASUK) ---
        try {
            DB::transaction(function () use ($validated, $room, $request, $checkInDate, $checkOutDate, $totalPrice, $ktpPath) {

                // [PENTING] Update Data Guest Utama jika ada KTP baru
                if ($ktpPath) {
                    Guest::where('id', $validated['guest_id'])->update([
                        'ktp_image' => $ktpPath,
                        'is_verified' => false // Set false agar masuk list verifikasi
                    ]);
                }

                // 1. Buat Booking (Langsung Checked_in)
                $booking = Booking::create([
                    'room_id' => $room->id,
                    'guest_id' => $validated['guest_id'],
                    'user_id' => $request->user()->id ?? null,
                    'check_in_date' => $checkInDate,
                    'check_out_date' => $checkOutDate,
                    'total_price' => $totalPrice,
                    'status' => 'checked_in', // Karena Cash & Walk-in, langsung masuk
                    'checked_in_at' => now(),
                    'is_incognito' => $request->boolean('is_incognito'),
                    'payment_method' => 'cash',
                    'midtrans_order_id' => 'CASH-' . time(),

                    // Data Verifikasi
                    'ktp_image' => $ktpPath,
                    'verification_status' => 'pending' // Pending agar admin memverifikasi foto KTP
                ]);

                // 2. Buat Data CheckIn Operasional
                CheckIn::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'guest_id' => $validated['guest_id'],
                    'check_in_time' => now(),
                    'is_active' => true,
                    'is_incognito' => $booking->is_incognito,
                ]);

                // 3. Update Status Kamar
                $room->update(['status' => 'occupied']);
            });

            return response()->json(['message' => 'Check-in Walk-in berhasil (Cash).']);
        } catch (\Throwable $e) {
            Log::error('Gagal Walk-in Cash: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses check-in.'], 500);
        }
    }

    /**
     * PROSES CHECK-OUT
     */
    public function checkout(Request $request, Room $room)
    {
        $paymentMethod = $request->input('payment_method', 'cash');

        if ($room->status !== 'occupied') {
            return response()->json(['message' => 'Kamar ini tidak sedang ditempati.'], 409);
        }

        try {
            DB::transaction(function () use ($room, $paymentMethod) {
                $activeCheckIn = CheckIn::where('room_id', $room->id)
                    ->where('is_active', true)
                    ->first();

                if ($activeCheckIn) {
                    // Lunasi semua Order Makanan
                    Order::where('room_id', $room->id)
                        ->where('guest_id', $activeCheckIn->guest_id)
                        ->whereNotIn('status', ['paid', 'cancelled'])
                        ->update([
                            'status' => 'paid',
                            'payment_method' => $paymentMethod,
                            'updated_at' => now()
                        ]);

                    // Selesaikan CheckIn
                    $activeCheckIn->update([
                        'is_active' => false,
                        'check_out_time' => now(),
                    ]);

                    // Update status Booking jadi completed
                    if ($activeCheckIn->booking) {
                        $activeCheckIn->booking->update(['status' => 'completed']);
                    }
                }

                $room->update(['status' => 'dirty']);
            });

            return response()->json(['message' => 'Check-out berhasil.']);
        } catch (Throwable $e) {
            Log::error('Gagal checkout: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat checkout.'], 500);
        }
    }
}
