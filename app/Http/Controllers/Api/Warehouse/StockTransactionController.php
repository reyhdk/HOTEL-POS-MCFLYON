<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
use App\Models\WarehouseItem;
use App\Models\CashFlow; 
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PembelianExport;
use App\Exports\LaporanStokExport; // Jangan lupa panggil class Export Anda
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
            $category = $request->input('category'); // Filter Kategori Baru
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
                $query->where('transaction_date', '<=', $endDate . ' 23:59:59');
            }
            
            // Filter Kategori dari Tabel Relasi
            if ($category) {
                $query->whereHas('warehouseItem', function($q) use ($category) {
                    $q->where('category', $category);
                });
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

                CashFlow::create([
                    'transaction_date' => $request->transaction_date,
                    'type' => 'expense',
                    'category' => 'warehouse',
                    'description' => 'Pembelian Stok Gudang: ' . $item->name,
                    'payment_method' => 'Cash',
                    'amount' => $totalPrice,
                    'reference_id' => $transaction->transaction_code,
                    'user_id' => auth()->id() ?? 1
                ]);

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
            return response()->json(['success' => true, 'data' => $transaction]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan'], 404);
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

            if ($transaction->transaction_type === 'in') {
                $item->decrement('current_stock', $transaction->quantity);
            } else {
                $item->increment('current_stock', $transaction->quantity);
            }

            $transaction->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Transaksi berhasil dihapus']);

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
     * Get transaction summary
     */
    public function getSummary(Request $request)
    {
        try {
            $period = $request->input('period', 'daily'); // daily, monthly
            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            $query = StockTransaction::whereBetween('transaction_date', [$startDate, $endDate . ' 23:59:59']);

            if ($period === 'daily') {
                $summary = $query->selectRaw('
                    DATE(transaction_date) as tgl_trx,
                    SUM(CASE WHEN transaction_type = "in" THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN transaction_type = "out" THEN quantity ELSE 0 END) as total_out,
                    SUM(CASE WHEN transaction_type = "in" THEN total_price ELSE 0 END) as total_in_value,
                    SUM(CASE WHEN transaction_type = "out" THEN total_price ELSE 0 END) as total_out_value,
                    COUNT(*) as transaction_count
                ')
                ->groupBy('tgl_trx')
                ->orderBy('tgl_trx', 'desc')
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

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'totals' => [
                        'total_in' => $summary->sum('total_in'),
                        'total_out' => $summary->sum('total_out'),
                        'total_in_value' => $summary->sum('total_in_value'),
                        'total_out_value' => $summary->sum('total_out_value'),
                        'net_value' => $summary->sum('total_in_value') - $summary->sum('total_out_value'),
                        'total_transactions' => $summary->sum('transaction_count')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil ringkasan'], 500);
        }
    }

    /**
     * Bulk store transactions
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
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $createdTransactions = [];
            $errors = [];

            foreach ($request->transactions as $index => $transactionData) {
                try {
                    $item = WarehouseItem::find($transactionData['warehouse_item_id']);

                    if ($transactionData['transaction_type'] === 'out' && $item->current_stock < $transactionData['quantity']) {
                        $errors[] = ['index' => $index, 'message' => "Stok {$item->name} tidak mencukupi"];
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

                    if ($transactionData['transaction_type'] === 'in') {
                        $item->increment('current_stock', $transactionData['quantity']);
                        CashFlow::create([
                            'transaction_date' => now(),
                            'type' => 'expense',
                            'category' => 'warehouse',
                            'description' => 'Pembelian Stok Gudang (Bulk): ' . $item->name,
                            'payment_method' => 'Cash',
                            'amount' => $totalPrice,
                            'reference_id' => $transaction->transaction_code,
                            'user_id' => auth()->id() ?? 1
                        ]);
                    } else {
                        $item->decrement('current_stock', $transactionData['quantity']);
                    }

                    $createdTransactions[] = $transaction->load('warehouseItem');

                } catch (\Exception $e) {
                    $errors[] = ['index' => $index, 'message' => $e->getMessage()];
                }
            }

            if (count($errors) > 0 && count($createdTransactions) === 0) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Semua gagal', 'errors' => $errors], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdTransactions) . ' transaksi diproses',
                'data' => $createdTransactions,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memproses batch', 'error' => $e->getMessage()], 500);
        }
    }


    public function exportLaporan(Request $request)
    {
        try {
            $format    = $request->input('format', 'pdf');
            $startDate = $request->input('start_date');
            $endDate   = $request->input('end_date');
            $category  = $request->input('category');

            // ── Tentukan jenis laporan ──────────────────────────────
            $reportType = ($startDate && $endDate) ? 'detail' : 'comparison';

            // ── Logo hotel (opsional) ───────────────────────────────
            $logoPath = public_path('images/logo.png');
            $logo     = file_exists($logoPath)
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                : null;

            // ── Label periode ───────────────────────────────────────
            $periode = ($startDate && $endDate)
                ? date('d M Y', strtotime($startDate)) . ' – ' . date('d M Y', strtotime($endDate))
                : 'Semua Waktu';

            $printedAt = now()->format('d M Y H:i:s');

            if ($reportType === 'detail') {

                $query = StockTransaction::with(['warehouseItem']);

                if ($category) {
                    $query->whereHas('warehouseItem', fn($q) => $q->where('category', $category));
                }
                if ($startDate) $query->where('transaction_date', '>=', $startDate);
                if ($endDate)   $query->where('transaction_date', '<=', $endDate . ' 23:59:59');

                $transactions = $query->orderBy('transaction_date', 'desc')->get();

                $summary = [
                    'total_in_value'     => $transactions->where('transaction_type', 'in')->sum('total_price'),
                    'total_out_value'    => $transactions->where('transaction_type', 'out')->sum('total_price'),
                    'total_transactions' => $transactions->count(),
                ];

                if ($format === 'excel') {
                    return Excel::download(
                        new LaporanStokExport($transactions, 'detail', $startDate, $endDate, $summary),
                        'Laporan_Stok_Gudang_' . now()->format('Ymd') . '.xlsx'
                    );
                }

                $pdf = Pdf::loadView('exports.laporan-stok', [
                    'reportType'   => 'detail',
                    'transactions' => $transactions,
                    'summary'      => $summary,
                    'periode'      => $periode,
                    'printed_at'   => $printedAt,
                    'logo'         => $logo,
                ])->setPaper('A4', 'landscape');

                return $pdf->download('Laporan_Stok_Gudang_' . now()->format('Ymd') . '.pdf');
            }

            // --- REPORT COMPARISON (REKAP STOK SAAT INI) ---
            // Stok Awal dihapus dari query untuk efisiensi
            $items = DB::table('warehouse_items as wi')
                ->leftJoin(
                    DB::raw('(
                        SELECT
                            warehouse_item_id,
                            SUM(CASE WHEN transaction_type = "in"  THEN quantity ELSE 0 END) AS total_in,
                            SUM(CASE WHEN transaction_type = "out" THEN quantity ELSE 0 END) AS total_out
                        FROM stock_transactions
                        GROUP BY warehouse_item_id
                    ) as st'),
                    'wi.id', '=', 'st.warehouse_item_id'
                )
                ->select(
                    'wi.id',
                    'wi.code',
                    'wi.name',
                    'wi.category',
                    'wi.unit',
                    'wi.current_stock',
                    'wi.min_stock',
                    'wi.cost_price',
                    'wi.is_active',
                    DB::raw('COALESCE(st.total_in,  0) AS total_in'),
                    DB::raw('COALESCE(st.total_out, 0) AS total_out')
                )
                ->when($category, fn($q) => $q->where('wi.category', $category))
                ->orderBy('wi.category')
                ->orderBy('wi.name')
                ->get();

            // Summary keseluruhan
            $summary = [
                'total_items'        => $items->count(),
                'low_stock_count'    => $items->filter(fn($i) => $i->current_stock > 0 && $i->current_stock <= $i->min_stock)->count(),
                'out_of_stock_count' => $items->filter(fn($i) => $i->current_stock <= 0)->count(),
                'total_asset_value'  => $items->sum(fn($i) => $i->current_stock * $i->cost_price),
            ];

            if ($format === 'excel') {
                return Excel::download(
                    new LaporanStokExport($items, 'comparison', null, null, $summary),
                    'Laporan_Status_Stok_Gudang_' . now()->format('Ymd') . '.xlsx'
                );
            }

            $pdf = Pdf::loadView('exports.laporan-stok', [
                'reportType' => 'comparison',
                'items'      => $items,
                'summary'    => $summary,
                'periode'    => $periode,
                'printed_at' => $printedAt,
                'logo'       => $logo,
            ])->setPaper('A4', 'landscape');

            return $pdf->download('Laporan_Status_Stok_Gudang_' . now()->format('Ymd') . '.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor laporan stok',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}