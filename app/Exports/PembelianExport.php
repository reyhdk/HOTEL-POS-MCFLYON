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

class PembelianExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    protected $transactions;
    protected $startDate;
    protected $endDate;
    protected $totalValue;

    public function __construct($transactions, $startDate, $endDate, $totalValue)
    {
        $this->transactions = $transactions;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->totalValue = $totalValue;
    }

    /**
     * Mapping data database menjadi baris dan kolom murni Excel
     */
    public function array(): array
    {
        $data = [];
        $totalQty = 0;
        
        foreach ($this->transactions as $trx) {
            $totalQty += $trx->quantity;
            $data[] = [
                date('Y-m-d H:i', strtotime($trx->transaction_date)),
                $trx->transaction_code,
                $trx->warehouseItem ? $trx->warehouseItem->name : 'Barang Dihapus',
                $trx->warehouseItem ? $trx->warehouseItem->category : '-',
                $trx->quantity,
                $trx->warehouseItem ? $trx->warehouseItem->unit : '-',
                $trx->unit_price,
                $trx->total_price,
                $trx->creator ? $trx->creator->name : 'Sistem',
                $trx->notes ?? '-'
            ];
        }

        // Beri jarak kosong 1 baris untuk pemisah antara data dan total
        $data[] = ['', '', '', '', '', '', '', '', '', ''];

        // Tambahkan Baris Summary / Total di paling bawah
        $data[] = ['', '', 'TOTAL KESELURUHAN', '', $totalQty, '', '', $this->totalValue, '', ''];

        return $data;
    }

    /**
     * Judul Laporan (Header Informasi) & Header Kolom Tabel
     */
    public function headings(): array
    {
        // Tentukan teks periode
        $periode = 'Semua Waktu';
        if ($this->startDate && $this->endDate) {
            $periode = date('d M Y', strtotime($this->startDate)) . ' - ' . date('d M Y', strtotime($this->endDate));
        }

        return [
            // Baris 1: Judul Utama
            ['LAPORAN PEMBELIAN BARANG (STOK MASUK GUDANG)'],
            // Baris 2: Periode
            ['Periode: ' . $periode],
            // Baris 3: Info Waktu Cetak
            ['Dicetak pada: ' . now()->format('d M Y H:i:s')],
            // Baris 4: Spasi Kosong
            [''], 
            // Baris 5: Header Kolom Tabel
            [
                'Tanggal Masuk',
                'Kode Transaksi',
                'Nama Barang',
                'Kategori',
                'Qty Masuk',
                'Satuan',
                'Harga Satuan (Rp)',
                'Total Harga (Rp)',
                'Diinput Oleh',
                'Catatan'
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
        
        // Menggabungkan (Merge) kolom A sampai J (10 Kolom) agar teks berada di tengah
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');

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
        $sheet->getStyle('A5:J5')->applyFromArray([
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
            $sheet->getStyle('A6:J' . $lastDataRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFEEEEEE'],
                    ],
                ],
            ]);
            
            // Align center untuk Qty, Satuan, dan Kategori
            $sheet->getStyle('D6:F' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Hitung baris Summary
        // $lastDataRow + 1 = baris kosong (spasi)
        $summaryRow = $lastDataRow + 2; 
        
        // Highlight Row Total Pembelian
        $sheet->getStyle('C' . $summaryRow . ':H' . $summaryRow)->applyFromArray([
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

        // Alignment Total Teks ke Kanan
        $sheet->getStyle('C' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Qty total

        return [];
    }

    /**
     * Mengatur format angka pada kolom nominal agar ada titik ribuan di Excel
     */
    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER, // Qty
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Harga Satuan
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total Harga
        ];
    }
}