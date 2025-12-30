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
     * ✅ Menampilkan daftar booking dengan Filter Lengkap
     */
    public function index(Request $request)
    {
        $query = Booking::with(['guest', 'room'])
            ->orderBy('created_at', 'desc');

        if ($request->has('room_id') && $request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Filter by Multiple Status (untuk Kalender & Check-in Modal)
        if ($request->has('status_in')) {
            $statuses = is_array($request->status_in)
                ? $request->status_in
                : explode(',', $request->status_in);

            $query->whereIn('status', $statuses);
        }

        if ($request->has('status') && !$request->has('status_in')) {
            $query->where('status', $request->status);
        }

        if ($request->has('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        // Filter by Date Range (untuk Kalender)
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

        // Filter by Date Greater Than or Equal (untuk Check-in Modal)
        if ($request->has('date_gte')) {
            $query->where('check_in_date', '>=', $request->date_gte);
        }

        // OLD: Backward compatibility
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
     * Membuat Booking Baru (Upload KTP & Midtrans)
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
            $checkInReq = $validated['check_in_date'];
            $checkOutReq = $validated['check_out_date'];

            $isAvailable = !Booking::where('room_id', $validated['room_id'])
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->where(function ($query) use ($checkInReq, $checkOutReq) {
                    $query->where('check_in_date', '<', $checkOutReq)
                        ->where('check_out_date', '>', $checkInReq);
                })->exists();

            if (!$isAvailable) {
                throw new \Exception('Kamar tidak tersedia pada tanggal yang dipilih (sudah terpesan).');
            }

            $guest = Guest::updateOrCreate(
                ['email' => $validated['guest_email']],
                [
                    'name' => $validated['guest_name'],
                    'phone_number' => $validated['guest_phone']
                ]
            );

            $ktpPath = null;
            if ($request->hasFile('ktp_image')) {
                $ktpPath = $request->file('ktp_image')->store('booking_ktp', 'public');
            }

            $room = Room::findOrFail($validated['room_id']);
            $checkIn = Carbon::parse($validated['check_in_date']);
            $checkOut = Carbon::parse($validated['check_out_date']);
            $durationInNights = $checkIn->diffInDays($checkOut);
            if ($durationInNights < 1) $durationInNights = 1;

            $totalPrice = $room->price_per_night * $durationInNights;

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
     * ✅ ADMIN: Verifikasi KTP (Terima)
     */
    public function verifyBooking($id)
    {
        $booking = Booking::with('guest')->findOrFail($id);

        if ($booking->status == 'cancelled') {
            return response()->json(['message' => 'Tidak bisa verifikasi. Booking sudah dibatalkan.'], 400);
        }

        if ($booking->verification_status === 'verified') {
            return response()->json(['message' => 'Booking ini sudah diverifikasi sebelumnya.'], 400);
        }

        try {
            DB::beginTransaction();

            $booking->update([
                'verification_status' => 'verified',
                'status' => 'confirmed'
            ]);

            if ($booking->guest && !$booking->guest->is_verified) {
                if ($booking->ktp_image) {
                    $booking->guest->update([
                        'ktp_image' => $booking->ktp_image,
                        'is_verified' => true
                    ]);
                }
            }

            DB::commit();

            Log::info("✅ Booking {$booking->id} verified & confirmed");

            return response()->json([
                'message' => 'Booking berhasil diverifikasi dan dikonfirmasi.',
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Booking Verify Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal memverifikasi booking.'], 500);
        }
    }

    /**
     * ✅ FIX UTAMA: Tolak KTP & Batalkan Booking dengan Refund
     */
    public function rejectBooking(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $booking = Booking::with('guest')->findOrFail($id);

        if ($booking->verification_status === 'rejected') {
            return response()->json(['message' => 'Booking ini sudah ditolak sebelumnya.'], 400);
        }

        DB::beginTransaction();
        try {
            $needsManualRefund = false;
            $refundInfo = null;

            // Hapus foto KTP dari booking
            if ($booking->ktp_image && Storage::disk('public')->exists($booking->ktp_image)) {
                Storage::disk('public')->delete($booking->ktp_image);
                Log::info("🗑️ Deleted Booking KTP: {$booking->ktp_image}");
            }

            // Hapus foto KTP dari guest profile jika ada
            if ($booking->guest && $booking->guest->ktp_image) {
                if (Storage::disk('public')->exists($booking->guest->ktp_image)) {
                    Storage::disk('public')->delete($booking->guest->ktp_image);
                    Log::info("🗑️ Deleted Guest KTP: {$booking->guest->ktp_image}");
                }
                $booking->guest->update([
                    'ktp_image' => null,
                    'is_verified' => false
                ]);
            }

            // 🔥 PERBAIKAN UTAMA: Update status SEBELUM cek refund
            $booking->update([
                'ktp_image' => null,
                'verification_status' => 'rejected',
                'status' => 'cancelled',
                'rejection_reason' => $request->reason,
                'rejected_at' => now() // Opsional: tambahkan timestamp
            ]);

            Log::info("❌ Booking {$booking->id} status updated to cancelled/rejected");

            // Coba Refund jika sudah bayar
            $paidStatuses = ['paid', 'settlement', 'confirmed'];
            if (in_array($booking->status, $paidStatuses) && $booking->midtrans_order_id) {
                try {
                    \Midtrans\Transaction::refund($booking->midtrans_order_id, [
                        'refund_key' => 'reject-' . $booking->id . '-' . time(),
                        'amount' => (int) $booking->total_price,
                        'reason' => 'Verifikasi KTP ditolak: ' . $request->reason
                    ]);

                    Log::info("💰 Auto Refund Success: Booking {$booking->id}");
                    $refundInfo = 'Auto refund berhasil diproses.';
                } catch (\Exception $refundError) {
                    Log::warning("⚠️ Auto Refund Failed: " . $refundError->getMessage());
                    $needsManualRefund = true;
                    $refundInfo = [
                        'booking_id' => $booking->id,
                        'amount' => $booking->total_price,
                        'guest_name' => $booking->guest->name,
                        'order_id' => $booking->midtrans_order_id
                    ];
                }
            }

            DB::commit();

            $message = "Booking #{$booking->id} ditolak dan dibatalkan.";

            if ($needsManualRefund) {
                $message .= " ⚠️ Perlu REFUND MANUAL sebesar Rp " . number_format($booking->total_price, 0, ',', '.');
            } elseif ($refundInfo && is_string($refundInfo)) {
                $message .= " " . $refundInfo;
            }

            Log::info("✅ Booking {$booking->id} rejected successfully. Reason: {$request->reason}");

            return response()->json([
                'message' => $message,
                'success' => true,
                'needs_manual_refund' => $needsManualRefund,
                'refund_info' => $needsManualRefund ? $refundInfo : null,
                'booking' => $booking->fresh() // ✅ Return data terbaru
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Booking Reject Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Gagal menolak booking: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
