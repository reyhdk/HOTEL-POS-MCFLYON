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
     * [TAMBAHKAN INI]
     * Menerapkan otorisasi berbasis permission secara otomatis.
     */
    public function __construct()
    {
        $this->authorizeResource(Room::class, 'room');
    }


    /**
     * ✅ FIXED: Mengambil daftar semua kamar untuk panel admin (POS, Folio, dll).
     * MENAMBAHKAN EAGER LOAD 'booking' AGAR is_incognito DARI BOOKING ONLINE TERDETEKSI.
     */
    public function index()
    {
        return Room::with([
            'checkIns' => function ($query) {
                // ✅ PERBAIKAN UTAMA: Tambahkan 'booking' di sini
                $query->where('is_active', true)
                      ->with(['guest', 'booking']); // Load booking juga!
            },
            'facilities',
            'serviceRequests' => function ($query) {
                $query->where('service_name', 'Pembersihan Kamar')
                      ->where('status', 'pending');
            }
        ])->latest()->get();
    }

    /**
     * Mengambil daftar kamar yang tersedia untuk publik dengan filter.
     */
    public function getAvailableRooms(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        $checkIn = $request->check_in_date;
        $checkOut = $request->check_out_date;

        // Filter Opsional
        $type = $request->query('type');
        $facilityIds = $request->query('facility_ids');

        // 2. Query Pencarian
        $query = Room::query();

        // -----------------------------------------------------------
        // [BARU] LOGIKA PERIODE KETERSEDIAAN (Tersedia Mulai - Sampai)
        // -----------------------------------------------------------
        // Logika: 
        // Jika kamar punya tanggal 'tersedia_mulai', maka:
        // 1. Tanggal Check-in tamu harus >= tersedia_mulai (Kamar sudah buka)
        // 2. Tanggal Check-out tamu harus <= tersedia_sampai (Kamar belum tutup)
        // Jika kolom ini NULL, berarti kamar tersedia selamanya.

        $query->where(function ($q) use ($checkIn, $checkOut) {
            $q->where(function ($sub) use ($checkIn, $checkOut) {
                // Kasus A: Kamar punya batas waktu (Specific Period)
                $sub->whereNotNull('tersedia_mulai')
                    ->whereNotNull('tersedia_sampai')
                    ->whereDate('tersedia_mulai', '<=', $checkIn)  // Kamar buka sblm/pas check-in
                    ->whereDate('tersedia_sampai', '>=', $checkOut); // Kamar tutup stlh/pas check-out
            })
                ->orWhere(function ($sub) {
                    // Kasus B: Kamar tidak punya batas waktu (Always Available)
                    $sub->whereNull('tersedia_mulai')
                        ->orWhereNull('tersedia_sampai');
                });
        });

        // -----------------------------------------------------------
        // LOGIKA ANTI-BENTROK (BOOKING OVERLAP)
        // -----------------------------------------------------------
        // Cari kamar yang TIDAK punya booking CONFIRMED/PAID di tanggal tersebut.
        $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
            $q->whereIn('status', ['confirmed', 'paid', 'checked_in'])
                ->where(function ($subQ) use ($checkIn, $checkOut) {
                    // Logika Overlap: (StartA < EndB) && (EndA > StartB)
                    $subQ->where('check_in_date', '<', $checkOut)
                        ->where('check_out_date', '>', $checkIn);
                });
        });

        // Filter Status Fisik
        // Gunakan strtolower atau pastikan database konsisten. 
        // Kita filter maintenance saja, supaya status 'dirty' (karena tamu checkout pagi) tetap bisa dibooking untuk nanti sore/besok.
        $query->where('status', '!=', 'maintenance');

        // Filter Tipe Kamar
        if ($type) {
            $query->where('type', $type);
        }

        // Filter Fasilitas
        if (!empty($facilityIds)) {
            $query->whereHas('facilities', function ($q) use ($facilityIds) {
                $q->whereIn('facilities.id', $facilityIds);
            });
        }

        $rooms = $query->with('facilities')->get();

        return response()->json($rooms);
    }

    /**
     * [BARU] Mengambil daftar kamar yang sedang ditempati untuk halaman Point of Sale.
     */
    public function getOccupiedRoomsForPos()
    {
        $this->authorize('create', \App\Models\Order::class);

        $occupiedRooms = Room::where('status', 'occupied')
                             ->with(['checkIns' => function ($query) {
                                 // ✅ Tambahkan booking di sini juga untuk konsistensi
                                 $query->where('is_active', true)
                                       ->with(['guest', 'booking']);
                             }])
                             ->get();

        return response()->json($occupiedRooms);
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
        if ($room->status !== 'occupied') {
            return response()->json(['message' => 'Hanya kamar yang terisi yang bisa ditandai untuk dibersihkan setelah check-out.'], 409);
        }
        $room->update(['status' => 'needs cleaning']);
        return response()->json(['message' => 'Kamar telah ditandai untuk dibersihkan.']);
    }

    /**
     * Tamu meminta kamarnya dibersihkan.
     */
    public function requestCleaning(Request $request, Room $room)
    {
        if ($room->status !== 'occupied') {
            return response()->json(['message' => 'Hanya kamar yang sedang terisi yang bisa meminta pembersihan.'], 409);
        }

        try {
            DB::transaction(function () use ($room) {
                $room->update(['status' => 'request cleaning']);

                \App\Models\ServiceRequest::create([
                    'room_id' => $room->id,
                    'user_id' => $room->checkIns()->where('is_active', true)->first()?->booking?->user_id,
                    'service_name' => 'Pembersihan Kamar',
                    'status' => 'pending',
                    'quantity' => 1,
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
        if (!in_array($room->status, ['needs cleaning', 'request cleaning', 'dirty'])) {
            return response()->json(['message' => 'Status kamar tidak valid untuk ditandai bersih.'], 409);
        }

        $hasActiveCheckIn = $room->checkIns()->where('is_active', true)->exists();
        $newStatus = $hasActiveCheckIn ? 'occupied' : 'available';
        $room->update(['status' => $newStatus]);

        return response()->json(['message' => 'Kamar telah ditandai bersih dan status diperbarui menjadi ' . $newStatus . '.']);
    }
}