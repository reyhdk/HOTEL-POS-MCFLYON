<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
use App\Models\WarehouseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockTransactionController extends Controller
{
    /**
     * Menampilkan daftar transaksi stok
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $type = $request->input('type'); // 'in', 'out'
            $itemId = $request->input('item_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query = StockTransaction::with(['warehouseItem', 'creator']);

            // Filter berdasarkan warehouse item
            if ($itemId) {
                $query->where('warehouse_item_id', $itemId);
            }

            // Filter tipe transaksi
            if ($type && in_array($type, ['in', 'out'])) {
                $query->where('transaction_type', $type);
            }

            // Filter tanggal
            if ($startDate) {
                $query->where('transaction_date', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('transaction_date', '<=', $endDate);
            }

            // Pencarian
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_code', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhereHas('warehouseItem', function ($q2) use ($search) {
                          $q2->where('name', 'like', "%{$search}%")
                             ->orWhere('code', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $query->latest('transaction_date')->latest('created_at');

            // Ambil data dengan pagination
            $transactions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $transactions->items(),
                'meta' => [
                    'total' => $transactions->total(),
                    'per_page' => $transactions->perPage(),
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyimpan transaksi baru (masuk/keluar)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_item_id' => 'required|exists:warehouse_items,id',
            'transaction_type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'reference_type' => 'nullable|in:purchase,production,waste,sale,audit,other',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'transaction_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $item = WarehouseItem::findOrFail($request->warehouse_item_id);

            // Validasi stok untuk transaksi keluar
            if ($request->transaction_type === 'out' && $item->current_stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak mencukupi',
                    'available_stock' => $item->current_stock
                ], 400);
            }

            // Hitung total harga
            $totalPrice = $request->quantity * $request->unit_price;

            // Buat transaksi
            $transaction = StockTransaction::create([
                'warehouse_item_id' => $request->warehouse_item_id,
                'transaction_type' => $request->transaction_type,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_price' => $totalPrice,
                'reference_type' => $request->reference_type ?? 'other',
                'reference_id' => $request->reference_id,
                'notes' => $request->notes,
                'transaction_date' => $request->transaction_date,
                'created_by' => auth()->id()
            ]);

            // Update stok barang
            if ($request->transaction_type === 'in') {
                $item->increment('current_stock', $request->quantity);
            } else {
                $item->decrement('current_stock', $request->quantity);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dicatat',
                'data' => $transaction->load('warehouseItem')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail transaksi
     */
    public function show($id)
    {
        try {
            $transaction = StockTransaction::with(['warehouseItem', 'creator'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Hapus transaksi (dengan rollback stok)
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $transaction = StockTransaction::findOrFail($id);
            $item = $transaction->warehouseItem;

            // Rollback stok
            if ($transaction->transaction_type === 'in') {
                $item->decrement('current_stock', $transaction->quantity);
            } else {
                $item->increment('current_stock', $transaction->quantity);
            }

            $transaction->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction summary (harian/bulanan)
     */
    public function getSummary(Request $request)
    {
        try {
            $period = $request->input('period', 'daily'); // daily, monthly
            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            $query = StockTransaction::whereBetween('transaction_date', [$startDate, $endDate]);

            if ($period === 'daily') {
                $summary = $query->selectRaw('
                    transaction_date,
                    SUM(CASE WHEN transaction_type = "in" THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN transaction_type = "out" THEN quantity ELSE 0 END) as total_out,
                    SUM(CASE WHEN transaction_type = "in" THEN total_price ELSE 0 END) as total_in_value,
                    SUM(CASE WHEN transaction_type = "out" THEN total_price ELSE 0 END) as total_out_value,
                    COUNT(*) as transaction_count
                ')
                ->groupBy('transaction_date')
                ->orderBy('transaction_date', 'desc')
                ->get();
            } else {
                $summary = $query->selectRaw('
                    DATE_FORMAT(transaction_date, "%Y-%m") as month,
                    SUM(CASE WHEN transaction_type = "in" THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN transaction_type = "out" THEN quantity ELSE 0 END) as total_out,
                    SUM(CASE WHEN transaction_type = "in" THEN total_price ELSE 0 END) as total_in_value,
                    SUM(CASE WHEN transaction_type = "out" THEN total_price ELSE 0 END) as total_out_value,
                    COUNT(*) as transaction_count
                ')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->get();
            }

            // Total keseluruhan
            $totalIn = $summary->sum('total_in');
            $totalOut = $summary->sum('total_out');
            $totalInValue = $summary->sum('total_in_value');
            $totalOutValue = $summary->sum('total_out_value');
            $totalTransactions = $summary->sum('transaction_count');

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'totals' => [
                        'total_in' => $totalIn,
                        'total_out' => $totalOut,
                        'total_in_value' => $totalInValue,
                        'total_out_value' => $totalOutValue,
                        'net_value' => $totalInValue - $totalOutValue,
                        'total_transactions' => $totalTransactions
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil ringkasan transaksi'
            ], 500);
        }
    }

    /**
     * Bulk store transactions (untuk import atau multiple items)
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transactions' => 'required|array|min:1',
            'transactions.*.warehouse_item_id' => 'required|exists:warehouse_items,id',
            'transactions.*.transaction_type' => 'required|in:in,out',
            'transactions.*.quantity' => 'required|numeric|min:0.01',
            'transactions.*.unit_price' => 'required|numeric|min:0',
            'transactions.*.notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $createdTransactions = [];
            $errors = [];

            foreach ($request->transactions as $index => $transactionData) {
                try {
                    $item = WarehouseItem::find($transactionData['warehouse_item_id']);

                    // Validasi stok untuk transaksi keluar
                    if ($transactionData['transaction_type'] === 'out' && 
                        $item->current_stock < $transactionData['quantity']) {
                        $errors[] = [
                            'index' => $index,
                            'message' => "Stok {$item->name} tidak mencukupi (tersedia: {$item->current_stock})"
                        ];
                        continue;
                    }

                    $totalPrice = $transactionData['quantity'] * $transactionData['unit_price'];

                    $transaction = StockTransaction::create([
                        'warehouse_item_id' => $transactionData['warehouse_item_id'],
                        'transaction_type' => $transactionData['transaction_type'],
                        'quantity' => $transactionData['quantity'],
                        'unit_price' => $transactionData['unit_price'],
                        'total_price' => $totalPrice,
                        'reference_type' => 'other',
                        'notes' => $transactionData['notes'] ?? null,
                        'transaction_date' => now(),
                        'created_by' => auth()->id()
                    ]);

                    // Update stok
                    if ($transactionData['transaction_type'] === 'in') {
                        $item->increment('current_stock', $transactionData['quantity']);
                    } else {
                        $item->decrement('current_stock', $transactionData['quantity']);
                    }

                    $createdTransactions[] = $transaction->load('warehouseItem');

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'message' => $e->getMessage()
                    ];
                }
            }

            if (count($errors) > 0 && count($createdTransactions) === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Semua transaksi gagal diproses',
                    'errors' => $errors
                ], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdTransactions) . ' transaksi berhasil diproses',
                'data' => $createdTransactions,
                'errors' => $errors,
                'stats' => [
                    'success' => count($createdTransactions),
                    'failed' => count($errors)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses transaksi batch',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}