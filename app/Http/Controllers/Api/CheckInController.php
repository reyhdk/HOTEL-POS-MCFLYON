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
        // 1. Validasi Input
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_id' => 'required|exists:guests,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'payment_method' => 'required|string|in:cash,midtrans',
            'is_incognito'   => 'nullable|boolean', // Input manual dari resepsionis
        ]);

        $room = Room::findOrFail($validated['room_id']);

        // Logika: Kamar boleh 'occupied' jika kita check-in kan booking yang sudah confirmed (bukan tamu nyelonong)
        // Jadi kita hapus pengecekan $room->status !== 'available' yang kaku di sini, 
        // kita ganti dengan pengecekan di bawah.

        try {
            DB::transaction(function () use ($validated, $room, $request) {
                
                $checkInDate = Carbon::parse($validated['check_in_date']);
                $checkOutDate = Carbon::parse($validated['check_out_date']);
                $durationInNights = $checkOutDate->diffInDays($checkInDate);
                $totalPrice = $room->price_per_night * $durationInNights;

                // [LOGIKA BARU - PENTING]
                // Cek apakah ada Booking Online yang sudah 'confirmed' untuk kamar ini hari ini?
                $existingBooking = Booking::where('room_id', $room->id)
                    ->where('guest_id', $validated['guest_id']) // Pastikan tamunya sama
                    ->whereIn('status', ['confirmed', 'paid'])  // Status sudah bayar/konfirmasi
                    ->whereDate('check_in_date', $checkInDate->format('Y-m-d'))
                    ->first();

                // Tentukan Status Incognito
                // Prioritas: 1. Input Resepsionis (jika ada), 2. Data Booking Online, 3. False
                $isIncognito = $request->boolean('is_incognito');
                
                if ($existingBooking && $existingBooking->is_incognito) {
                    $isIncognito = true; // Warisi status incognito dari online booking
                }

                if ($existingBooking) {
                    // SKENARIO A: CHECK-IN DARI BOOKING ONLINE
                    $booking = $existingBooking;
                    
                    // Update status booking biar rapi
                    $booking->update([
                        'status' => 'checked_in', // Atau biarkan 'confirmed' tergantung flow Anda
                        // Jika resepsionis mengubah tanggal checkout saat kedatangan, update juga:
                        'check_out_date' => $checkOutDate, 
                        'total_price' => $totalPrice
                    ]);

                } else {
                    // SKENARIO B: WALK-IN (Tamu Datang Langsung)
                    // Cek ketersediaan fisik kamar
                    if ($room->status !== 'available') {
                        throw new \Exception('Kamar ini sedang terisi dan tidak memiliki jadwal booking untuk tamu ini.');
                    }

                    $booking = Booking::create([
                        'room_id' => $room->id,
                        'user_id' => $request->user() ? $request->user()->id : null,
                        'guest_id' => $validated['guest_id'],
                        'check_in_date' => $checkInDate,
                        'check_out_date' => $checkOutDate,
                        'total_price' => $totalPrice,
                        'status' => 'checked_in', // Langsung masuk
                        'is_incognito' => $isIncognito, // Simpan status
                    ]);
                }

                // 3. Create CheckIn Record (Sesi Masuk Kamar)
                CheckIn::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'guest_id' => $validated['guest_id'],
                    'check_in_time' => now(),
                    'is_active' => true,
                    'is_incognito' => $isIncognito, // <--- INI KUNCINYA
                ]);

                // 4. Update Status Kamar Fisik
                $room->update(['status' => 'occupied']);
            });

            return response()->json(['message' => 'Check-in berhasil diproses.']);

        } catch (\Throwable $e) {
            Log::error('Gagal check-in: ' . $e->getMessage());
            // Kembalikan pesan error yang ramah user
            return response()->json(['message' => $e->getMessage()], 409); 
        }
    }

    /**
     * Proses Check-out Tamu & Pelunasan Folio Otomatis
     */
    public function checkout(Request $request, Room $room)
    {
        // Default metode bayar checkout adalah 'cash' jika admin tidak memilih
        $paymentMethod = $request->input('payment_method', 'cash');

        if ($room->status !== 'occupied') {
            return response()->json(['message' => 'Kamar ini tidak sedang ditempati.'], 409);
        }

        try {
            DB::transaction(function () use ($room, $paymentMethod) {
                // 1. Ambil data CheckIn aktif
                $activeCheckIn = CheckIn::where('room_id', $room->id)
                                        ->where('is_active', true)
                                        ->first();

                if ($activeCheckIn) {
                    // 2. [KRUSIAL] Lunasi semua Order 'titipan' tamu ini
                    // Cari order yang statusnya belum 'paid' & belum 'cancelled'
                    \App\Models\Order::where('room_id', $room->id)
                        ->where('guest_id', $activeCheckIn->guest_id)
                        ->whereNotIn('status', ['paid', 'cancelled']) 
                        ->update([
                            'status' => 'paid',            // Ubah jadi Paid agar masuk Riwayat
                            'payment_method' => $paymentMethod, // Ikut metode bayar checkout
                            'updated_at' => now()
                        ]);

                    // 3. Selesaikan CheckIn
                    $activeCheckIn->update([
                        'is_active' => false,
                        'check_out_time' => now(),
                    ]);
                }

                // 4. Tandai kamar kotor
                $room->update(['status' => 'dirty']);
            });

            return response()->json(['message' => 'Check-out berhasil. Semua tagihan folio telah dilunasi dan masuk Riwayat Transaksi.']);

        } catch (Throwable $e) {
            Log::error('Gagal checkout: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat checkout.'], 500);
        }
    }
}