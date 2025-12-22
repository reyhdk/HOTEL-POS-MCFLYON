<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GuestController extends Controller
{

    public function index(Request $request)
    {
        $query = Guest::query();

        // 1. Filter Pencarian (Search)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. ✅ Filter Verifikasi yang DIPERBAIKI
        if ($request->has('verification_needed') && $request->verification_needed == 1) {
            $query->where(function ($q) {
                // Kondisi A: Tamu Walk-in (KTP ada di tabel Guests)
                $q->where(function ($sub) {
                    $sub->whereNotNull('ktp_image')
                        ->where('ktp_image', '!=', '')
                        ->where('is_verified', false);
                })
                    // Kondisi B: Tamu Booking Online (KTP ada di tabel Bookings)
                    ->orWhereHas('bookings', function ($b) {
                        $b->whereIn('status', ['paid', 'confirmed', 'settlement', 'pending'])
                            ->whereNotNull('ktp_image')
                            ->where('ktp_image', '!=', '')
                            ->where('verification_status', 'pending');
                    });
            });

            // 🔍 DEBUG: Log jumlah tamu yang perlu verifikasi
            $count = $query->count();
            Log::info("📋 Guests Needing Verification: {$count}");
        }

        // 3. Filter Blacklist
        if ($request->has('is_blacklisted')) {
            $query->where('is_blacklisted', $request->boolean('is_blacklisted'));
        }

        // 4. Ambil data dengan relasi
        $guests = $query->with([
            'checkIns' => function ($q) {
                $q->where('is_active', true)->with('room');
            },
            // Load booking terbaru yang relevan
            'bookings' => function ($q) {
                $q->whereIn('status', ['paid', 'confirmed', 'settlement', 'pending', 'checked_in'])
                    ->where('check_in_date', '>=', now()->subDays(7)) // 7 hari terakhir
                    ->with('room')
                    ->orderBy('created_at', 'desc');
            }
        ])->latest()->get();

        // 5. ✅ Fallback Gambar (Pinjam foto dari Booking jika Guest kosong)
        $guests->transform(function ($guest) {
            $imagePath = $guest->ktp_image;

            // Jika di profil Guest kosong, cari di Booking terbarunya
            if (empty($imagePath) && $guest->bookings->isNotEmpty()) {
                $bookingWithImage = $guest->bookings->firstWhere(fn($b) => !empty($b->ktp_image));

                if ($bookingWithImage) {
                    $imagePath = $bookingWithImage->ktp_image;

                    // 🔍 DEBUG: Log fallback
                    Log::info("📸 Fallback KTP: Guest {$guest->id} ← Booking {$bookingWithImage->id}");
                }
            }

            // Generate URL
            if ($imagePath) {
                $guest->ktp_image_url = str_contains($imagePath, 'http')
                    ? $imagePath
                    : asset('storage/' . $imagePath);
            } else {
                $guest->ktp_image_url = null;

                // 🔍 DEBUG: Peringatan jika tidak ada gambar
                if ($guest->is_verified === false) {
                    Log::warning("⚠️ Guest {$guest->id} ({$guest->name}) needs verification but has NO KTP!");
                }
            }

            return $guest;
        });

        return response()->json(['data' => $guests]);
    }

    /**
     * Menyimpan tamu baru (Walk-in atau Manual)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|unique:guests,email',
            'address' => 'nullable|string',
            'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        try {
            // Upload KTP jika ada
            $ktpPath = null;
            if ($request->hasFile('id_card_image')) {
                $ktpPath = $request->file('id_card_image')->store('ktp_images', 'public');
            }

            // Buat Guest Baru
            $guest = Guest::create([
                'name' => $validated['name'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'ktp_image' => $ktpPath,
                'is_verified' => false,
                'is_blacklisted' => false
            ]);

            Log::info("✅ Guest Created: ID {$guest->id} | Name: {$guest->name}");

            return response()->json([
                'message' => 'Tamu berhasil ditambahkan.',
                'data' => $guest
            ], 201);
        } catch (\Exception $e) {
            // Cleanup jika gagal
            if (isset($ktpPath) && Storage::disk('public')->exists($ktpPath)) {
                Storage::disk('public')->delete($ktpPath);
            }

            Log::error("Guest Store Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan tamu.'], 500);
        }
    }

    /**
     * Menampilkan detail satu tamu
     */
    public function show(Guest $guest)
    {
        // Load relasi
        $guest->load([
            'checkIns.room',
            'bookings' => function ($q) {
                $q->with('room')->latest();
            }
        ]);

        $imagePath = $guest->ktp_image;

        if (empty($imagePath)) {
            $latestBooking = $guest->bookings()
                ->whereNotNull('ktp_image')
                ->latest()
                ->first();

            if ($latestBooking) {
                $imagePath = $latestBooking->ktp_image;
                Log::info("📸 Show Fallback: Guest {$guest->id} using Booking {$latestBooking->id} KTP");
            }
        }

        // Generate URL
        if ($imagePath) {
            $guest->ktp_image_url = str_contains($imagePath, 'http')
                ? $imagePath
                : asset('storage/' . $imagePath);
        } else {
            $guest->ktp_image_url = null;
        }

        return response()->json(['data' => $guest]);
    }

    /**
     * Update data tamu
     */
    public function update(Request $request, Guest $guest)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:guests,email,' . $guest->id,
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        try {
            $guest->update($validated);

            Log::info("✅ Guest Updated: ID {$guest->id}");

            return response()->json([
                'message' => 'Data tamu berhasil diperbarui.',
                'data' => $guest
            ]);
        } catch (\Exception $e) {
            Log::error("Guest Update Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui data tamu.'], 500);
        }
    }


    public function verify($id)
    {
        $guest = Guest::findOrFail($id);

        try {
            // 1. Permanenkan foto dari booking ke guest jika kosong
            if (empty($guest->ktp_image)) {
                $latestBooking = $guest->bookings()
                    ->whereNotNull('ktp_image')
                    ->where('ktp_image', '!=', '')
                    ->latest()
                    ->first();

                if ($latestBooking) {
                    $guest->ktp_image = $latestBooking->ktp_image;
                    Log::info("📸 Verify: Copied KTP from Booking {$latestBooking->id} to Guest {$guest->id}");
                }
            }

            // 2. Update status verifikasi Guest
            $guest->update(['is_verified' => true]);

            // 3. ✅ FIX UTAMA: Update semua booking terkait
            $updatedCount = $guest->bookings()
                ->where('verification_status', 'pending')
                ->whereIn('status', ['paid', 'settlement']) // Yang sudah bayar
                ->update([
                    'verification_status' => 'verified',
                    'status' => 'confirmed'  // 🔥 INI YANG PENTING - Agar masuk kalender & list check-in
                ]);

            Log::info("✅ Guest Verified: ID {$guest->id} | Bookings updated: {$updatedCount}");

            return response()->json([
                'message' => 'Identitas tamu berhasil diverifikasi.',
                'bookings_updated' => $updatedCount,
                'data' => $guest
            ]);
        } catch (\Exception $e) {
            Log::error("Guest Verify Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal memverifikasi tamu.'], 500);
        }
    }

    /**
     *Tolak KTP & Hapus Foto
     */
    public function rejectKtp($id)
    {
        $guest = Guest::findOrFail($id);

        try {
            // 1. Hapus file fisik di Guest Profile
            if ($guest->ktp_image && Storage::disk('public')->exists($guest->ktp_image)) {
                Storage::disk('public')->delete($guest->ktp_image);
                Log::info("🗑️ Deleted Guest KTP: {$guest->ktp_image}");
            }

            // 2. Update database Guest
            $guest->update([
                'ktp_image' => null,
                'is_verified' => false
            ]);

            // 3. ✅ Hapus juga foto di semua booking terkait
            $bookings = $guest->bookings()
                ->whereNotNull('ktp_image')
                ->where('ktp_image', '!=', '')
                ->get();

            foreach ($bookings as $booking) {
                // Hapus file fisik
                if (Storage::disk('public')->exists($booking->ktp_image)) {
                    Storage::disk('public')->delete($booking->ktp_image);
                    Log::info("🗑️ Deleted Booking KTP: {$booking->ktp_image}");
                }

                // Update database
                $booking->update([
                    'ktp_image' => null,
                    'verification_status' => 'rejected'
                ]);
            }

            Log::info("❌ Guest KTP Rejected: ID {$guest->id} | Bookings affected: {$bookings->count()}");

            return response()->json([
                'message' => 'Foto KTP ditolak dan dihapus. Tamu harus upload ulang.',
                'deleted_files' => $bookings->count() + 1
            ]);
        } catch (\Exception $e) {
            Log::error("Guest Reject Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menolak verifikasi.'], 500);
        }
    }

    /**
     * ✅ ADMIN: Blacklist Tamu
     */
    public function blacklist(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $guest = Guest::findOrFail($id);

        try {
            $guest->update([
                'is_blacklisted' => true,
                'blacklist_reason' => $validated['reason']
            ]);

            Log::info("🚫 Guest Blacklisted: ID {$guest->id} | Reason: {$validated['reason']}");

            return response()->json([
                'message' => 'Tamu berhasil ditambahkan ke blacklist.'
            ]);
        } catch (\Exception $e) {
            Log::error("Blacklist Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal memblacklist tamu.'], 500);
        }
    }

    /**
     * ✅ ADMIN: Hapus dari Blacklist
     */
    public function unblacklist($id)
    {
        $guest = Guest::findOrFail($id);

        try {
            $guest->update([
                'is_blacklisted' => false,
                'blacklist_reason' => null
            ]);

            Log::info("✅ Guest Removed from Blacklist: ID {$guest->id}");

            return response()->json([
                'message' => 'Tamu berhasil dihapus dari blacklist.'
            ]);
        } catch (\Exception $e) {
            Log::error("Unblacklist Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus dari blacklist.'], 500);
        }
    }

    /**
     * Hapus tamu dari database
     */
    public function destroy(Guest $guest)
    {
        // Validasi: Cek apakah punya riwayat
        if ($guest->bookings()->exists() || $guest->checkIns()->exists()) {
            return response()->json([
                'message' => 'Tamu ini tidak dapat dihapus karena memiliki riwayat booking atau check-in.'
            ], 409);
        }

        try {
            // Hapus file KTP jika ada
            if ($guest->ktp_image && Storage::disk('public')->exists($guest->ktp_image)) {
                Storage::disk('public')->delete($guest->ktp_image);
            }

            $guest->delete();

            Log::info("🗑️ Guest Deleted: ID {$guest->id}");

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error("Guest Delete Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus tamu.'], 500);
        }
    }

    /**
     *Get Statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_guests' => Guest::count(),
                'verified_guests' => Guest::where('is_verified', true)->count(),
                'pending_verification' => Guest::where('is_verified', false)
                    ->whereNotNull('ktp_image')
                    ->count(),
                'blacklisted' => Guest::where('is_blacklisted', true)->count(),
                'active_checkins' => Guest::whereHas('checkIns', function ($q) {
                    $q->where('is_active', true);
                })->count(),
            ];

            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            Log::error("Guest Stats Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal mengambil statistik.'], 500);
        }
    }
}
