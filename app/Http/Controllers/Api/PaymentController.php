<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\TransactionExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    /**
     * Mendapatkan daftar pesanan yang menunggu pembayaran
     */
    public function getPendingOrders()
    {
        // PERBAIKAN: Tampilkan semua order yang belum dibayar
        $billableStatuses = ['pending', 'processing', 'delivering'];

        $orders = Order::whereIn('status', $billableStatuses)
                        ->with('room', 'items.menu', 'user')
                        ->latest()
                        ->get();
        return response()->json($orders);
    }

    /**
     * Memproses pembayaran pesanan individual (bukan melalui folio)
     */
    public function processPayment(Request $request, Order $order)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        // PERBAIKAN: Cek apakah order masih bisa dibayar
        $billableStatuses = ['pending', 'processing', 'delivering'];

        if (!in_array($order->status, $billableStatuses)) {
            return response()->json(['message' => 'Pesanan ini tidak bisa dibayar (status: ' . $order->status . ')'], 400);
        }

        DB::beginTransaction();

        try {
            // Ubah status menjadi 'paid' (konsisten dengan folio)
            $order->status = 'paid';
            $order->save();

            DB::commit();
            return response()->json(['message' => 'Pembayaran berhasil.', 'order' => $order]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses pembayaran.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Membatalkan pesanan yang masih pending
     */
    public function cancelOrder(Order $order)
    {
        // PERBAIKAN: Hanya order yang belum dibayar bisa dibatalkan
        $billableStatuses = ['pending', 'processing', 'delivering'];

        if (!in_array($order->status, $billableStatuses)) {
            return response()->json(['message' => 'Hanya pesanan yang belum dibayar yang bisa dibatalkan.'], 400);
        }

        DB::beginTransaction();
        try {
            // Kembalikan stok
            foreach ($order->items as $item) {
                $item->menu()->increment('stock', $item->quantity);
            }

            // Ubah status pesanan menjadi 'cancelled'
            $order->status = 'cancelled';
            $order->save();

            DB::commit();

            return response()->json(['message' => 'Pesanan berhasil dibatalkan dan stok telah dikembalikan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membatalkan pesanan.', 'error' => $e->getMessage()], 500);
        }
    }

   /**
     * Mendapatkan riwayat transaksi dengan Filter & Total Pendapatan
     */
    public function getTransactionHistory(Request $request)
    {
        // 1. Query Dasar
        $query = Order::with(['room', 'items.menu', 'user', 'guest']);

        // 2. Filter Tanggal (Start - End)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date . ' 00:00:00';
            $endDate = $request->end_date . ' 23:59:59';
            $query->whereBetween('updated_at', [$startDate, $endDate]); // Gunakan updated_at agar sesuai waktu pembayaran
        }

        // 3. Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: Ambil paid & cancelled
            $query->whereIn('status', ['paid', 'cancelled']);
        }

        // 4. Search (ID, Nama Tamu, Midtrans ID)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('midtrans_order_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('guest', function($qGuest) use ($searchTerm) {
                      $qGuest->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('user', function($qUser) use ($searchTerm) {
                      $qUser->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // --- FITUR BARU: Hitung Total Revenue ---
        // Clone query agar tidak merusak pagination
        $revenueQuery = clone $query;
        // Hitung total hanya yang statusnya 'paid'
        $totalRevenue = $revenueQuery->where('status', 'paid')->sum('total_price');

        // 5. Pagination
        $orders = $query->latest()->paginate(10); // 10 per halaman

        return response()->json([
            'orders' => $orders,
            'total_revenue' => $totalRevenue
        ]);
    }
    public function exportReport(Request $request)
{
    // 1. Ambil Query Filter (SAMA PERSIS dengan getTransactionHistory)
    $query = Order::with(['room', 'user', 'guest']);

    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('updated_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    } else {
        $query->whereIn('status', ['paid', 'cancelled']);
    }

    if ($request->filled('search')) {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('id', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('guest', function($q2) use ($searchTerm) { $q2->where('name', 'LIKE', "%{$searchTerm}%"); });
        });
    }

    // Ambil SEMUA data (tanpa pagination)
    $orders = $query->latest()->get();
    $totalRevenue = $orders->where('status', 'paid')->sum('total_price');

    // 2. Cek tipe export
    if ($request->type == 'pdf') {
        $pdf = Pdf::loadView('exports.transactions', compact('orders', 'totalRevenue'));
        // Set kertas landscape agar muat banyak kolom
        return $pdf->setPaper('a4', 'landscape')->download('Laporan-Transaksi.pdf');
    } 

    if ($request->type == 'excel') {
        return Excel::download(new TransactionExport($orders, $totalRevenue), 'Laporan-Transaksi.xlsx');
    }
}
}
