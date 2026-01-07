<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking; // Pastikan import Model Booking
use Illuminate\Http\Request;

class UserBookingController extends Controller
{
    public function index(Request $request)
    {
        // Ambil User ID dari token
        $userId = $request->user()->id;

        // Ambil booking milik user (ID 2)
        $bookings = Booking::with(['room', 'guest'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        // Format data agar cocok dengan Frontend Vue (image_url)
        $formattedBookings = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'check_in_date' => $booking->check_in_date,
                'check_out_date' => $booking->check_out_date,
                'total_price' => $booking->total_price,
                'status' => $booking->status,
                'room' => [
                    'room_number' => $booking->room->room_number ?? $booking->room->number ?? '-',
                    'type' => $booking->room->type ?? 'Standard',

                    // Logic agar image_url selalu valid (URL lengkap)
                    'image_url' => $booking->room->image
                        ? (str_starts_with($booking->room->image, 'http')
                            ? $booking->room->image
                            : asset('storage/' . $booking->room->image))
                        : null,
                ],
                'guest' => [
                    'name' => $booking->guest->name ?? 'Guest',
                ]
            ];
        });

        return response()->json($formattedBookings);
    }
}
