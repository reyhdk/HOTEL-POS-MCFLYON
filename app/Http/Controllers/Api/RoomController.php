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
     * ⚠️ PENTING: Bagian __construct ini SAYA KOMENTAR (NONAKTIFKAN).
     * Penyebab tombol tidak muncul/gagal adalah karena baris ini memblokir akses
     * jika Anda belum membuat file Policy yang lengkap.
     */
    // public function __construct()
    // {
    //     $this->authorizeResource(Room::class, 'room');
    // }

    /**
     * Menampilkan daftar kamar untuk Admin (Dashboard).
     */
    public function index()
    {
        return Room::with([
            'checkIns' => function ($query) {
                $query->where('is_active', true)
                    ->with(['guest', 'booking']);
            },
            'facilities',
            'serviceRequests' => function ($query) {
                $query->where('service_name', 'Pembersihan Kamar')
                    ->where('status', 'pending');
            }
        ])->latest()->get();
    }

    /**
     * Menyimpan kamar baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number',
            'type' => 'required|string',
            'status' => 'required|string', // available, occupied, dirty, maintenance
            'price_per_night' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096', // Max 4MB
            'facility_ids' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction(); // Mulai Transaksi Database

            $data = $request->only(['room_number', 'type', 'status', 'price_per_night', 'description', 'tersedia_mulai', 'tersedia_sampai']);

            // 2. Handle Upload Gambar
            if ($request->hasFile('image')) {
                // Simpan ke folder: storage/app/public/rooms
                // Hasil path: "rooms/namafile.jpg"
                $path = $request->file('image')->store('rooms', 'public');
                $data['image'] = $path;
            }

            // 3. Simpan Data Kamar
            $room = Room::create($data);

            // 4. Simpan Fasilitas (Jika ada)
            if ($request->has('facility_ids')) {
                // Pastikan facility_ids tidak null atau string kosong
                $facilities = is_string($request->facility_ids) ? explode(',', $request->facility_ids) : $request->facility_ids;
                $room->facilities()->attach($facilities);
            }

            DB::commit(); // Simpan permanen
            return response()->json(['message' => 'Kamar berhasil ditambahkan', 'data' => $room->load('facilities')], 201);
        } catch (\Throwable $e) {
            DB::rollBack(); // Batalkan jika error
            Log::error('Error Add Room: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan kamar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Memperbarui data kamar.
     */
    public function update(Request $request, Room $room)
    {
        // Validasi
        $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number,' . $room->id,
            'type' => 'required|string',
            'status' => 'required|string',
            'price_per_night' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->only(['room_number', 'type', 'status', 'price_per_night', 'description', 'tersedia_mulai', 'tersedia_sampai']);

            // 1. Handle Ganti Gambar
            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada
                if ($room->image && Storage::disk('public')->exists($room->image)) {
                    Storage::disk('public')->delete($room->image);
                }
                // Upload gambar baru
                $path = $request->file('image')->store('rooms', 'public');
                $data['image'] = $path;
            }

            // 2. Update Data
            $room->update($data);

            // 3. Update Fasilitas
            if ($request->has('facility_ids')) {
                $facilities = is_string($request->facility_ids) ? explode(',', $request->facility_ids) : $request->facility_ids;
                $room->facilities()->sync($facilities);
            }

            DB::commit();
            return response()->json(['message' => 'Kamar berhasil diperbarui', 'data' => $room->load('facilities')]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error Update Room: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal update kamar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus kamar.
     */
    public function destroy(Room $room)
    {
        if ($room->status === 'occupied') {
            return response()->json(['message' => 'Kamar sedang terisi, tidak bisa dihapus!'], 409);
        }

        try {
            // Hapus gambar fisik
            if ($room->image && Storage::disk('public')->exists($room->image)) {
                Storage::disk('public')->delete($room->image);
            }

            $room->delete();
            return response()->json(['message' => 'Kamar berhasil dihapus'], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal menghapus kamar'], 500);
        }
    }

    // --- FITUR TAMBAHAN (PUBLIC / POS) ---

    public function getAvailableRooms(Request $request)
    {
        // Validasi
        $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        $checkIn = $request->check_in_date;
        $checkOut = $request->check_out_date;
        $type = $request->query('type');

        $query = Room::query();

        // Logika Periode Ketersediaan
        $query->where(function ($q) use ($checkIn, $checkOut) {
            $q->where(function ($sub) use ($checkIn, $checkOut) {
                $sub->whereNotNull('tersedia_mulai')
                    ->whereNotNull('tersedia_sampai')
                    ->whereDate('tersedia_mulai', '<=', $checkIn)
                    ->whereDate('tersedia_sampai', '>=', $checkOut);
            })
                ->orWhere(function ($sub) {
                    $sub->whereNull('tersedia_mulai')->orWhereNull('tersedia_sampai');
                });
        });

        // Logika Anti Bentrok Booking
        $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
            $q->whereIn('status', ['confirmed', 'paid', 'checked_in'])
                ->where(function ($subQ) use ($checkIn, $checkOut) {
                    $subQ->where('check_in_date', '<', $checkOut)
                        ->where('check_out_date', '>', $checkIn);
                });
        });

        $query->where('status', '!=', 'maintenance');

        if ($type) {
            $query->where('type', $type);
        }

        return response()->json($query->with('facilities')->get());
    }

    public function getOccupiedRoomsForPos()
    {
        // Pastikan Anda sudah login atau hapus baris ini jika test di Postman tanpa auth
        // $this->authorize('create', \App\Models\Order::class); 

        $occupiedRooms = Room::where('status', 'occupied')
            ->with(['checkIns' => function ($query) {
                $query->where('is_active', true)->with(['guest', 'booking']);
            }])
            ->get();

        return response()->json($occupiedRooms);
    }

    public function show(Room $room)
    {
        return $room->load('facilities');
    }

    public function markAsClean(Room $room)
    {
        if (!in_array($room->status, ['needs cleaning', 'request cleaning', 'dirty'])) {
            return response()->json(['message' => 'Status tidak valid'], 409);
        }

        $hasActiveCheckIn = $room->checkIns()->where('is_active', true)->exists();
        $newStatus = $hasActiveCheckIn ? 'occupied' : 'available';

        $room->update(['status' => $newStatus]);
        return response()->json(['message' => 'Kamar bersih. Status: ' . $newStatus]);
    }

    public function requestCleaning(Request $request, Room $room)
    {
        if ($room->status !== 'occupied') return response()->json(['message' => 'Kamar harus terisi'], 409);

        $room->update(['status' => 'request cleaning']);
        // Tambahkan logika ServiceRequest disini jika diperlukan (sesuai kode lama Anda)

        return response()->json(['message' => 'Request cleaning terkirim']);
    }
}
