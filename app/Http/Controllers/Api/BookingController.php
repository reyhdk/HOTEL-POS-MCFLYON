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
use Midtrans\Config;
use Throwable;

class BookingController extends Controller
{
    public function __construct()
    {
        // Konfigurasi Midtrans (Opsional jika ingin di-init di sini)
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        \Midtrans\Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

    // app/Http/Controllers/Api/BookingController.php

    public function index(Request $request)
    {
        // Start Query
        $query = Booking::with(['guest', 'room'])
            ->orderBy('check_in_date', 'asc');

        // 1. Filter Room ID (Wajib untuk Modal Jadwal)
        if ($request->has('room_id') && $request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // 2. Filter Status
        if ($request->has('status_in')) {
            $statuses = explode(',', $request->status_in);
            $query->whereIn('status', $statuses);
        } elseif ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter Tanggal (LOGIKA BARU - LEBIH KUAT)
        if ($request->has('date_from') && $request->has('date_to')) {
            // Parsing tanggal agar aman (abaikan jam/menit)
            $from = Carbon::parse($request->date_from)->startOfDay();
            $to   = Carbon::parse($request->date_to)->endOfDay();

            /* LOGIKA OVERLAP / IRISAN JADWAL:
           Booking dianggap tampil di kalender bulan ini jika:
           1. Tanggal Masuk Booking <= Tanggal Akhir Kalender
           2. DAN Tanggal Keluar Booking >= Tanggal Awal Kalender
        */
            $query->where(function ($q) use ($from, $to) {
                $q->whereDate('check_in_date', '<=', $to)
                    ->whereDate('check_out_date', '>=', $from);
            });
        }
        // Fallback untuk CheckInModal (Hanya lihat yg check-in hari ini/masa depan)
        elseif ($request->has('date_gte')) {
            $query->whereDate('check_in_date', '>=', $request->date_gte);
        }

        // 4. Search Teks
        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('midtrans_order_id', 'like', "%{$search}%")
                    ->orWhereHas('guest', function ($guest) use ($search) {
                        $guest->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $bookings = $query->get();

        return response()->json(['data' => $bookings]);
    }
    /**
     * Menyimpan data booking baru dan membuat transaksi Midtrans.
     * (Logika asli Anda dipertahankan)
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
        // LOGIKA CEK BLACKLIST
        // =========================================================================
        $blacklistedGuest = Guest::where('is_blacklisted', true)
            ->where(function ($query) use ($validated) {
                $query->where('email', $validated['guest_email'])
                    ->orWhere('phone_number', $validated['guest_phone']);
            })
            ->first();

        if ($blacklistedGuest) {
            $reason = $blacklistedGuest->blacklist_reason ? " (Alasan: {$blacklistedGuest->blacklist_reason})" : "";
            return response()->json([
                'message' => 'Maaf, permintaan booking Anda ditolak karena identitas Anda masuk dalam daftar Blacklist hotel kami.' . $reason
            ], 403);
        }

        // 2. Kalkulasi Harga & Durasi
        $room = Room::findOrFail($validated['room_id']);

        $checkInDate = Carbon::parse($validated['check_in_date']);
        $checkOutDate = Carbon::parse($validated['check_out_date']);

        $durationInNights = $checkOutDate->diffInDays($checkInDate);
        if ($durationInNights < 1) $durationInNights = 1;

        $totalPrice = $room->price_per_night * $durationInNights;
        $booking = null;

        try {
            DB::transaction(function () use ($validated, $checkInDate, $checkOutDate, $totalPrice, &$booking, $request) {

                $lockedRoom = Room::where('id', $validated['room_id'])->lockForUpdate()->first();

                // Cek Bentrok Tanggal
                $isBooked = Booking::where('room_id', $lockedRoom->id)
                    ->whereIn('status', ['confirmed', 'paid', 'checked_in', 'settlement']) 
                    ->where(function ($query) use ($checkInDate, $checkOutDate) {
                        $query->where('check_in_date', '<', $checkOutDate)
                            ->where('check_out_date', '>', $checkInDate);
                    })
                    ->exists();

                if ($isBooked) {
                    throw new \Exception('Maaf, kamar ini sudah terpesan untuk tanggal yang Anda pilih.');
                }

                if ($lockedRoom->status === 'maintenance') {
                    throw new \Exception('Maaf, kamar ini sedang dalam perbaikan dan tidak dapat dipesan.');
                }

                // 4. Cari atau Buat Data Tamu
                $guest = Guest::firstOrCreate(
                    ['email' => $validated['guest_email']],
                    ['name' => $validated['guest_name'], 'phone_number' => $validated['guest_phone']]
                );

                if ($guest->is_blacklisted) {
                    throw new \Exception('Identitas tamu terdeteksi sebagai Blacklist.');
                }


                $booking = Booking::create([
                    'room_id' => $lockedRoom->id,
                    'guest_id' => $guest->id,
                    'user_id' => auth()->id() ?? null,
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
                'item_details' => [[
                    'id' => 'ROOM-' . $room->id,
                    'price' => (int) $room->price_per_night,
                    'quantity' => $durationInNights,
                    'name' => 'Sewa Kamar ' . $room->number
                ]]
            ];

            $snapToken = Snap::getSnapToken($params);

            return response()->json([
                'snap_token' => $snapToken,
                'booking_id' => $booking->id
            ]);
        } catch (Throwable $e) {
            Log::error('Booking Store Error: ' . $e->getMessage());
            $statusCode = str_contains($e->getMessage(), 'sudah terpesan') ? 409 : 500;
            return response()->json(['message' => $e->getMessage()], $statusCode);
        }
    }
}
