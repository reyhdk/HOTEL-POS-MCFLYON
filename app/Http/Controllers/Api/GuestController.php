<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Booking;
use App\Mail\KtpRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GuestController extends Controller
{
    /**
     * ✅ ADMIN: Daftar Tamu dengan Auto-Sync KTP dari Booking
     */
    public function index(Request $request)
    {
        $query = Guest::query();

        // Load relasi
        $query->with([
            'checkIns' => fn($q) => $q->where('is_active', true)->with('room'),
            'bookings' => fn($q) => $q->latest()
        ]);

        // Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter Verifikasi
        if ($request->has('verification_status')) {
            if ($request->verification_status === 'pending') {
                $query->where('is_verified', false)
                      ->whereNotNull('ktp_image');
            } elseif ($request->verification_status === 'verified') {
                $query->where('is_verified', true);
            }
        }

        $guests = $query->latest()->paginate($request->per_page ?? 12);

        // ✅ AUTO-SYNC: Jika guest belum punya KTP, ambil dari booking terbaru
        $guests->getCollection()->transform(function($guest) {
            $latestBooking = $guest->bookings->first();
            
            if (!$guest->ktp_image && $latestBooking && $latestBooking->ktp_image) {
                // Sinkronisasi otomatis dari booking ke guest
                $guest->ktp_image = $latestBooking->ktp_image;
                $guest->save();
            }
            
            return $guest;
        });

        return response()->json($guests);
    }

    /**
     * ✅ USER: Ambil Profil + Auto-Sync dari Booking
     */
    public function getMyProfile(Request $request)
    {
        $user = $request->user();
        
        // ✅ Load guest dengan relasi bookings
        $guest = Guest::with(['bookings' => function($q) {
            $q->latest()->take(1); // Ambil 1 booking terbaru saja
        }])
        ->where('email', $user->email)
        ->orWhere('phone_number', $user->phone_number)
        ->first();

        if (!$guest) {
            return response()->json(['message' => 'Profil tidak ditemukan'], 404);
        }

        // ✅ AUTO-SYNC dari booking terbaru jika guest belum punya KTP
        if (!$guest->ktp_image && $guest->bookings && $guest->bookings->count() > 0) {
            $latestBooking = $guest->bookings->first();
            
            if ($latestBooking && $latestBooking->ktp_image) {
                $guest->ktp_image = $latestBooking->ktp_image;
                $guest->save();
                Log::info("Auto-synced KTP from Booking ID: {$latestBooking->id}");
            }
        }

        return response()->json($guest);
    }
    /**
     * ✅ ADMIN: Setujui KTP
     */
    public function verifyKtp($id)
    {
        $guest = Guest::find($id);
        if (!$guest) return response()->json(['message' => 'Tamu tidak ditemukan'], 404);

        try {
            $guest->update([
                'is_verified' => true,
                'rejection_reason' => null  // ✅ Bersihkan alasan penolakan
            ]);

            return response()->json([
                'message' => 'KTP Berhasil Diverifikasi',
                'data' => $guest
            ]);
        } catch (\Exception $e) {
            Log::error("Error Verifikasi Guest {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses verifikasi'], 500);
        }
    }

    /**
     * ✅ ADMIN: Tolak KTP (File TIDAK dihapus)
     */
    public function rejectKtp(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alasan penolakan wajib diisi',
                'errors' => $validator->errors()
            ], 422);
        }

        $guest = Guest::find($id);
        if (!$guest) return response()->json(['message' => 'Tamu tidak ditemukan'], 404);

        try {
            $guest->update([
                'is_verified' => false,
                'rejection_reason' => $request->reason  // ✅ Simpan di guests
            ]);

            // Kirim email notifikasi
            if ($guest->email) {
                Mail::to($guest->email)->send(new KtpRejected($guest, $request->reason));
            }

            return response()->json([
                'message' => 'KTP ditolak. Tamu akan diberitahu untuk upload ulang.'
            ]);
        } catch (\Exception $e) {
            Log::error("Error Reject KTP Guest {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menolak KTP'], 500);
        }
    }

    /**
     * ✅ USER: Upload Ulang KTP (Hapus File Lama Setelah Berhasil)
     */
    public function selfUpdateKtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ktp_image' => 'required|image|mimes:jpg,jpeg,png|max:4096'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $guest = Guest::where('email', $user->email)
                    ->orWhere('phone_number', $user->phone_number)
                    ->first();

        if (!$guest) return response()->json(['message' => 'Profil tidak ditemukan'], 404);

        try {
            DB::beginTransaction();

            $oldPath = $guest->ktp_image;

            // Upload file baru
            $newPath = $request->file('ktp_image')->store('ktp_images', 'public');

            // Update data guest
            $guest->update([
                'ktp_image' => $newPath,
                'is_verified' => false,           // ✅ Reset verifikasi
                'rejection_reason' => null        // ✅ Hapus alasan penolakan
            ]);

            // ✅ HAPUS FILE LAMA setelah file baru berhasil tersimpan
            if ($oldPath && $oldPath !== $newPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
                Log::info("File lama dihapus: {$oldPath}");
            }

            DB::commit();

            return response()->json([
                'message' => 'KTP berhasil diperbarui. Menunggu verifikasi admin.',
                'data' => $guest
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error Update KTP: " . $e->getMessage());
            return response()->json(['message' => 'Gagal mengunggah foto'], 500);
        }
    }

    /**
     * Detail Satu Tamu
     */
    public function show($id)
    {
        $guest = Guest::with(['checkIns.room', 'bookings' => fn($q) => $q->latest()])->find($id);
        if (!$guest) return response()->json(['message' => 'Tamu tidak ditemukan'], 404);

        return response()->json(['data' => $guest]);
    }

    /**
     * ADMIN: Tambah Tamu Baru
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

        $ktpPath = $request->hasFile('ktp_image')
            ? $request->file('ktp_image')->store('ktp_images', 'public')
            : null;

        $guest = Guest::firstOrCreate(
            ['phone_number' => $request->phone_number],
            [
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'ktp_image' => $ktpPath,
                'is_verified' => $request->hasFile('ktp_image')
            ]
        );

        return response()->json(['data' => $guest], 201);
    }

    /**
     * ADMIN: Update Profil Tamu
     */
    public function update(Request $request, $id)
    {
        $guest = Guest::find($id);
        if (!$guest) return response()->json(['message' => 'Tamu tidak ditemukan'], 404);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email',
            'ktp_image' => 'nullable|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except(['ktp_image']);

        if ($request->hasFile('ktp_image')) {
            // Hapus file lama
            if ($guest->ktp_image && Storage::disk('public')->exists($guest->ktp_image)) {
                Storage::disk('public')->delete($guest->ktp_image);
            }
            
            $data['ktp_image'] = $request->file('ktp_image')->store('ktp_images', 'public');
            $data['is_verified'] = true;
        }

        $guest->update($data);

        return response()->json(['data' => $guest, 'message' => 'Profil tamu diperbarui']);
    }

    /**
     * Hapus Tamu
     */
    public function destroy($id)
    {
        $guest = Guest::find($id);
        if (!$guest) return response()->json(['message' => 'Tamu tidak ditemukan'], 404);

        if ($guest->checkIns()->where('is_active', true)->exists()) {
            return response()->json(['message' => 'Tamu sedang menginap'], 400);
        }

        // Hapus file KTP
        if ($guest->ktp_image && Storage::disk('public')->exists($guest->ktp_image)) {
            Storage::disk('public')->delete($guest->ktp_image);
        }

        $guest->delete();

        return response()->json(['message' => 'Data tamu dihapus']);
    }
}