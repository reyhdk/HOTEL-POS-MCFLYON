<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chef;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChefController extends Controller
{
    /**
     * Mendapatkan daftar chef dengan detail workload
     */
    public function index(Request $request)
    {
        $query = Chef::with(['user' => function($q) {
            $q->select('id', 'name', 'email');
        }]);

        // Filter berdasarkan status aktif
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $chefs = $query->latest()->get();

        // Tambahkan informasi workload realtime
        $chefs->map(function($chef) {
            $chef->current_workload = $chef->getCurrentWorkload();
            $chef->can_take_order = $chef->canTakeOrder();
            return $chef;
        });

        return response()->json($chefs);
    }

    /**
     * Mendapatkan daftar chef yang tersedia untuk assign order
     * (Khusus untuk dropdown di Kitchen Management)
     */
    public function getAvailableChefs()
    {
        $chefs = Chef::with(['user' => function($q) {
            $q->select('id', 'name');
        }])
        ->where('is_active', true)
        ->get()
        ->map(function($chef) {
            return [
                'id' => $chef->user_id, // Pakai user_id untuk kompatibilitas dengan sistem lama
                'name' => $chef->user->name,
                'specialization' => $chef->specialization,
                'current_workload' => $chef->getCurrentWorkload(),
                'max_concurrent_orders' => $chef->max_concurrent_orders,
                'can_take_order' => $chef->canTakeOrder()
            ];
        })
        ->sortBy('current_workload') // Chef dengan beban paling sedikit di atas
        ->values();

        return response()->json($chefs);
    }

    /**
     * Mendapatkan users yang memiliki role chef/kitchen (untuk dropdown add chef)
     */
    public function getEligibleUsers()
    {
        // User yang punya role chef/kitchen tapi belum terdaftar sebagai chef
        $existingChefUserIds = Chef::pluck('user_id')->toArray();

        $users = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['chef', 'kitchen', 'admin']);
        })
        ->whereNotIn('id', $existingChefUserIds)
        ->select('id', 'name', 'email')
        ->get();

        return response()->json($users);
    }

    /**
     * Menambahkan chef baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:chefs,user_id',
            'specialization' => 'nullable|string|max:255',
            'max_concurrent_orders' => 'required|integer|min:1|max:20',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            // Validasi apakah user memiliki role chef/kitchen
            $user = User::findOrFail($validated['user_id']);
            $hasChefRole = $user->hasAnyRole(['chef', 'kitchen', 'admin']);
            
            if (!$hasChefRole) {
                return response()->json([
                    'message' => 'User tidak memiliki role Chef/Kitchen. Harap assign role terlebih dahulu.'
                ], 422);
            }

            $chef = Chef::create($validated);

            DB::commit();

            return response()->json([
                'message' => 'Chef berhasil ditambahkan',
                'data' => $chef->load('user')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating chef: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal menambahkan chef: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update data chef
     */
    public function update(Request $request, $id)
    {
        $chef = Chef::findOrFail($id);

        $validated = $request->validate([
            'specialization' => 'nullable|string|max:255',
            'max_concurrent_orders' => 'required|integer|min:1|max:20',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        try {
            $chef->update($validated);

            return response()->json([
                'message' => 'Data chef berhasil diperbarui',
                'data' => $chef->load('user')
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating chef: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal memperbarui chef: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status aktif chef
     */
    public function toggleStatus($id)
    {
        $chef = Chef::findOrFail($id);
        
        $chef->update([
            'is_active' => !$chef->is_active
        ]);

        return response()->json([
            'message' => 'Status chef berhasil diubah',
            'data' => $chef->load('user')
        ]);
    }

    /**
     * Hapus chef dari sistem
     */
    public function destroy($id)
    {
        $chef = Chef::findOrFail($id);

        // Check apakah chef masih punya order aktif
        $activeOrdersCount = $chef->activeOrders()->count();
        
        if ($activeOrdersCount > 0) {
            return response()->json([
                'message' => "Chef masih memiliki {$activeOrdersCount} pesanan aktif. Selesaikan dulu pesanannya."
            ], 422);
        }

        $chef->delete();

        return response()->json([
            'message' => 'Chef berhasil dihapus dari sistem'
        ]);
    }

    /**
     * Statistik chef (untuk dashboard)
     */
    public function statistics()
    {
        $stats = [
            'total_chefs' => Chef::count(),
            'active_chefs' => Chef::where('is_active', true)->count(),
            'available_chefs' => Chef::active()->get()->filter(fn($c) => $c->canTakeOrder())->count(),
            'total_workload' => Chef::active()->get()->sum(fn($c) => $c->getCurrentWorkload())
        ];

        return response()->json($stats);
    }
}