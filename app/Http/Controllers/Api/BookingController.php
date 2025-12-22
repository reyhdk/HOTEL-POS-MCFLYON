<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Midtrans\Snap;
use Midtrans\Config;
use Throwable;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

    /**
     * ✅ FIXED: Menampilkan daftar booking dengan Filter Lengkap
     * Mendukung filter untuk Kalender & Check-in Modal
     */
    public function index(Request $request)
    {
        $query = Booking::with(['guest', 'room'])
            ->orderBy('created_at', 'desc');

        // Filter by Room
        if ($request->has('room_id') && $request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // ✅ Filter by Multiple Status (status_in) - UNTUK KALENDER & CHECK-IN MODAL
        if ($request->has('status_in')) {
            $statuses = is_array($request->status_in)
                ? $request->status_in
                : explode(',', $request->status_in);

            $query->whereIn('status', $statuses);
        }

        // Filter by Single Status (backward compatibility)
        if ($request->has('status') && !$request->has('status_in')) {
            $query->where('status', $request->status);
        }

        // Filter by Status Verifikasi KTP (pending, verified, rejected)
        if ($request->has('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        // ✅ Filter by Date Range (date_from & date_to) - UNTUK KALENDER
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->where(function ($q) use ($request) {
                $q->whereBetween('check_in_date', [$request->date_from, $request->date_to])
                    ->orWhereBetween('check_out_date', [$request->date_from, $request->date_to])
                    ->orWhere(function ($sub) use ($request) {
                        $sub->where('check_in_date', '<=', $request->date_from)
                            ->where('check_out_date', '>=', $request->date_to);
                    });
            });
        }

        // ✅ Filter by Date Greater Than or Equal (date_gte) - UNTUK CHECK-IN MODAL
        if ($request->has('date_gte')) {
            $query->where('check_in_date', '>=', $request->date_gte);
        }

        // OLD: Filter by Date Range (backward compatibility)
        if ($request->has('start_date') && $request->has('end_date') && !$request->has('date_from')) {
            $query->where(function ($q) use ($request) {
                $q->whereBetween('check_in_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('check_out_date', [$request->start_date, $request->end_date]);
            });
        }

        $bookings = $query->get();

        return response()->json([
            'data' => $bookings,
            'meta' => [
                'total' => $bookings->count(),
                'filters_applied' => [
                    'room_id' => $request->room_id,
                    'status_in' => $request->status_in,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                    'date_gte' => $request->date_gte,
                ]
            ]
        ]);
    }

    /**
     * Membuat Booking Baru (Termasuk Upload KTP & Midtrans)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'ktp_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::beginTransaction();
        try {
            // Cek Ketersediaan Kamar
            $isAvailable = !Booking::where('room_id', $validated['room_id'])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'rejected')
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('check_in_date', [$validated['check_in_date'], $validated['check_out_date']])
                        ->orWhereBetween('check_out_date', [$validated['check_in_date'], $validated['check_out_date']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('check_in_date', '<=', $validated['check_in_date'])
                                ->where('check_out_date', '>=', $validated['check_out_date']);
                        });
                })->exists();

            if (!$isAvailable) {
                throw new \Exception('Kamar tidak tersedia pada tanggal yang dipilih (sudah terpesan).');
            }

            // Handle Guest
            $guest = Guest::updateOrCreate(
                ['email' => $validated['guest_email']],
                [
                    'name' => $validated['guest_name'],
                    'phone_number' => $validated['guest_phone']
                ]
            );

            // Handle Upload KTP
            $ktpPath = null;
            if ($request->hasFile('ktp_image')) {
                $ktpPath = $request->file('ktp_image')->store('booking_ktp', 'public');
            }

            // Hitung Harga Total
            $room = Room::findOrFail($validated['room_id']);
            $checkIn = Carbon::parse($validated['check_in_date']);
            $checkOut = Carbon::parse($validated['check_out_date']);
            $durationInNights = $checkIn->diffInDays($checkOut);
            if ($durationInNights < 1) $durationInNights = 1;

            $totalPrice = $room->price_per_night * $durationInNights;

            // Buat Record Booking
            $booking = Booking::create([
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'user_id' => auth('sanctum')->id() ?? null,
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'verification_status' => 'pending',
                'ktp_image' => $ktpPath,
                'is_incognito' => false,
            ]);

            // Generate Midtrans Snap Token
            $midtransOrderId = 'BOOK-' . $booking->id . '-' . time();
            $booking->midtrans_order_id = $midtransOrderId;
            $booking->save();

            $params = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => (int) $totalPrice,
                ],
                'customer_details' => [
                    'first_name' => $guest->name,
                    'email' => $guest->email,
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

            DB::commit();

            return response()->json([
                'message' => 'Booking berhasil dibuat. Mohon segera lakukan pembayaran.',
                'snap_token' => $snapToken,
                'booking_id' => $booking->id
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();

            if (isset($ktpPath) && Storage::disk('public')->exists($ktpPath)) {
                Storage::disk('public')->delete($ktpPath);
            }

            Log::error('Booking Store Error: ' . $e->getMessage());

            $statusCode = str_contains($e->getMessage(), 'sudah terpesan') ? 409 : 500;
            return response()->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    /**
     * Menampilkan detail booking
     */
    public function show($id)
    {
        $booking = Booking::with(['guest', 'room', 'user'])->findOrFail($id);
        return response()->json($booking);
    }

    /**
     * ADMIN: Verifikasi KTP (Terima)
     */
    public function verifyBooking($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status == 'cancelled') {
            return response()->json(['message' => 'Tidak bisa verifikasi. Booking sudah dibatalkan.'], 400);
        }

        $booking->update([
            'verification_status' => 'verified',
            'status' => 'confirmed' // ✅ Update status jadi confirmed
        ]);

        return response()->json(['message' => 'Identitas tamu berhasil diverifikasi.']);
    }

    /**
     * ADMIN: Tolak KTP & Batalkan Booking
     */
    public function rejectBooking(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->verification_status === 'rejected') {
            return response()->json(['message' => 'Booking ini sudah ditolak sebelumnya.'], 400);
        }

        DB::beginTransaction();
        try {
            $message = 'Booking ditolak.';

            if (in_array($booking->status, ['paid', 'settlement', 'captured'])) {
                $message = 'Booking ditolak. Status sudah LUNAS, silakan lakukan Refund manual kepada tamu.';
            } else {
                try {
                    \Midtrans\Transaction::cancel($booking->midtrans_order_id);
                } catch (\Exception $e) {
                    // Ignore
                }
            }

            $booking->update([
                'verification_status' => 'rejected',
                'status' => 'cancelled',
                'rejection_reason' => $request->reason
            ]);

            DB::commit();
            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses penolakan: ' . $e->getMessage()], 500);
        }
    }
}
