<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Booking;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuestController extends Controller
{
    /**
     * Menampilkan daftar tamu.
     */
    public function index(Request $request)
    {
        $query = Guest::query();

        // [PERBAIKAN UTAMA DI SINI]
        // Kita harus meload relasi 'bookings' agar frontend bisa baca ID Booking
        $query->with([
            'checkIns' => function ($q) {
                $q->where('is_active', true)->with('room');
            },
            'bookings' => function ($q) {
                // Urutkan dari yg terbaru, agar bookings[0] di frontend adalah yg terkini
                $q->orderBy('created_at', 'desc');
            }
        ]);

        // Filter Pencarian
        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter Status Verifikasi
        if ($request->has('verification_status')) {
            if ($request->verification_status === 'pending') {
                // Pending = Belum Verified DAN Punya KTP (atau Punya Booking Paid)
                $query->where('is_verified', false)
                    ->where(function ($q) {
                        $q->whereNotNull('ktp_image')
                            ->orWhereHas('bookings', fn($b) => $b->where('status', 'paid'));
                    });
            } elseif ($request->verification_status === 'verified') {
                $query->where('is_verified', true);
            } elseif ($request->verification_status === 'unverified') {
                $query->where('is_verified', false)->whereNull('ktp_image');
            }
        }

        $guests = $query->latest()->paginate($request->per_page ?? 10);
        return response()->json($guests);
    }

    /**
     * Simpan tamu baru.
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
     * Tampilkan detail tamu.
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

        if ($request->hasFile('ktp_image')) {
            if ($guest->ktp_image && Storage::disk('public')->exists($guest->ktp_image)) {
                Storage::disk('public')->delete($guest->ktp_image);
            }
            $data['ktp_image'] = $request->file('ktp_image')->store('ktp_images', 'public');
            // Reset verifikasi jika upload KTP baru
            $data['is_verified'] = false;
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

        if ($guest->checkIns()->where('is_active', true)->exists()) {
            return response()->json(['message' => 'Tidak bisa menghapus tamu yang sedang menginap.'], 400);
        }

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
     * Tolak KTP Tamu:
     */
    public function rejectKtp($id)
    {
        $guest = Guest::find($id);

        if (!$guest) {
            return response()->json(['message' => 'Tamu tidak ditemukan'], 404);
        }

        try {
            DB::transaction(function () use ($guest) {

                // Reject semua booking aktif milik tamu ini
                Booking::where('guest_id', $guest->id)
                    ->whereIn('status', ['pending', 'awaiting_payment', 'confirmed', 'paid'])
                    ->where('check_in_date', '>=', now()->startOfDay())
                    ->update([
                        'status' => 'rejected'
                    ]);

                // Force checkout jika sedang menginap
                $activeCheckIns = CheckIn::where('guest_id', $guest->id)
                    ->where('is_active', true)
                    ->with(['room', 'booking'])
                    ->get();

                foreach ($activeCheckIns as $checkIn) {
                    $checkIn->update([
                        'is_active' => false,
                        'check_out_time' => now()
                    ]);

                    if ($checkIn->room) {
                        $checkIn->room->update(['status' => 'dirty']);
                    }

                    if ($checkIn->booking) {
                        $checkIn->booking->update([
                            'status' => 'rejected',
                            'notes'  => 'Force Checkout: KTP Ditolak'
                        ]);
                    }
                }

                // Hapus foto KTP
                if ($guest->ktp_image && Storage::disk('public')->exists($guest->ktp_image)) {
                    Storage::disk('public')->delete($guest->ktp_image);
                }

                $guest->ktp_image = null;
                $guest->is_verified = false;
                $guest->save();
            });

            return response()->json([
                'message' => 'KTP ditolak. Booking dibatalkan, Tamu dicheck-out, dan Jadwal dihapus.',
                'data' => $guest
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal Reject KTP Guest ID ' . $guest->id . ': ' . $e->getMessage());

            return response()->json([
                'message' => 'Gagal memproses penolakan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
