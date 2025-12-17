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
use Midtrans\Snap;
use Midtrans\Config; // Tambahan Import Penting
use Throwable;

class CheckInController extends Controller
{
    /**
     * Konstruktor untuk konfigurasi Midtrans
     * Pastikan Anda sudah set MIDTRANS_SERVER_KEY di .env
     */
    public function __construct()
    {
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        \Midtrans\Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

    /**
     * FUNGSI 1: CHECK-IN DARI BOOKING YANG SUDAH ADA
     * Digunakan ketika tamu yang sudah Booking (Confirmed/Paid) datang ke resepsionis.
     */
    public function storeFromBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::with(['room', 'guest'])->findOrFail($request->booking_id);

        // 1. Validasi Status Pembayaran
        // Pastikan status di database konsisten (huruf kecil/besar)
        if (!in_array(strtolower($booking->status), ['paid', 'confirmed', 'settlement'])) {
            return response()->json(['message' => 'Tamu belum melunasi booking atau status belum confirmed.'], 400);
        }

        // 2. Validasi Tanggal
        $today = Carbon::now()->startOfDay();
        $bookingDate = Carbon::parse($booking->check_in_date)->startOfDay();

        // Jika hari ini < tanggal booking (Booking untuk besok, tapi datang sekarang)
        if ($today->lt($bookingDate)) {
            return response()->json(['message' => 'Check-in gagal. Jadwal booking tamu ini adalah tanggal ' . $bookingDate->format('d M Y')], 400);
        }

        // 3. Validasi Fisik Kamar
        if ($booking->room->status === 'occupied') {
            return response()->json(['message' => 'Kamar fisik masih terisi tamu lain. Mohon lakukan Check-out pada tamu sebelumnya.'], 409);
        }

        try {
            DB::transaction(function () use ($booking) {
                // A. Buat Record di Tabel 'check_ins' (Tabel History Operasional)
                CheckIn::create([
                    'booking_id' => $booking->id,
                    'room_id' => $booking->room_id,
                    'guest_id' => $booking->guest_id,
                    'check_in_time' => now(),
                    'is_active' => true,
                    'is_incognito' => $booking->is_incognito,
                ]);

                // B. Update Status Kamar -> Occupied
                $booking->room->update(['status' => 'occupied']);

                // C. Update Status Booking -> Checked In & Isi Waktu Check-in
                // Ini menyambung dengan diskusi kita sebelumnya untuk update kolom 'checked_in_at'
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
     * Tamu datang, pilih kamar, langsung bayar di tempat (Cash/QRIS).
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
        ]);

        $room = Room::findOrFail($validated['room_id']);
        $guest = Guest::findOrFail($validated['guest_id']);

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

        // Hitung Harga & Durasi
        // Menggunakan max(1) untuk antisipasi jika checkin & checkout di hari yang sama (transit) tetap hitung 1 malam/hari
        $durationInNights = max(1, $checkOutDate->diffInDays($checkInDate));
        $totalPrice = $room->price_per_night * $durationInNights;

        // --- ALUR MIDTRANS (BAYAR DI MEJA VIA QRIS) ---
        if ($validated['payment_method'] === 'midtrans') {

            // Generate Order ID Unik
            $midtransOrderId = 'WALK-' . time() . '-' . rand(100, 999);

            $booking = Booking::create([
                'room_id' => $room->id,
                'guest_id' => $validated['guest_id'],
                'user_id' => $request->user()->id ?? null, // Handle jika user tidak login (opsional)
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'is_incognito' => $request->boolean('is_incognito'),
                'payment_method' => 'midtrans',
                'midtrans_order_id' => $midtransOrderId
            ]);

            $params = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => (int) $totalPrice,
                ],
                'customer_details' => [
                    'first_name' => $guest->name,
                    'phone' => $guest->phone_number,
                ],
                // Opsional: Tambahkan Item Details agar lebih rapi di struk Midtrans
                'item_details' => [[
                    'id' => 'ROOM-' . $room->id,
                    'price' => (int) $room->price_per_night,
                    'quantity' => $durationInNights,
                    'name' => 'Sewa Kamar ' . $room->number
                ]]
            ];

            try {
                $snapToken = Snap::getSnapToken($params);

                return response()->json([
                    'status' => 'pending_payment',
                    'message' => 'Booking dibuat. Silakan scan QRIS untuk check-in.',
                    'snap_token' => $snapToken,
                    'booking_id' => $booking->id
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Gagal koneksi ke Midtrans: ' . $e->getMessage()], 500);
            }
        }

        // --- ALUR CASH (LANGSUNG MASUK) ---
        try {
            DB::transaction(function () use ($validated, $room, $request, $checkInDate, $checkOutDate, $totalPrice) {

                // 1. Buat Booking (Langsung Lunas & Checked_in)
                $booking = Booking::create([
                    'room_id' => $room->id,
                    'guest_id' => $validated['guest_id'],
                    'user_id' => $request->user()->id ?? null,
                    'check_in_date' => $checkInDate,
                    'check_out_date' => $checkOutDate,
                    'total_price' => $totalPrice,
                    'status' => 'checked_in',
                    'checked_in_at' => now(), // ISI KOLOM BARU
                    'is_incognito' => $request->boolean('is_incognito'),
                    'payment_method' => 'cash',
                    'midtrans_order_id' => 'CASH-' . time() // Dummy ID agar kolom tidak null
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
