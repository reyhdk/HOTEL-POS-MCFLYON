<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\Room;
use App\Models\Guest;
use App\Models\User; 
use App\Models\Setting;
use App\Models\CashFlow; // [TAMBAHAN] Import CashFlow
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Midtrans\Snap;
use Midtrans\Config;

class CheckInController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

    private function calculateEarlyCheckInFee()
    {
        $setting = Setting::first();
        if (!$setting || !$setting->early_check_in_fee || $setting->early_check_in_fee == 0) {
            return 0;
        }

        $now = Carbon::now('Asia/Jakarta');
        $checkInTimeSetting = $setting->check_in_time ?? '14:00';
        $feePerHour = $setting->early_check_in_fee;

        $standardCheckIn = Carbon::createFromFormat('H:i', substr($checkInTimeSetting, 0, 5), 'Asia/Jakarta')
            ->setDate($now->year, $now->month, $now->day);

        if ($now->gte($standardCheckIn)) {
            return 0;
        }

        $hoursEarly = $now->diffInHours($standardCheckIn);
        $minutesResidue = $now->diffInMinutes($standardCheckIn) % 60;

        if ($minutesResidue > 0) {
            $hoursEarly++;
        }

        return (int) ($hoursEarly * $feePerHour);
    }

    private function processCheckInPayment(Booking $booking, string $paymentMethod, bool $isWalkIn)
    {
        $earlyFee = $this->calculateEarlyCheckInFee();
        $roomPrice = $isWalkIn ? $booking->total_price : 0;
        $totalToPay = $roomPrice + $earlyFee;

        $snapToken = null;

        if ($totalToPay > 0) {
            $paymentStatus = ($paymentMethod === 'cash') ? 'paid' : 'unpaid';
            
            $booking->update([
                'early_check_in_fee' => $earlyFee,
                'early_payment_status' => $paymentStatus,
                'payment_status' => $isWalkIn ? $paymentStatus : $booking->payment_status
            ]);

            if ($paymentStatus === 'unpaid') {
                try {
                    $prefix = $isWalkIn ? 'WALKIN-' : 'EARLY-';
                    $midtransOrderId = $prefix . $booking->id . '-' . time();
                    
                    $itemDetails = [];
                    if ($isWalkIn) {
                        $itemDetails[] = [
                            'id' => 'ROOM-PAY',
                            'price' => (int) $roomPrice,
                            'quantity' => 1,
                            'name' => 'Pembayaran Kamar Walk-In'
                        ];
                    }
                    if ($earlyFee > 0) {
                        $itemDetails[] = [
                            'id' => 'EARLY-FEE',
                            'price' => (int) $earlyFee,
                            'quantity' => 1,
                            'name' => 'Biaya Early Check-In'
                        ];
                    }

                    $params = [
                        'transaction_details' => [
                            'order_id' => $midtransOrderId,
                            'gross_amount' => (int) $totalToPay,
                        ],
                        'customer_details' => [
                            'first_name' => $booking->guest->name ?? 'Guest',
                            'email' => $booking->guest->email ?? 'guest@example.com',
                        ],
                        'item_details' => $itemDetails
                    ];
                    
                    $snapToken = Snap::getSnapToken($params);
                    $booking->update(['midtrans_order_id' => $midtransOrderId]);
                } catch (\Exception $e) {
                    Log::error("Midtrans Snap Error pada Check-In: " . $e->getMessage());
                }
            }
        }

        return ['fee' => $earlyFee, 'snap_token' => $snapToken];
    }

    public function storeDirect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id'        => 'required|exists:rooms,id',
            'booking_id'     => 'nullable|exists:bookings,id',
            'guest_id'       => 'required_without_all:guest_name,booking_id|nullable|exists:guests,id',
            'guest_name'     => 'required_without_all:guest_id,booking_id|nullable|string',
            'check_out_date' => 'required_without:booking_id|nullable|date',
            'ktp_image'      => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $bookingId = $request->booking_id;
        $isWalkIn = empty($bookingId);
        $paymentMethod = $request->payment_method ?? 'cash';

        try {
            if (!$isWalkIn) {
                $bookingCheck = Booking::findOrFail($bookingId);
                $bookingDate = Carbon::parse($bookingCheck->check_in_date)->timezone('Asia/Jakarta')->startOfDay();
                $today = Carbon::today('Asia/Jakarta')->startOfDay();
                
                if ($bookingDate->gt($today)) {
                    return response()->json([
                        'message' => "Booking ini dijadwalkan untuk tanggal " . $bookingDate->format('d M Y') . ". Belum bisa check-in hari ini."
                    ], 422);
                }
            }

            $feeResult = ['fee' => 0, 'snap_token' => null];

            DB::transaction(function () use ($request, $bookingId, $isWalkIn, $paymentMethod, &$feeResult) {
                $room = Room::findOrFail($request->room_id);
                
                if (!$isWalkIn) {
                    $booking = Booking::with('guest')->findOrFail($bookingId);
                } else {
                    $checkInDate = Carbon::today('Asia/Jakarta');
                    $checkOutDate = Carbon::parse($request->check_out_date);
                    $nights = $checkInDate->diffInDays($checkOutDate) ?: 1;

                    if (!$request->guest_id && $request->guest_name) {
                        $guest = Guest::create([
                            'name' => $request->guest_name,
                            'phone_number' => $request->guest_phone,
                            'email' => $request->guest_email,
                            'is_verified' => false
                        ]);
                    } else {
                        $guest = Guest::findOrFail($request->guest_id);
                    }

                    $totalRoomPrice = $room->price_per_night * $nights;

                    $booking = Booking::create([
                        'room_id' => $room->id,
                        'guest_id' => $guest->id,
                        'check_in_date' => $checkInDate->format('Y-m-d'),
                        'check_out_date' => $checkOutDate->format('Y-m-d'),
                        'status' => 'pending', 
                        'total_price' => $totalRoomPrice,
                        'payment_status' => 'unpaid' 
                    ]);
                }

                if ($request->hasFile('ktp_image')) {
                    $guest = $booking->guest;
                    if ($guest && $guest->ktp_image) {
                        Storage::disk('public')->delete($guest->ktp_image);
                    }
                    $path = $request->file('ktp_image')->store('ktp_images', 'public');
                    
                    $guest->update([
                        'ktp_image' => $path,
                        'is_verified' => true
                    ]);
                }

                $linkedUser = User::where(function($q) use ($booking) {
                    $guest = $booking->guest;
                    if ($guest->email) $q->where('email', $guest->email);
                    if ($guest->phone_number) $q->orWhere('phone', $guest->phone_number);
                })->first();

                $feeResult = $this->processCheckInPayment($booking, $paymentMethod, $isWalkIn);
                
                if ($paymentMethod === 'cash') {
                    $booking->update([
                        'status' => 'checked_in', 
                        'check_in_time' => now()
                    ]);

                    $checkInData = [
                        'room_id'        => $room->id,
                        'guest_id'       => $booking->guest_id,
                        'booking_id'     => $booking->id,
                        'check_in_time'  => now(),
                        'is_active'      => true,
                        'is_incognito'   => $request->boolean('is_incognito', false),
                    ];

                    if ($linkedUser) {
                        $checkInData['user_id'] = $linkedUser->id; 
                    }

                    CheckIn::create($checkInData);
                    $room->update(['status' => 'occupied']);

                    // [TAMBAHAN] OTOMATIS CATAT CASH FLOW JIKA BAYAR TUNAI WALK-IN
                    $totalToPay = $feeResult['fee'] + ($isWalkIn ? $booking->total_price : 0);
                    if ($totalToPay > 0) {
                        CashFlow::create([
                            'transaction_date' => now(),
                            'type' => 'income',
                            'category' => 'booking',
                            'description' => $isWalkIn ? 'Pembayaran Kamar Walk-In (Tunai)' : 'Biaya Early Check-In (Tunai)',
                            'payment_method' => 'Cash',
                            'amount' => $totalToPay,
                            'reference_id' => 'WALKIN-' . $booking->id,
                            'user_id' => auth('api')->id() ?? 1
                        ]);
                    }
                    
                } else {
                    $booking->update([
                        'notes' => $request->boolean('is_incognito', false) ? 'INCOGNITO' : 'NORMAL'
                    ]);
                }
            });

            return response()->json([
                'message' => $paymentMethod === 'cash' ? 'Check-in berhasil diproses!' : 'Menunggu pembayaran melalui QRIS/Transfer.',
                'early_check_in_fee' => $feeResult['fee'],
                'snap_token' => $feeResult['snap_token']
            ]);

        } catch (\Exception $e) {
            Log::error("Gagal storeDirect: " . $e->getMessage());
            return response()->json([
                'message' => 'Gagal memproses check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeFromBooking(Request $request)
    {
        return $this->storeDirect($request);
    }

    public function checkOut(Request $request)
    {
        $roomId = $request->input('room_id');
        $room = Room::findOrFail($roomId);

        if ($room->status !== 'occupied') {
            return response()->json(['message' => 'Kamar tidak sedang dalam status Terisi.'], 409);
        }

        try {
            DB::transaction(function () use ($room) {
                $active = CheckIn::where('room_id', $room->id)
                    ->where('is_active', true)
                    ->latest()
                    ->first();

                if ($active) {
                    $active->update(['is_active' => false, 'check_out_time' => now()]);
                    if ($active->booking_id) {
                        Booking::where('id', $active->booking_id)->update(['status' => 'completed']);
                    }
                }
                $room->update(['status' => 'dirty']);
            });

            return response()->json(['message' => 'Check-out berhasil. Kamar kini berstatus Kotor.']);
        } catch (\Throwable $e) {
            Log::error("Gagal checkOut: " . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat memproses check-out.'], 500);
        }
    }
}