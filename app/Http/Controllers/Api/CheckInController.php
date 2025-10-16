<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\Room;
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
    $validated = $request->validate([
        'room_id' => 'required|exists:rooms,id',
        'guest_id' => 'required|exists:guests,id',
        'check_in_date' => 'required|date|after_or_equal:today',
        'check_out_date' => 'required|date|after:check_in_date',
        'payment_method' => 'required|string|in:cash,midtrans',
    ]);

    $room = Room::findOrFail($validated['room_id']);
    if ($room->status !== 'available') {
        return response()->json(['message' => 'Kamar ini tidak tersedia untuk check-in.'], 409);
    }

    try {
        $checkInDate = Carbon::parse($validated['check_in_date']);
        $checkOutDate = Carbon::parse($validated['check_out_date']);
        $durationInNights = $checkOutDate->diffInDays($checkInDate);
        $totalPrice = $room->price_per_night * $durationInNights;

        if ($validated['payment_method'] === 'cash') {
            DB::transaction(function () use ($validated, $checkInDate, $checkOutDate, $totalPrice, $room) {
                $booking = Booking::create([
                    'room_id' => $room->id, 'guest_id' => $validated['guest_id'], 'user_id' => auth()->id(),
                    'check_in_date' => $checkInDate, 'check_out_date' => $checkOutDate,
                    'total_price' => $totalPrice,
                    // [PERBAIKAN DI SINI] Ubah status menjadi 'completed' agar langsung masuk dasbor
                    'status' => 'completed',
                ]);
                CheckIn::create([
                    'booking_id' => $booking->id, 'room_id' => $room->id, 'guest_id' => $validated['guest_id'],
                    'check_in_time' => now(), 'is_active' => true,
                ]);
                $room->update(['status' => 'occupied']);
            });
            return response()->json(['message' => 'Check-in berhasil dicatat dengan pembayaran tunai.'], 201);
        }

        if ($validated['payment_method'] === 'midtrans') {
            $booking = Booking::create([
                'room_id' => $room->id, 'guest_id' => $validated['guest_id'], 'user_id' => auth()->id(),
                'check_in_date' => $checkInDate, 'check_out_date' => $checkOutDate,
                'total_price' => $totalPrice, 'status' => 'pending',
            ]);

            $midtransOrderId = 'BOOK-' . $booking->id . '-' . time();
            $booking->midtrans_order_id = $midtransOrderId;
            $booking->save();

            $guest = $booking->guest;
            $params = [
                'transaction_details' => [ 'order_id' => $midtransOrderId, 'gross_amount' => (int) $totalPrice, ],
                'customer_details' => [ 'first_name' => $guest->name, 'email' => $guest->email, 'phone' => $guest->phone_number, ],
            ];

            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken]);
        }

    } catch (Throwable $e) {
        Log::error('Gagal melakukan check-in manual: ' . $e->getMessage());
        return response()->json(['message' => 'Terjadi kesalahan saat proses check-in.'], 500);
    }
}

    /**
     * Menangani proses check-out manual oleh admin/resepsionis.
     */
    public function checkout(Room $room)
    {
        if ($room->status !== 'occupied') {
            return response()->json(['message' => 'Kamar ini tidak sedang ditempati.'], 409);
        }

        try {
            DB::transaction(function () use ($room) {
                $activeCheckIn = CheckIn::where('room_id', $room->id)->where('is_active', true)->first();
                if ($activeCheckIn) {
                    $activeCheckIn->update([
                        'is_active' => false,
                        'check_out_time' => now(),
                    ]);
                }

                // Ubah status kamar menjadi 'dirty' setelah check-out
                $room->update(['status' => 'dirty']);
            });

            return response()->json(['message' => 'Check-out berhasil. Kamar ditandai perlu dibersihkan.']);

        } catch (Throwable $e) {
            Log::error('Gagal melakukan check-out manual: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat proses check-out.'], 500);
        }
    }
}
