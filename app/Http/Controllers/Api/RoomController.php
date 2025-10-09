<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class RoomController extends Controller
{
    /**
     * Mengambil daftar semua kamar untuk panel admin (POS, Folio, dll).
     * INI ADALAH FUNGSI KUNCI UNTUK MASALAHMU.
     */
    public function index()
    {
        return Room::with([
            'checkIns' => function ($query) {
                $query->where('is_active', true)->with('guest');
            },
            'facilities',
            'serviceRequests' => function ($query) {
            $query->where('service_name', 'Pembersihan Kamar')->where('status', 'pending');
        }
        ])->latest()->get();
    }

    /**
     * Mengambil daftar kamar yang tersedia untuk publik dengan filter.
     */
    public function getAvailableRooms(Request $request)
    {
        $validated = $request->validate([
            'check_in_date' => 'nullable|date|after_or_equal:today',
            'check_out_date' => 'nullable|date|after_or_equal:check_in_date',
            'facility_ids' => 'nullable|array',
            'facility_ids.*' => 'exists:facilities,id',
            'type' => 'nullable|string|in:Standard,Deluxe,Suite'
        ]);

        $query = Room::with('facilities')->where('status', 'available');

        $query->when(!empty($validated['type']), function ($q) use ($validated) {
            $q->where('type', $validated['type']);
        });

        $query->when(!empty($validated['facility_ids']), function ($q) use ($validated) {
            foreach ($validated['facility_ids'] as $facilityId) {
                $q->whereHas('facilities', fn($subQuery) => $subQuery->where('facilities.id', $facilityId));
            }
        });

        return $query->latest()->get();
    }

    /**
     * Menampilkan detail satu kamar untuk ADMIN.
     */
    public function show(Room $room)
    {
        return $room->load('facilities');
    }

    /**
     * Menampilkan detail satu kamar untuk PUBLIK.
     */
    public function showPublic(Room $room)
    {
        if ($room->status !== 'available') {
            return response()->json(['message' => 'Kamar tidak ditemukan atau tidak tersedia.'], 404);
        }
        return $room->load('facilities');
    }

    /**
     * Menyimpan kamar baru ke database.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number',
            'type' => 'required|string',
            'status' => 'required|string',
            'price_per_night' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'facility_ids' => 'nullable|array',
            'facility_ids.*' => 'exists:facilities,id',
            'tersedia_mulai' => 'nullable|date',
            'tersedia_sampai' => 'nullable|date|after_or_equal:tersedia_mulai',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/rooms');
            $validatedData['image'] = $path;
        }

        $room = Room::create($validatedData);

        if ($request->has('facility_ids')) {
            $room->facilities()->attach($validatedData['facility_ids']);
        }

        return response()->json($room->load('facilities'), 201);
    }

    /**
     * Memperbarui data kamar.
     */
    public function update(Request $request, Room $room)
    {
        // app/Http/Controllers/Api/RoomController.php -> method store()

    $validatedData = $request->validate([
        'room_number' => 'required|string|unique:rooms,room_number,' . $room->id,
        'type' => 'required|string',
        'status' => 'required|string',
        'price_per_night' => 'required|numeric|min:0',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'facility_ids' => 'nullable|array',
        'facility_ids.*' => 'exists:facilities,id',
        'tersedia_mulai' => 'nullable|date',
        'tersedia_sampai' => 'nullable|date|after_or_equal:tersedia_mulai',
    ]);

        Log::info('Data yang divalidasi untuk diupdate:', $validatedData);


        if ($request->hasFile('image')) {
            if ($room->image) Storage::delete($room->image);
            $path = $request->file('image')->store('public/rooms');
            $validatedData['image'] = $path;
        }

        $room->update($validatedData);
        $room->facilities()->sync($request->input('facility_ids', []));

        return response()->json($room->load('facilities'));
    }

    /**
     * Menghapus kamar dari database.
     */
    public function destroy(Room $room)
    {
        if ($room->status === 'occupied') {
            return response()->json(['message' => 'Kamar ini tidak dapat dihapus karena sedang digunakan.'], 409);
        }

        if ($room->image) {
            Storage::delete($room->image);
        }

        $room->delete();

        return response()->json(null, 204);
    }

    /**
     * Menandai kamar perlu dibersihkan setelah check-out.
     */
    public function markForCleaning(Room $room)
    {
        if ($room->status !== 'occupied') { // Logika: Seharusnya dari 'occupied' setelah checkout
            return response()->json(['message' => 'Hanya kamar yang terisi yang bisa ditandai untuk dibersihkan setelah check-out.'], 409);
        }
        $room->update(['status' => 'needs cleaning']);
        return response()->json(['message' => 'Kamar telah ditandai untuk dibersihkan.']);
    }

    /**
     * Tamu meminta kamarnya dibersihkan.
     */
    public function requestCleaning(Request $request, Room $room) // Tambahkan Request
{
    if ($room->status !== 'occupied') {
        return response()->json(['message' => 'Hanya kamar yang sedang terisi yang bisa meminta pembersihan.'], 409);
    }

    try {
        DB::transaction(function () use ($room) {
            // 1. Ubah status kamar
            $room->update(['status' => 'request cleaning']);

            // 2. Buat record ServiceRequest agar sinkron
            \App\Models\ServiceRequest::create([
                'room_id' => $room->id,
                'user_id' => $room->checkIns()->where('is_active', true)->first()?->booking?->user_id, // Ambil user_id dari check-in aktif
                'service_name' => 'Pembersihan Kamar',
                'status' => 'pending',
                'quantity' => 1, // Default quantity
            ]);
        });

        return response()->json(['message' => 'Permintaan pembersihan kamar telah dicatat.']);
    } catch (\Throwable $e) {
        Log::error('Gagal membuat permintaan pembersihan manual: ' . $e->getMessage());
        return response()->json(['message' => 'Gagal membuat permintaan.'], 500);
    }
}

    /**
     * Menandai kamar sudah bersih (logika cerdas).
     */
    public function markAsClean(Room $room)
    {
        if (!in_array($room->status, ['needs cleaning', 'request cleaning'])) {
            return response()->json(['message' => 'Status kamar tidak valid untuk ditandai bersih.'], 409);
        }

        // Cek apakah masih ada sesi check-in aktif untuk kamar ini.
        // Ini untuk kasus di mana 'request cleaning' diproses saat tamu masih di dalam.
        $hasActiveCheckIn = $room->checkIns()->where('is_active', true)->exists();
        $newStatus = $hasActiveCheckIn ? 'occupied' : 'available';

        $room->update(['status' => $newStatus]);

        return response()->json(['message' => 'Kamar telah ditandai bersih dan status diperbarui menjadi ' . $newStatus . '.']);
    }
}
