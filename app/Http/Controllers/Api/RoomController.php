<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Booking; 
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    /**
     * Menampilkan daftar semua kamar dengan relasi lengkap (Untuk Admin/Staff).
     */
    public function index()
    {
        $rooms = Room::with([
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

        return response()->json($rooms);
    }

    /**
     * Pencarian Kamar Tersedia untuk Online Booking.
     * Menggunakan logika "Anti-Bentrok" agar kamar yang sudah dibooking tidak muncul.
     * Diperbaiki agar tetap menampilkan kamar yang status fisiknya 'occupied' atau 'dirty'
     * selama tidak ada booking yang bertabrakan di tanggal yang dipilih.
     */
    public function getAvailableRooms(Request $request)
    {
        try {
            $checkIn = $request->check_in_date;
            $checkOut = $request->check_out_date;
            $type = $request->type;
            $facilityIds = $request->facility_ids;

            // 1. Cari ID kamar yang sudah memiliki booking pada rentang tanggal tersebut
            $bookedRoomIds = [];
            if ($checkIn && $checkOut) {
                $bookedRoomIds = Booking::where(function($query) use ($checkIn, $checkOut) {
                    // Logika Overlap: Kamar tidak tersedia jika (CheckIn_Lama < CheckOut_Baru) DAN (CheckOut_Lama > CheckIn_Baru)
                    $query->where('check_in_date', '<', $checkOut)
                          ->where('check_out_date', '>', $checkIn);
                })
                ->whereIn('status', ['pending', 'confirmed', 'paid', 'checked_in']) // Booking yang dianggap aktif
                ->pluck('room_id')
                ->toArray();
            }

            /**
             * PERBAIKAN:
             * Kita tidak hanya mencari status 'available' secara kaku.
             * Kamar yang sedang dihuni ('occupied') atau kotor ('dirty') harusnya tetap bisa dipesan
             * untuk tanggal di masa depan (misal: minggu depan).
             * Filter 'status' di sini hanya untuk mengecualikan kamar yang rusak/maintenance.
             */
            $query = Room::with('facilities')
                ->whereIn('status', ['available', 'occupied', 'dirty', 'request cleaning'])
                ->whereNotIn('id', $bookedRoomIds);

            // Filter Tipe Kamar (Gunakan LIKE agar lebih fleksibel terhadap huruf besar/kecil)
            if ($type) {
                $query->where('type', 'LIKE', $type);
            }

            // Filter Fasilitas (Jika user memilih filter fasilitas tertentu)
            if ($facilityIds && is_array($facilityIds)) {
                foreach ($facilityIds as $id) {
                    $query->whereHas('facilities', function($q) use ($id) {
                        $q->where('facilities.id', $id);
                    });
                }
            }

            $rooms = $query->get()->map(function($room) {
                // Format URL Gambar Kamar agar bisa diakses Frontend
                $room->image_url = $room->image 
                    ? (str_starts_with($room->image, 'http') ? $room->image : asset('storage/' . $room->image))
                    : null;

                // Format URL Ikon Fasilitas dari database
                $room->facilities->transform(function($facility) {
                    $facility->icon_url = $facility->icon 
                        ? (str_starts_with($facility->icon, 'http') ? $facility->icon : asset('storage/' . $facility->icon))
                        : null;
                    return $facility;
                });

                return $room;
            });
            
            return response()->json($rooms);
        } catch (\Exception $e) {
            Log::error("Error pada getAvailableRooms: " . $e->getMessage());
            return response()->json(['message' => 'Gagal mengambil data kamar tersedia.'], 500);
        }
    }

    /**
     * Menampilkan detail satu kamar untuk publik.
     */
    public function showPublic(Room $room)
    {
        return response()->json($room->load('facilities'));
    }

    /**
     * Menyimpan data kamar baru (Admin).
     */
    public function store(Request $request)
    {
        $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number',
            'type' => 'required|string',
            'status' => 'required|string',
            'price_per_night' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'facility_ids' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $room = new Room();
            $room->room_number = $request->room_number;
            $room->type = $request->type;
            $room->status = $request->status;
            $room->price_per_night = $request->price_per_night;
            $room->description = $request->description;

            // Handle Upload Gambar Kamar
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('rooms', 'public');
                $room->image = $path;
            }

            $room->save();

            // Handle Relasi Fasilitas
            if ($request->has('facility_ids')) {
                $facilities = is_string($request->facility_ids) 
                    ? explode(',', $request->facility_ids) 
                    : $request->facility_ids;
                $room->facilities()->attach($facilities);
            }

            DB::commit();

            return response()->json([
                'message' => 'Kamar berhasil ditambahkan ke sistem.', 
                'data' => $room->load('facilities')
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal Add Room: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Memperbarui data kamar (Admin).
     */
    public function update(Request $request, Room $room)
    {
        $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number,' . $room->id,
            'type' => 'required|string',
            'status' => 'required|string',
            'price_per_night' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        try {
            DB::beginTransaction();

            $room->room_number = $request->room_number;
            $room->type = $request->type;
            $room->status = $request->status;
            $room->price_per_night = $request->price_per_night;
            $room->description = $request->description;

            // Handle Perubahan Gambar
            if ($request->hasFile('image')) {
                // Hapus foto lama jika ada
                if ($room->image && Storage::disk('public')->exists($room->image)) {
                    Storage::disk('public')->delete($room->image);
                }
                $path = $request->file('image')->store('rooms', 'public');
                $room->image = $path;
            }

            $room->save();

            // Sinkronisasi Fasilitas (Sync akan menghapus yang lama dan mengganti dengan yang baru)
            if ($request->has('facility_ids')) {
                $facilities = is_string($request->facility_ids) 
                    ? explode(',', $request->facility_ids) 
                    : $request->facility_ids;
                $room->facilities()->sync($facilities);
            }

            DB::commit();

            return response()->json([
                'message' => 'Data kamar berhasil diperbarui.', 
                'data' => $room->load('facilities')
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal Update Room: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus kamar (Admin).
     */
    public function destroy(Room $room)
    {
        // Jangan hapus jika sedang terisi
        if ($room->status === 'occupied') {
            return response()->json(['message' => 'Kamar tidak bisa dihapus karena masih terisi tamu!'], 409);
        }

        try {
            // Hapus file gambar di storage
            if ($room->image && Storage::disk('public')->exists($room->image)) {
                Storage::disk('public')->delete($room->image);
            }
            
            $room->delete();

            return response()->json(['message' => 'Kamar telah dihapus dari sistem.'], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal menghapus data kamar.'], 500);
        }
    }

    /**
     * Menandai kamar telah selesai dibersihkan (Housekeeping).
     */
    public function markAsClean(Room $room)
    {
        $invalidStatuses = ['needs cleaning', 'request cleaning', 'dirty'];
        
        if (!in_array($room->status, $invalidStatuses)) {
            return response()->json(['message' => 'Kamar tidak dalam status perlu pembersihan.'], 409);
        }

        // Cek apakah ada tamu di dalam (Check-in aktif)
        $hasActiveCheckIn = CheckIn::where('room_id', $room->id)
            ->where('is_active', true)
            ->exists();

        // Jika ada tamu, status kembali ke 'occupied'. Jika kosong, status kembali ke 'available'.
        if ($hasActiveCheckIn) {
            $room->status = 'occupied';
        } else {
            $room->status = 'available';
        }
        
        $room->save();

        return response()->json([
            'message' => 'Status pembersihan selesai diperbarui.',
            'new_status' => $room->status
        ]);
    }

    /**
     * Permintaan Pembersihan Kamar (Cleaning Service).
     */
    public function requestCleaning(Request $request, Room $room)
    {
        if ($room->status !== 'occupied') {
            return response()->json(['message' => 'Hanya kamar terisi yang bisa melakukan request cleaning.'], 409);
        }

        $room->status = 'request cleaning';
        $room->save();

        return response()->json(['message' => 'Permintaan pembersihan kamar telah dikirim ke tim HK.']);
    }

    /**
     * Proses Checkout Kamar dan Sinkronisasi Status Booking.
     */
    public function checkout(Room $room)
    {
        if ($room->status !== 'occupied') {
            return response()->json(['message' => 'Kamar ini tidak sedang dalam status terisi.'], 400);
        }

        try {
            DB::beginTransaction();

            // Cari data check-in yang aktif
            $activeCheckIn = CheckIn::where('room_id', $room->id)
                ->where('is_active', true)
                ->latest()
                ->first();

            if ($activeCheckIn) {
                // 1. Nonaktifkan Check-In dan catat waktu checkout
                $activeCheckIn->is_active = false;
                $activeCheckIn->check_out_time = now();
                $activeCheckIn->save();

                // 2. Jika check-in berasal dari booking, tandai booking selesai
                if ($activeCheckIn->booking_id) {
                    $booking = Booking::find($activeCheckIn->booking_id);
                    if ($booking) {
                        $booking->status = 'completed';
                        $booking->save();
                    }
                }
            }

            // 3. Set status kamar menjadi kotor agar dibersihkan terlebih dahulu
            $room->status = 'dirty';
            $room->save();

            DB::commit();

            return response()->json([
                'message' => 'Checkout berhasil diproses. Kamar kini berstatus Kotor.',
                'room_status' => $room->status
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error saat Checkout: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses checkout: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan daftar kamar yang sedang terisi untuk POS/Restoran.
     */
    public function getOccupiedRoomsForPos()
    {
        $rooms = Room::where('status', 'occupied')
            ->select('id', 'room_number', 'type')
            ->get();
            
        return response()->json($rooms);
    }
}