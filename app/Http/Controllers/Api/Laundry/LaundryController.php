<?php

namespace App\Http\Controllers\Api\Laundry;

use App\Http\Controllers\Controller;
use App\Models\LaundryService;
use App\Models\LaundryOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LaundryController extends Controller
{
    /**
     * Guest melihat daftar layanan laundry
     */
    public function getServices()
    {
        $services = LaundryService::where('is_active', true)->orderBy('name', 'asc')->get();
        return response()->json(['success' => true, 'data' => $services]);
    }

    /**
     * Guest melakukan request laundry dari aplikasi kamar
     */
    public function requestLaundry(Request $request)
    {
        $request->validate([
            'room_number' => 'required|string',
        ]);

        $order = LaundryOrder::create([
            'order_number' => 'REQ-' . strtoupper(Str::random(6)),
            'user_id' => auth()->id(),
            'room_number' => $request->room_number,
            'status' => 'requested',
            'total_price' => 0 // Harga belum ditentukan sampai petugas menimbang
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Permintaan laundry berhasil dikirim. Petugas akan segera mengambil cucian ke kamar Anda.', 
            'data' => $order
        ], 201);
    }

    /**
     * Guest melihat status laundry miliknya sendiri
     */
    public function myRequests()
    {
        $orders = LaundryOrder::with('items.service')
                    ->where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json(['success' => true, 'data' => $orders]);
    }
}