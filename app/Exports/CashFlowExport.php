<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CashFlowExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    protected $transactions;
    protected $totalIncome;
    protected $totalExpense;
    protected $netBalance;
    protected $request;

    public function __construct($transactions, $totalIncome, $totalExpense, $netBalance, $request)
    {
        $this->transactions = $transactions;
        $this->totalIncome = $totalIncome;
        $this->totalExpense = $totalExpense;
        $this->netBalance = $netBalance;
        $this->request = $request;
    }

    /**
     * Mapping data database menjadi baris dan kolom murni Excel
     */
    public function array(): array
    {
        $data = [];
        
        foreach ($this->transactions as $trx) {
            $data[] = [
                date('Y-m-d H:i', strtotime($trx->transaction_date)),
                $trx->type == 'income' ? 'Pemasukan' : 'Pengeluaran',
                ucfirst($trx->category),
                $trx->description,
                $trx->payment_method . ($trx->reference_id ? ' (Ref: ' . $trx->reference_id . ')' : ''),
                
                // Pisahkan nominal masuk & keluar agar rapi secara kolom
                $trx->type == 'income' ? $trx->amount : 0,
                $trx->type == 'expense' ? $trx->amount : 0,
            ];
        }

        // Beri jarak kosong 1 baris untuk pemisah antara data dan total
        $data[] = ['', '', '', '', '', '', ''];

        // Tambahkan Baris Summary / Total di paling bawah
        $data[] = ['', '', '', 'TOTAL PEMASUKAN', '', $this->totalIncome, ''];
        $data[] = ['', '', '', 'TOTAL PENGELUARAN', '', '', $this->totalExpense];
        $data[] = ['', '', '', 'SALDO BERSIH', '', $this->netBalance, ''];

        return $data;
    }

    /**
     * Judul Laporan (Header Informasi) & Header Kolom Tabel
     */
    public function headings(): array
    {
        // Tentukan teks periode
        $periode = 'Semua Waktu';
        if ($this->request->has('start_date') && $this->request->has('end_date')) {
            $periode = date('d M Y', strtotime($this->request->start_date)) . ' - ' . date('d M Y', strtotime($this->request->end_date));
        }

        return [
            // Baris 1: Judul Utama
            ['LAPORAN ARUS KAS HOTEL'],
            // Baris 2: Periode
            ['Periode: ' . $periode],
            // Baris 3: Info Waktu Cetak
            ['Dicetak pada: ' . now()->format('d M Y H:i:s')],
            // Baris 4: Spasi Kosong
            [''], 
            // Baris 5: Header Kolom Tabel
            [
                'Tanggal',
                'Tipe',
                'Kategori',
                'Deskripsi',
                'Metode & Referensi',
                'Uang Masuk (Rp)',
                'Uang Keluar (Rp)'
            ]
        ];
    }

    /**
     * Merapikan Gaya (Styling) Teks
     */
    public function styles(Worksheet $sheet)
    {
        // ---------------------------------------------------------
        // 1. STYLING INFORMASI LAPORAN (Baris 1 - 3)
        // ---------------------------------------------------------
        
        // Menggabungkan (Merge) kolom A sampai G agar teks berada di tengah dokumen
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');

        // Style untuk Judul Laporan
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FFF68B1E'], // Teks Oranye
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Style untuk Sub-Judul (Periode & Waktu Cetak)
        $sheet->getStyle('A2:A3')->applyFromArray([
            'font' => [
                'size' => 11,
                'italic' => true,
                'color' => ['argb' => 'FF555555'], // Abu-abu gelap
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // ---------------------------------------------------------
        // 2. STYLING HEADER KOLOM TABEL (Baris 5)
        // ---------------------------------------------------------
        $sheet->getStyle('A5:G5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF68B1E'], // Background Oranye
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFE0E0E0'],
                ],
            ],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(25);

        // ---------------------------------------------------------
        // 3. STYLING AREA DATA & SUMMARY
        // ---------------------------------------------------------
        
        // Hitung batas baris: Header ada 5 baris + jumlah data
        $lastDataRow = 5 + count($this->transactions);

        // Styling tabel (border & posisi tengah) jika ada data
        if (count($this->transactions) > 0) {
            $sheet->getStyle('A6:G' . $lastDataRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFEEEEEE'],
                    ],
                ],
            ]);
            $sheet->getStyle('A6:C' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Hitung baris Summary
        // $lastDataRow + 1 = baris kosong (spasi)
        $summaryStartRow = $lastDataRow + 2; 
        
        // Tebalkan teks Total
        $sheet->getStyle('D' . $summaryStartRow . ':G' . ($summaryStartRow + 1))->applyFromArray([
            'font' => ['bold' => true],
        ]);

        // Highlight "Saldo Bersih" (Baris paling bawah)
        $saldoRow = $summaryStartRow + 2;
        $sheet->getStyle('D' . $saldoRow . ':G' . $saldoRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF68B1E'], // Background Oranye
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FFD87614'], // Border Oranye Gelap
                ],
            ],
        ]);

        return [];
    }

    /**
     * Mengatur format angka pada kolom nominal agar ada titik ribuan di Excel
     * (Contoh: 1500000 -> 1,500,000)
     */
    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}