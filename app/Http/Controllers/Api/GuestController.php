<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Booking; // ✅ [FIX] Wajib import Model Booking
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // ✅ [FIX] Wajib import DB Facade
use Illuminate\Support\Facades\Log; // ✅ [FIX] Untuk debugging error

class GuestController extends Controller
{
    /**
     * Menampilkan daftar tamu dengan filter dan relasi.
     */
    public function index(Request $request)
    {
        $query = Guest::query();

        // 1. Eager Loading Checkins & Room
        $query->with(['checkIns' => function ($q) {
            $q->where('is_active', true)->with('room');
        }]);

        // 2. Filter Pencarian
        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 3. Filter Status Verifikasi
        if ($request->has('verification_status')) {
            if ($request->verification_status === 'pending') {
                $query->where('is_verified', false)->whereNotNull('ktp_image');
            } elseif ($request->verification_status === 'verified') {
                $query->where('is_verified', true);
            } elseif ($request->verification_status === 'unverified') {
                $query->where('is_verified', false)->whereNull('ktp_image');
            }
        }

        // 4. Pagination
        $guests = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($guests);
    }

    /**
     * Simpan tamu baru (Walk-in).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'ktp_image' => 'nullable|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ktpPath = null;
        if ($request->hasFile('ktp_image')) {
            $ktpPath = $request->file('ktp_image')->store('ktp_images', 'public');
        }

        $guest = Guest::firstOrCreate(
            ['phone_number' => $request->phone_number],
            [
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'ktp_image' => $ktpPath,
                'is_verified' => false
            ]
        );

        return response()->json(['data' => $guest], 201);
    }

    /**
     * Tampilkan detail tamu spesifik.
     */
    public function show($id)
    {
        $guest = Guest::with(['checkIns.room', 'bookings'])->find($id);

        if (!$guest) {
            return response()->json(['message' => 'Tamu tidak ditemukan'], 404);
        }

        return response()->json(['data' => $guest]);
    }

    /**
     * Update data tamu.
     */
    public function update(Request $request, $id)
    {
        $guest = Guest::find($id);
        if (!$guest) {
            return response()->json(['message' => 'Tamu tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email',
            'ktp_image' => 'nullable|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except(['ktp_image']);

        // Handle ganti foto KTP
        if ($request->hasFile('ktp_image')) {
            if ($guest->ktp_image && Storage::disk('public')->exists($guest->ktp_image)) {
                Storage::disk('public')->delete($guest->ktp_image);
            }
            $data['ktp_image'] = $request->file('ktp_image')->store('ktp_images', 'public');
            $data['is_verified'] = false; // Reset verifikasi
        }

        $guest->update($data);

        return response()->json(['data' => $guest, 'message' => 'Data tamu berhasil diupdate']);
    }

    /**
     * Hapus tamu.
     */
    public function destroy($id)
    {
        $guest = Guest::find($id);
        if (!$guest) {
            return response()->json(['message' => 'Tamu tidak ditemukan'], 404);
        }

        // Cek jika tamu sedang menginap
        if ($guest->checkIns()->where('is_active', true)->exists()) {
            return response()->json(['message' => 'Tidak bisa menghapus tamu yang sedang menginap.'], 400);
        }

        // Hapus foto KTP
        if ($guest->ktp_image && Storage::disk('public')->exists($guest->ktp_image)) {
            Storage::disk('public')->delete($guest->ktp_image);
        }

        $guest->delete();

        return response()->json(['message' => 'Tamu berhasil dihapus']);
    }

    /**
     * Verifikasi KTP Tamu.
     */
    public function verifyKtp($id)
    {
        $guest = Guest::find($id);

        if (!$guest) {
            return response()->json(['message' => 'Tamu tidak ditemukan'], 404);
        }

        $guest->is_verified = true;
        $guest->save();

        return response()->json([
            'message' => 'KTP Berhasil Diverifikasi',
            'data' => $guest
        ]);
    }

    /**
     * [FIXED] Tolak KTP Tamu (SAFE MODE)
     * - Menghapus update kolom 'notes' untuk mencegah error SQL jika kolom tidak ada.
     * - Memastikan Booking status berubah jadi 'rejected'.
     */
    public function rejectKtp($id)
    {
        $guest = Guest::find($id);

        if (!$guest) {
            return response()->json(['message' => 'Tamu tidak ditemukan'], 404);
        }

        try {
            DB::transaction(function () use ($guest) {

                // 1. CARI & BATALKAN BOOKING AKTIF/MENDATANG
                Booking::where('guest_id', $guest->id)
                    ->whereIn('status', ['pending', 'awaiting_payment', 'confirmed', 'paid'])
                    ->where('check_in_date', '>=', now()->startOfDay())
                    ->update([
                        'status' => 'rejected'
                    ]);

                // 2. Hapus file fisik KTP
                if ($guest->ktp_image && Storage::disk('public')->exists($guest->ktp_image)) {
                    Storage::disk('public')->delete($guest->ktp_image);
                }

                // 3. Reset status tamu di database
                $guest->ktp_image = null;
                $guest->is_verified = false;
                $guest->save();
            });

            return response()->json([
                'message' => 'KTP ditolak. Booking terkait telah dibatalkan.',
                'data' => $guest
            ]);
        } catch (\Throwable $e) {
            // PERBAIKAN DI SINI: Gunakan $guest->id
            Log::error('Gagal Reject KTP Guest ID ' . $guest->id . ': ' . $e->getMessage());

            return response()->json([
                'message' => 'Gagal memproses penolakan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }}