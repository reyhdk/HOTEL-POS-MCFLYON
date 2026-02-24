<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KitchenController extends Controller
{
    /**
     * Mengambil pesanan KHUSUS HARI INI dengan Paginate & Filter
     */
    public function getTodayOrders(Request $request)
    {
        $perPage = $request->input('per_page', 12);
        
        $query = Order::with(['room', 'table', 'guest', 'user', 'chef', 'items.menu'])
                      ->whereDate('created_at', Carbon::today()) // HANYA HARI INI
                      ->where('status', '!=', 'cancelled'); // Sembunyikan yang dibatalkan

        // 1. Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 2. Filter Search (Cari Kamar, Meja, atau Nama Tamu)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Cari dari Nomor Kamar
                $q->whereHas('room', function($roomQuery) use ($search) {
                    $roomQuery->where('room_number', 'like', "%{$search}%");
                })
                // Cari dari Nama Meja
                ->orWhereHas('table', function($tableQuery) use ($search) {
                    $tableQuery->where('name', 'like', "%{$search}%");
                })
                // Cari dari Nama Guest (Tamu Menginap)
                ->orWhereHas('guest', function($guestQuery) use ($search) {
                    $guestQuery->where('name', 'like', "%{$search}%");
                })
                // Cari dari Nama User (Tamu Walk-in/POS)
                ->orWhereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Urutkan dari yang terbaru (pesanan terbaru di atas)
        $orders = $query->latest()->paginate($perPage);

        return response()->json($orders);
    }

    public function getChefs()
    {
        // Jika Anda menggunakan Spatie Permission:
        $chefs = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['chef', 'kitchen', 'admin']); // Sesuaikan nama role di DB (Admin ditambahkan sbg fallback)
        })->select('id', 'name')->get();

        // [Alternatif] Jika Anda HANYA menggunakan kolom 'role' string biasa di tabel users, gunakan ini:
        // $chefs = User::whereIn('role', ['chef', 'kitchen', 'admin'])->select('id', 'name')->get();

        return response()->json($chefs);
    }

    /**
     * Mengambil status antrean pesanan dapur secara realtime
     */
    public function getQueueStatus()
    {
        $activeOrders = Order::with(['room', 'table', 'guest', 'user', 'items.menu'])
            ->whereDate('created_at', Carbon::today())
            // Menambahkan 'paid' agar pesanan cash langsung masuk antrean masak dapur
            ->whereIn('status', ['pending', 'processing', 'paid', 'ready_for_delivery']) 
            ->latest()
            ->get();

        // Kalkulasi beban waktu masak untuk hari ini
        $totalWorkloadMinutes = 0;

        // Injeksi total estimasi per item ke masing-masing order
        $activeOrders->map(function ($order) use (&$totalWorkloadMinutes) {
            $orderWorkload = 0;
            
            foreach ($order->items as $item) {
                // Asumsi field di tabel menus adalah 'cooking_estimation_time', jika null gunakan default 10 menit
                $estimationPerPortion = $item->menu->cooking_estimation_time ?? 10;
                $totalEstimation = $estimationPerPortion * $item->quantity;
                $orderWorkload += $totalEstimation;
            }

            $order->estimated_workload = $orderWorkload;
            
            // Tambahkan ke total beban global (jika pesanan belum selesai dimasak)
            if (in_array($order->status, ['pending', 'paid', 'processing'])) {
                $totalWorkloadMinutes += $orderWorkload;
            }

            return $order;
        });

        return response()->json([
            'success' => true,
            'total_queue' => $activeOrders->count(),
            'orders' => $activeOrders, // <-- Diubah menjadi 'orders' agar sesuai dengan Vue
            'total_workload_minutes' => $totalWorkloadMinutes // <-- Ditambahkan agar estimasi Vue berjalan
        ]);
    }

    /**
     * Assign Chef dan Estimasi Waktu ke Pesanan
     */
    public function assignChef(Request $request, Order $order)
    {
        $request->validate([
            'chef_id' => 'required|exists:users,id',
            'estimated_time' => 'required|integer|min:1' // Dalam Menit
        ]);

        $order->update([
            'chef_id' => $request->chef_id,
            'estimated_time' => $request->estimated_time,
            'status' => 'processing' // Otomatis ubah status menjadi processing saat chef di-assign
        ]);

        return response()->json([
            'message' => 'Chef berhasil ditugaskan dan pesanan mulai diproses.',
            'order' => $order->load('chef') // Kembalikan relasi chef agar frontend bisa langsung menampilkannya
        ]);
    }

    /**
     * Update Status Pesanan dari Dapur
     */
    public function updateStatus(Request $request, Order $order)
    {
        // Menyesuaikan status dengan versi lama (menambahkan ready_for_delivery)
        $validated = $request->validate([
            'status' => 'required|in:processing,ready_for_delivery,completed,cancelled'
        ]);

        $order->update([
            'status' => $validated['status']
        ]);

        return response()->json([
            'message' => 'Status pesanan berhasil diperbarui.',
            'order' => $order
        ]);
    }
}