<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; 

class BookingController extends Controller
{
    /**
     * Menyimpan booking baru, membuat data tamu, membuat catatan check-in, 
     * dan mengubah status kamar dalam satu proses.
     */
    public function store(Request $request)
    {
        // --- Validasi Input ---
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        $room = Room::findOrFail($validated['room_id']);
        $checkInDate = Carbon::parse($validated['check_in_date']);
        $checkOutDate = Carbon::parse($validated['check_out_date']);

        // --- Logika Inti Menggunakan Transaksi Database ---
        try {
            DB::beginTransaction();

            // 1. Cek Ketersediaan Kamar Sekali Lagi
            if ($room->status !== 'available') {
                throw new \Exception('Maaf, kamar ini baru saja dipesan. Silakan pilih kamar lain.');
            }

            // 2. Buat atau Cari Data Tamu
            $guest = Guest::firstOrCreate(
                ['email' => $validated['guest_email']],
                [
                    'name' => $validated['guest_name'],
                    'phone_number' => $validated['guest_phone'],
                ]
            );

            // 3. Buat Catatan Booking Baru
            $booking = Booking::create([    
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'user_id' => Auth::id(), // [DIUBAH] Tambahkan ID pengguna yang login
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'total_price' => $room->price_per_night * $checkOutDate->diffInDays($checkInDate),
                'status' => 'confirmed', 
            ]);

            // 4. Buat Catatan Check-in yang Aktif
            CheckIn::create([
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'booking_id' => $booking->id,
                'check_in_time' => now(),
                'is_active' => true,
            ]);

            // 5. Ubah Status Kamar menjadi 'Occupied'
            $room->status = 'occupied';
            $room->save();

            DB::commit();

            return response()->json([
                'message' => 'Booking Anda telah berhasil dikonfirmasi!', 
                'booking' => $booking
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }
}