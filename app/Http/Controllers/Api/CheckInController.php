<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\Room;
use App\Models\Guest; // Pastikan model Guest diimport
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Snap;
use Throwable;

class CheckInController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_id' => 'required|exists:guests,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'payment_method' => 'required|string|in:cash,midtrans',
            'is_incognito'   => 'nullable|boolean',
        ]);

        $room = Room::findOrFail($validated['room_id']);
        $guest = Guest::findOrFail($validated['guest_id']); // Ambil data tamu untuk Midtrans
        if ($guest->is_blacklisted) {
            return response()->json([
                'message' => 'Tamu ini masuk daftar BLACKLIST dan tidak dapat melakukan Check-in. Alasan: ' . ($guest->blacklist_reason ?? 'Tidak disebutkan')
            ], 403);
        }   

        // Hitung Harga
        $checkInDate = Carbon::parse($validated['check_in_date']);
        $checkOutDate = Carbon::parse($validated['check_out_date']);
        $durationInNights = $checkOutDate->diffInDays($checkInDate) ?: 1; // Minimal 1 malam
        $totalPrice = $room->price_per_night * $durationInNights;

        // ============================================================
        // SKENARIO 1: PEMBAYARAN VIA MIDTRANS (QRIS / TRANSFER)
        // ============================================================
        if ($validated['payment_method'] === 'midtrans') {
            try {
                // 1. Cek ketersediaan fisik kamar dulu (Strict untuk Walk-in Online)
                if ($room->status !== 'available') {
                    // Kecuali jika ini bookingan dia sendiri yang sudah confirm (Edge case)
                    // Tapi biasanya fitur "Bayar Sekarang" di modal dipakai untuk Walk-in
                    return response()->json(['message' => 'Kamar sedang terisi. Tidak bisa memproses pembayaran baru.'], 409);
                }

                // 2. Buat Booking dengan status PENDING
                // Kita TIDAK membuat data CheckIn di sini. CheckIn dibuat otomatis oleh MidtransController (Webhook)
                $booking = Booking::create([
                    'room_id' => $room->id,
                    'user_id' => $request->user() ? $request->user()->id : null, // Admin yang proses
                    'guest_id' => $validated['guest_id'],
                    'check_in_date' => $validated['check_in_date'],
                    'check_out_date' => $validated['check_out_date'],
                    'total_price' => $totalPrice,
                    'status' => 'pending', // <--- PENTING: Pending dulu
                    'is_incognito' => $request->boolean('is_incognito'),
                    'payment_method' => 'midtrans'
                ]);

                // 3. Generate Order ID sesuai format MidtransController (BOOK-{id}-{timestamp})
                $midtransOrderId = 'BOOK-' . $booking->id . '-' . time();
                $booking->midtrans_order_id = $midtransOrderId;
                $booking->save();

                // 4. Siapkan Parameter Midtrans
                $params = [
                    'transaction_details' => [
                        'order_id' => $midtransOrderId,
                        'gross_amount' => (int) $totalPrice,
                    ],
                    'customer_details' => [
                        'first_name' => $guest->name,
                        'phone' => $guest->phone_number ?? '',
                    ],
                    'item_details' => [
                        [
                            'id' => 'ROOM-' . $room->id,
                            'price' => (int) $room->price_per_night,
                            'quantity' => (int) $durationInNights,
                            'name' => "Sewa Kamar {$room->room_number} ({$durationInNights} Malam)"
                        ]
                    ]
                ];

                // 5. Minta Snap Token
                $snapToken = Snap::getSnapToken($params);

                // 6. Return ke Vue (CheckInModal.vue akan menangkap ini dan buka popup)
                return response()->json([
                    'status' => 'pending_payment',
                    'message' => 'Silakan selesaikan pembayaran.',
                    'snap_token' => $snapToken,
                    'booking_id' => $booking->id
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal generate Midtrans Token: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal memproses pembayaran online: ' . $e->getMessage()], 500);
            }
        }

        // ============================================================
        // SKENARIO 2: PEMBAYARAN TUNAI (CASH) / EXISTING BOOKING
        // ============================================================
        try {
            DB::transaction(function () use ($validated, $room, $request, $checkInDate, $checkOutDate, $totalPrice) {

                // Cek Booking Online Existing (Logika Lama Anda)
                $existingBooking = Booking::where('room_id', $room->id)
                    ->where('guest_id', $validated['guest_id'])
                    ->whereIn('status', ['confirmed', 'paid'])
                    ->whereDate('check_in_date', $checkInDate->format('Y-m-d'))
                    ->first();

                $isIncognito = $request->boolean('is_incognito');

                if ($existingBooking && $existingBooking->is_incognito) {
                    $isIncognito = true;
                }

                if ($existingBooking) {
                    // A. CHECK-IN DARI BOOKING ONLINE (Sudah Bayar sebelumnya)
                    $booking = $existingBooking;
                    $booking->update([
                        'status' => 'checked_in',
                        'check_out_date' => $checkOutDate,
                        'total_price' => $totalPrice
                    ]);
                } else {
                    // B. WALK-IN CASH
                    if ($room->status !== 'available') {
                        throw new \Exception('Kamar ini sedang terisi.');
                    }

                    $booking = Booking::create([
                        'room_id' => $room->id,
                        'user_id' => $request->user() ? $request->user()->id : null,
                        'guest_id' => $validated['guest_id'],
                        'check_in_date' => $checkInDate,
                        'check_out_date' => $checkOutDate,
                        'total_price' => $totalPrice,
                        'status' => 'checked_in', // Langsung masuk
                        'is_incognito' => $isIncognito,
                        'payment_method' => 'cash'
                    ]);
                }

                // Create CheckIn Record
                CheckIn::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'guest_id' => $validated['guest_id'],
                    'check_in_time' => now(),
                    'is_active' => true,
                    'is_incognito' => $isIncognito,
                ]);

                // Update Status Kamar
                $room->update(['status' => 'occupied']);
            });

            return response()->json(['message' => 'Check-in berhasil diproses (Cash).']);
        } catch (\Throwable $e) {
            Log::error('Gagal check-in cash: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * Proses Check-out Tamu & Pelunasan Folio Otomatis
     * (Tidak ada perubahan, kode Anda sudah benar di sini)
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
                    // Lunasi semua Order
                    \App\Models\Order::where('room_id', $room->id)
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

                    // Update status Booking jadi completed (history)
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
