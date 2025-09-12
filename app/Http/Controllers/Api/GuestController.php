<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index()
    {
        return Guest::latest()->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:guests,email',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $guest = Guest::create($request->all());
        return response()->json($guest, 201);
    }

    public function show(Guest $guest)
    {
        return $guest;
    }

    public function update(Request $request, Guest $guest)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:guests,email,' . $guest->id,
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $guest->update($request->all());
        return response()->json($guest);
    }

public function destroy(Guest $guest)
{
    if ($guest->bookings()->exists() || $guest->checkIns()->exists()) {
        
        return response()->json([
            'message' => 'Tamu ini tidak dapat dihapus karena memiliki riwayat booking atau check-in.'
        ], 409); 
    }
    $guest->delete();
    
    return response()->json(null, 204);
}
}