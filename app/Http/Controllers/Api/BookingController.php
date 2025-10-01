<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookingController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
        if (app()->environment('local')) {
            Config::$curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
        }
    }

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

        $room = Room::findOrFail($validated['room_id']);
        $checkInDate = Carbon::parse($validated['check_in_date']);
        $checkOutDate = Carbon::parse($validated['check_out_date']);
        $booking = null;

        try {
            DB::transaction(function () use ($validated, $room, $checkInDate, $checkOutDate, &$booking) {
                $room = Room::where('id', $validated['room_id'])->lockForUpdate()->first();
                
                if ($room->status !== 'available') {
                    throw new \Exception('Maaf, kamar ini baru saja dipesan. Silakan pilih kamar lain.');
                }

                $guest = Guest::firstOrCreate(
                    ['email' => $validated['guest_email']],
                    ['name' => $validated['guest_name'], 'phone_number' => $validated['guest_phone']]
                );

                $room->status = 'booked';
                $room->save();

                $booking = Booking::create([
                    'room_id' => $room->id,
                    'guest_id' => $guest->id,
                    'user_id' => auth()->id(),
                    'check_in_date' => $checkInDate,
                    'check_out_date' => $checkOutDate,
                    'total_price' => $room->price_per_night * $checkOutDate->diffInDays($checkInDate),
                    'status' => 'pending',
                ]);
            });

            $midtransOrderId = 'BOOK-' . $booking->id . '-' . time();
            
            $params = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => (int) $booking->total_price,
                ],
                'customer_details' => [
                    'first_name' => $validated['guest_name'],
                    'email' => $validated['guest_email'],
                    'phone' => $validated['guest_phone'],
                ],
                'item_details' => [[
                    'id' => (string) $room->id,
                    'price' => (int) $booking->total_price,
                    'quantity' => 1,
                    'name' => substr('Booking Kamar ' . $room->room_number . ' (' . $checkInDate->format('d M') . ')', 0, 50),
                ]],
            ];

            $snapToken = Snap::getSnapToken($params);
            
            $booking->midtrans_order_id = $midtransOrderId;
            $booking->save();

            return response()->json(['snap_token' => $snapToken]);

        } catch (Throwable $e) {
            if ($booking) {
                $booking->room()->update(['status' => 'available']);
            }
            Log::error('Booking Store Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());

            return response()->json(
                ['message' => 'Gagal memproses booking: ' . $e->getMessage()],
                500,
                ['Access-Control-Allow-Origin' => '*']
            );
        }
    }
}