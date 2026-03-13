<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashFlow;
use App\Models\Setting; // <-- Pastikan model Setting di-import
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CashFlowExport;

class CashFlowController extends Controller
{
    /**
     * Membangun Query berdasarkan Filter (Digunakan ulang di index & export)
     */
    private function buildFilterQuery(Request $request)
    {
        $query = CashFlow::query()->orderBy('transaction_date', 'desc');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }

        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_id', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->buildFilterQuery($request);

        $summaryQuery = clone $query;
        $totalIncome = (clone $summaryQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $summaryQuery)->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        $transactions = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'summary' => [
                'totalIncome' => $totalIncome,
                'totalExpense' => $totalExpense,
                'netBalance' => $netBalance
            ],
            'transactions' => $transactions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category' => 'required|in:booking,resto,laundry,warehouse,other',
            'description' => 'required|string|max:255',
            'payment_method' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'reference_id' => 'nullable|string|max:100',
        ]);

        $validated['user_id'] = auth('api')->id() ?? 1;
        $cashFlow = CashFlow::create($validated);

        return response()->json([
            'message' => 'Transaksi kas berhasil dicatat',
            'data' => $cashFlow
        ], 201);
    }

    /**
     * Export Data Cash Flow ke Excel (.xlsx) atau PDF
     */
    public function export(Request $request)
    {
        $query = $this->buildFilterQuery($request);
        $transactions = $query->get();

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        $format = $request->query('format', 'excel'); // Default ke excel

        if ($format === 'excel' || $format === 'csv') {
            $filename = 'Laporan_Arus_Kas_' . date('Ymd_His') . '.xlsx';
            return Excel::download(new CashFlowExport($transactions, $totalIncome, $totalExpense, $netBalance, $request), $filename);
        } elseif ($format === 'pdf') {
            return $this->exportToPdf($transactions, $totalIncome, $totalExpense, $netBalance, $request);
        }

        return response()->json(['message' => 'Format tidak didukung'], 400);
    }

    private function exportToPdf($transactions, $totalIncome, $totalExpense, $netBalance, $request)
    {
        $periode = 'Semua Waktu';
        if ($request->has('start_date') && $request->has('end_date')) {
            $periode = date('d M Y', strtotime($request->start_date)) . ' - ' . date('d M Y', strtotime($request->end_date));
        }

        // --- MENGAMBIL LOGO DARI DATABASE SETTINGS ---
        $logoBase64 = null;
        try {
            // Asumsi model Setting dan mengambil row pertama
            $setting = Setting::first(); 
            
            if ($setting && $setting->logo) {
                // Sesuaikan path ini. Biasanya file diupload ke folder public/storage/ atau public/
                // Jika pakai storage link: public_path('storage/' . $setting->logo)
                // Jika langsung di public: public_path($setting->logo)
                $logoPath = public_path('storage/' . $setting->logo); 
                
                if (file_exists($logoPath)) {
                    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                    $dataImage = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($dataImage);
                }
            }
        } catch (\Exception $e) {
            // Abaikan jika error mengambil logo, PDF tetap akan dirender tanpa logo
        }

        $data = [
            'transactions' => $transactions,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netBalance' => $netBalance,
            'periode' => $periode,
            'printed_at' => now()->format('d M Y H:i:s'),
            'logo' => $logoBase64 // Kirim variabel logo ke Blade
        ];

        $pdf = Pdf::loadView('exports.cashflow', $data)->setPaper('a4', 'landscape');
        return $pdf->download('Laporan_Arus_Kas_' . date('Ymd_His') . '.pdf');
    }
}