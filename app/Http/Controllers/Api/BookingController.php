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
        // 1. Validasi Input
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'is_incognito'   => 'nullable|boolean',
        ]);

        // =========================================================================
        // [BARU] LOGIKA CEK BLACKLIST
        // =========================================================================
        // Mengecek apakah email ATAU no hp tamu ini ada di daftar blacklist
        $blacklistedGuest = Guest::where('is_blacklisted', true)
            ->where(function ($query) use ($validated) {
                $query->where('email', $validated['guest_email'])
                    ->orWhere('phone_number', $validated['guest_phone']);
            })
            ->first();

        // Jika ditemukan tamu blacklist, tolak dengan kode 403
        if ($blacklistedGuest) {
            $reason = $blacklistedGuest->blacklist_reason ? " (Alasan: {$blacklistedGuest->blacklist_reason})" : "";
            return response()->json([
                'message' => 'Maaf, permintaan booking Anda ditolak karena identitas Anda masuk dalam daftar Blacklist hotel kami.' . $reason
            ], 403);
        }
        // =========================================================================

        // 2. Kalkulasi Harga
        $room = Room::findOrFail($validated['room_id']);
        $checkInDate = Carbon::parse($validated['check_in_date']);
        $checkOutDate = Carbon::parse($validated['check_out_date']);

        // Pastikan durasi minimal 1 malam (jika user pilih tanggal yang sama/jam beda tipis)
        $durationInNights = $checkOutDate->diffInDays($checkInDate);
        if ($durationInNights < 1) {
            $durationInNights = 1;
        }

        $totalPrice = $room->price_per_night * $durationInNights;
        $booking = null;

        try {
            DB::transaction(function () use ($validated, $checkInDate, $checkOutDate, $totalPrice, &$booking, $request) {
                // 3. Kunci baris data kamar (Pessimistic Locking)
                $lockedRoom = Room::where('id', $validated['room_id'])->lockForUpdate()->first();

                // Pastikan kamar masih tersedia (Available)
                if ($lockedRoom->status !== 'available') {
                    throw new \Exception('Maaf, kamar ini baru saja dipesan orang lain. Silakan pilih kamar lain.');
                }

                // 4. Cari atau Buat Data Tamu (Guest)
                $guest = Guest::firstOrCreate(
                    ['email' => $validated['guest_email']],
                    ['name' => $validated['guest_name'], 'phone_number' => $validated['guest_phone']]
                );

                // Pastikan tamu yang baru di-fetch/create tidak di-blacklist (Double check)
                if ($guest->is_blacklisted) {
                    throw new \Exception('Identitas tamu terdeteksi sebagai Blacklist.');
                }

                // 5. Buat Record Booking
                $booking = Booking::create([
                    'room_id' => $lockedRoom->id,
                    'guest_id' => $guest->id,
                    'user_id' => auth()->id(), // null jika guest booking tanpa login
                    'check_in_date' => $checkInDate,
                    'check_out_date' => $checkOutDate,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'is_incognito' => $request->boolean('is_incognito'),
                ]);
            });

            if (!$booking) {
                throw new \Exception('Gagal membuat record booking di database.');
            }

            // 6. Generate Midtrans Snap Token
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
            Log::error('Booking Store Error: ' . $e->getMessage());

            // Mengembalikan pesan error yang ramah pengguna
            // Jika error blacklist dari dalam transaction, akan tertangkap di sini juga
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
