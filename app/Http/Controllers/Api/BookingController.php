<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Snap;
use Throwable;

class BookingController extends Controller
{
    /**
     * Menyimpan data booking baru dan membuat transaksi Midtrans.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        // Kalkulasi harga tetap dilakukan di awal
        $room = Room::findOrFail($validated['room_id']);
        $checkInDate = Carbon::parse($validated['check_in_date']);
        $checkOutDate = Carbon::parse($validated['check_out_date']);
        $durationInNights = $checkOutDate->diffInDays($checkInDate);
        $totalPrice = $room->price_per_night * $durationInNights;

        $booking = null;

        try {
            DB::transaction(function () use ($validated, $checkInDate, $checkOutDate, $totalPrice, &$booking) {
                // Kunci baris data kamar untuk mencegah double booking saat proses ini berjalan
                $lockedRoom = Room::where('id', $validated['room_id'])->lockForUpdate()->first();

                // Pastikan kamar masih tersedia tepat sebelum membuat booking
                if ($lockedRoom->status !== 'available') {
                    throw new \Exception('Maaf, kamar ini baru saja dipesan, Silakan pilih kamar lain.');
                }

                $guest = Guest::firstOrCreate(
                    ['email' => $validated['guest_email']],
                    ['name' => $validated['guest_name'], 'phone_number' => $validated['guest_phone']]
                );

                // Membuat record booking baru dengan status 'pending'. Ini sudah benar.
                $booking = Booking::create([
                    'room_id' => $lockedRoom->id,
                    'guest_id' => $guest->id,
                    'user_id' => auth()->id(),
                    'check_in_date' => $checkInDate,
                    'check_out_date' => $checkOutDate,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                ]);
            });

            // Logika setelah transaksi database berhasil
            if (!$booking) {
                throw new \Exception('Gagal membuat record booking di database.');
            }

            $midtransOrderId = 'BOOK-' . $booking->id . '-' . time();
            $booking->midtrans_order_id = $midtransOrderId;
            $booking->save();

            $params = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => (int) $totalPrice,
                ],
                'customer_details' => [
                    'first_name' => $validated['guest_name'],
                    'email' => $validated['guest_email'],
                    'phone' => $validated['guest_phone'],
                ],
            ];

            $snapToken = Snap::getSnapToken($params);

            return response()->json(['snap_token' => $snapToken]);

        } catch (Throwable $e) {
            // Karena kita tidak mengubah status kamar, blok catch ini menjadi lebih sederhana.
            // Tidak perlu mengembalikan status kamar lagi.
            Log::error('Booking Store Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses booking: ' . $e->getMessage()], 500);
        }
    }
}
