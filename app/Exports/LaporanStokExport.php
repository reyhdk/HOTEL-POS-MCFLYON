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

class LaporanStokExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    protected $data;
    protected $reportType;
    protected $startDate;
    protected $endDate;
    protected $summary;

    /**
     * @param mixed  $data        Collection transaksi (detail) atau warehouse_items (comparison)
     * @param string $reportType  'detail' | 'comparison'
     * @param string $startDate
     * @param string $endDate
     * @param array  $summary     Ringkasan untuk baris footer
     */
    public function __construct($data, string $reportType = 'detail', $startDate = null, $endDate = null, array $summary = [])
    {
        $this->data       = $data;
        $this->reportType = $reportType;
        $this->startDate  = $startDate;
        $this->endDate    = $endDate;
        $this->summary    = $summary;
    }

    // =========================================================
    // ARRAY DATA
    // =========================================================

    public function array(): array
    {
        return $this->reportType === 'comparison'
            ? $this->buildComparisonArray()
            : $this->buildDetailArray();
    }

    /**
     * Laporan I – Semua Alur Gudang (detail per transaksi)
     */
    private function buildDetailArray(): array
    {
        $rows = [];

        foreach ($this->data as $trx) {
            $isAdj = str_starts_with($trx->transaction_code ?? '', 'ADJ/');
            if ($isAdj) {
                $tipeLabel = 'Stok Revisi (' . ($trx->transaction_type === 'in' ? '+' : '-') . ')';
            } else {
                $tipeLabel = $trx->transaction_type === 'in' ? 'Barang Masuk' : 'Barang Keluar';
            }

            $rows[] = [
                date('Y-m-d H:i', strtotime($trx->transaction_date)),
                $trx->transaction_code ?? '-',
                $trx->warehouse_item->name   ?? 'Barang Dihapus',
                $trx->warehouse_item->category ?? '-',
                $tipeLabel,
                $trx->quantity . ' ' . ($trx->warehouse_item->unit ?? ''),
                $trx->unit_price,
                $trx->total_price,
                $trx->notes ?? '-',
            ];
        }

        // Spasi sebelum summary
        $rows[] = array_fill(0, 9, '');

        // Footer
        $rows[] = ['', '', '', '', 'TOTAL NILAI MASUK',  '', '', $this->summary['total_in_value']  ?? 0, ''];
        $rows[] = ['', '', '', '', 'TOTAL NILAI KELUAR', '', '', $this->summary['total_out_value'] ?? 0, ''];
        $rows[] = ['', '', '', '', 'SELISIH BERSIH',     '', '', ($this->summary['total_in_value'] ?? 0) - ($this->summary['total_out_value'] ?? 0), ''];

        return $rows;
    }

    /**
     * Laporan II – Perbandingan Stok (semua waktu) - TANPA STOK AWAL
     * Kolom: Kode | Nama | Kategori | Satuan | Total Masuk | Total Keluar | Stok Sekarang | Stok Min | Nilai Aset | Status
     */
    private function buildComparisonArray(): array
    {
        $rows = [];

        foreach ($this->data as $item) {
            $currentStock = floatval($item->current_stock);
            $minStock     = floatval($item->min_stock);
            $totalIn      = floatval($item->total_in  ?? 0);
            $totalOut     = floatval($item->total_out ?? 0);
            $costPrice    = floatval($item->cost_price ?? 0);

            if ($currentStock <= 0) {
                $status = 'Habis';
            } elseif ($currentStock <= $minStock) {
                $status = 'Defisit (Stok Rendah)';
            } elseif ($currentStock > $minStock * 2) {
                $status = 'Surplus';
            } else {
                $status = 'Normal';
            }

            $rows[] = [
                $item->code,
                $item->name,
                $item->category,
                $item->unit,
                $totalIn,               // Total Masuk (akumulasi)
                $totalOut,              // Total Keluar (akumulasi)
                $currentStock,          // Stok Sekarang
                $minStock,              // Stok Minimum
                $currentStock * $costPrice, // Nilai Aset
                $status,
            ];
        }

        // Spasi
        $rows[] = array_fill(0, 10, '');

        // Footer
        $rows[] = ['', '', '', '', '', '', '', 'TOTAL ASET GUDANG', $this->summary['total_asset_value'] ?? 0, ''];
        $rows[] = ['', '', 'JUMLAH BARANG',      $this->summary['total_items']        ?? 0, '', '', '', '', '', ''];
        $rows[] = ['', '', 'STOK RENDAH',        $this->summary['low_stock_count']    ?? 0, '', '', '', '', '', ''];
        $rows[] = ['', '', 'STOK HABIS',         $this->summary['out_of_stock_count'] ?? 0, '', '', '', '', '', ''];

        return $rows;
    }

    // =========================================================
    // HEADINGS (5 baris: judul, periode, dicetak, spasi, header kolom)
    // =========================================================

    public function headings(): array
    {
        $periode = 'Semua Waktu';
        if ($this->startDate && $this->endDate) {
            $periode = date('d M Y', strtotime($this->startDate)) . ' – ' . date('d M Y', strtotime($this->endDate));
        }

        $judul = $this->reportType === 'comparison'
            ? 'LAPORAN REKAP STATUS STOK GUDANG'
            : 'LAPORAN ALUR TRANSAKSI STOK GUDANG';

        $headerKolom = $this->reportType === 'comparison'
            ? ['Kode', 'Nama Barang', 'Kategori', 'Satuan', 'Total Masuk', 'Total Keluar', 'Stok Sekarang', 'Stok Minimum', 'Nilai Aset (Rp)', 'Status']
            : ['Tanggal', 'Kode Trx', 'Nama Barang', 'Kategori', 'Tipe', 'Qty', 'Harga Satuan (Rp)', 'Total Nilai (Rp)', 'Keterangan'];

        return [
            [$judul],
            ['Periode: ' . $periode],
            ['Dicetak pada: ' . now()->format('d M Y H:i:s')],
            [''],
            $headerKolom,
        ];
    }

    // =========================================================
    // STYLES
    // =========================================================

    public function styles(Worksheet $sheet)
    {
        // Karena Stok Awal dihapus, Kolom Comparison sekarang A sampai J. Detail A sampai I.
        $lastCol     = $this->reportType === 'comparison' ? 'J' : 'I';
        $dataCount   = count($this->data);
        $lastDataRow = 5 + $dataCount;

        // ── Merge baris info laporan ──────────────────────────
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->mergeCells("A3:{$lastCol}3");

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFF68B1E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->getStyle('A2:A3')->applyFromArray([
            'font'      => ['size' => 11, 'italic' => true, 'color' => ['argb' => 'FF555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF68B1E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE0E0E0']]],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(25);

        if ($dataCount > 0) {
            $sheet->getStyle("A6:{$lastCol}{$lastDataRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFEEEEEE']]],
            ]);

            for ($row = 6; $row <= $lastDataRow; $row++) {
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF8F0']],
                    ]);
                }
            }

            if ($this->reportType === 'detail') {
                $sheet->getStyle("A6:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E6:F{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G6:H{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                for ($row = 6; $row <= $lastDataRow; $row++) {
                    $val = (string) $sheet->getCell("E{$row}")->getValue();
                    $argb = match(true) {
                        str_contains($val, 'Masuk')  => 'FF28A745',
                        str_contains($val, 'Keluar') => 'FFDC3545',
                        str_contains($val, 'Revisi') => 'FF0D6EFD',
                        default                      => 'FF333333',
                    };
                    $sheet->getStyle("E{$row}")->getFont()->getColor()->setARGB($argb);
                    $sheet->getStyle("E{$row}")->getFont()->setBold(true);
                }
            }

            if ($this->reportType === 'comparison') {
                // Kolom E (Total Masuk) s/d I (Nilai Aset) Rata Kanan
                $sheet->getStyle("E6:I{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                // Kolom J (Status) Rata Tengah
                $sheet->getStyle("J6:J{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                for ($row = 6; $row <= $lastDataRow; $row++) {
                    $status = (string) $sheet->getCell("J{$row}")->getValue();
                    $argb = match(true) {
                        str_contains($status, 'Habis')   => 'FFDC3545',
                        str_contains($status, 'Defisit') => 'FFFD7E14',
                        str_contains($status, 'Surplus') => 'FF0D6EFD',
                        default                          => 'FF28A745',
                    };
                    $sheet->getStyle("J{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => $argb]],
                    ]);
                }
            }
        }

        $summaryStart = $lastDataRow + 2;

        $sheet->getStyle("A{$summaryStart}:{$lastCol}" . ($summaryStart + 2))->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $totalRow = $summaryStart + 2;
        $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
            'font'    => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF68B1E']],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFD87614']]],
        ]);

        return [];
    }

    // =========================================================
    // COLUMN FORMATS
    // =========================================================

    public function columnFormats(): array
    {
        // Laporan II: kolom E (Total Masuk) s/d I (Nilai Aset) → format angka ribuan
        if ($this->reportType === 'comparison') {
            return [
                'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            ];
        }

        // Laporan I: kolom G (Harga Satuan) dan H (Total Nilai)
        return [
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}