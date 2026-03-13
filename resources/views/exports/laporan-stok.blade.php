<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Stok Gudang</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }

        /* ---- HEADER ---- */
        .header-table { width: 100%; margin-bottom: 18px; border-bottom: 2px solid #F68B1E; padding-bottom: 10px; }
        .header-table td { border: none; padding: 0; }
        .header-title { text-align: center; }
        .header-title h2 { margin: 0; color: #F68B1E; font-size: 19px; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-title p  { margin: 4px 0 0; color: #555; font-size: 11px; }
        .header-title .print-date { font-size: 10px; color: #999; font-style: italic; margin-top: 2px; }

        /* ---- TABLE ---- */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px 7px; text-align: left; vertical-align: middle; }
        th { background-color: #F68B1E; color: #fff; font-weight: bold; font-size: 10px; text-align: center; }
        tr:nth-child(even) td { background-color: #FFF8F0; }

        /* ---- BADGE TIPE TRANSAKSI ---- */
        .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-masuk  { background-color: #d4edda; color: #155724; }
        .badge-keluar { background-color: #f8d7da; color: #721c24; }
        .badge-revisi { background-color: #cce5ff; color: #004085; }

        /* ---- BADGE STATUS STOK ---- */
        .badge-habis   { background-color: #f8d7da; color: #721c24; }
        .badge-defisit { background-color: #fff3cd; color: #856404; }
        .badge-surplus { background-color: #cce5ff; color: #004085; }
        .badge-normal  { background-color: #d4edda; color: #155724; }

        /* ---- ALIGNMENT ---- */
        .text-right  { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-success { color: #28a745; font-weight: bold; }
        .text-danger  { color: #dc3545; font-weight: bold; }
        .text-primary { color: #0d6efd; font-weight: bold; }
        .text-muted   { color: #888; }

        /* ---- SUMMARY BOX ---- */
        .summary-wrap { margin-top: 20px; overflow: hidden; }
        .summary-box  { float: right; width: 330px; }
        .summary-box table th { background: #fff; color: #333; border: none; padding: 5px 8px; text-align: left; font-size: 11px; }
        .summary-box table td { border: none; padding: 5px 8px; text-align: right; font-weight: bold; font-size: 11px; }
        .summary-total    { background-color: #F68B1E !important; }
        .summary-total th, .summary-total td { color: #fff !important; font-size: 13px !important; border: none !important; }

        /* ---- KETERANGAN / LEGEND BOX ---- */
        .legend-box { margin-top: 22px; clear: both; border: 1px solid #e9e0d5; border-radius: 6px; padding: 10px 14px; background-color: #fffaf5; }
        .legend-box .legend-title { font-weight: bold; color: #F68B1E; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .legend-grid { width: 100%; border: none; margin-top: 0; }
        .legend-grid td { border: none; padding: 3px 8px 3px 0; font-size: 10px; vertical-align: top; width: 50%; background: none !important; }
        .legend-grid .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; vertical-align: middle; }

        /* ---- FOOTER ---- */
        .footer { position: fixed; bottom: -10px; width: 100%; text-align: center;
                  font-size: 9px; color: #aaa; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>

    {{-- ============= HEADER ============= --}}
    <table class="header-table">
        <tr>
            <td style="width:20%; text-align:left; vertical-align:middle;">
                @if(!empty($logo))
                    <img src="{{ $logo }}" style="max-height:60px; max-width:150px; object-fit:contain;">
                @endif
            </td>
            <td style="width:60%; vertical-align:middle;" class="header-title">
                <h2>
                    @if($reportType === 'comparison')
                        Rekap Status Stok Gudang
                    @else
                        Riwayat Transaksi Stok Gudang
                    @endif
                </h2>
                <p>Periode: <strong>{{ $periode }}</strong></p>
                <p class="print-date">Dicetak: {{ $printed_at }}</p>
            </td>
            <td style="width:20%;"></td>
        </tr>
    </table>


    {{-- ============================================================ --}}
    {{-- LAPORAN RIWAYAT TRANSAKSI --}}
    {{-- ============================================================ --}}
    @if($reportType === 'detail')

        <table>
            <thead>
                <tr>
                    <th style="width:11%">Tanggal</th>
                    <th style="width:10%">Kode Trx</th>
                    <th style="width:17%">Nama Barang</th>
                    <th style="width:9%">Kategori</th>
                    <th style="width:10%">Tipe</th>
                    <th style="width:8%">Qty</th>
                    <th style="width:11%" class="text-right">Harga Satuan</th>
                    <th style="width:12%" class="text-right">Total Nilai</th>
                    <th style="width:12%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                    @php
                        $isAdj = str_starts_with($trx->transaction_code ?? '', 'ADJ/');
                        if ($isAdj) {
                            $badgeClass = 'badge-revisi';
                            $tipeLabel  = 'Revisi (' . ($trx->transaction_type === 'in' ? '+' : '-') . ')';
                            $valueClass = 'text-primary';
                        } elseif ($trx->transaction_type === 'in') {
                            $badgeClass = 'badge-masuk';
                            $tipeLabel  = 'Barang Masuk';
                            $valueClass = 'text-success';
                        } else {
                            $badgeClass = 'badge-keluar';
                            $tipeLabel  = 'Barang Keluar';
                            $valueClass = 'text-danger';
                        }
                    @endphp
                    <tr>
                        <td class="text-center">
                            {{ date('d M Y', strtotime($trx->transaction_date)) }}<br>
                            <span class="text-muted" style="font-size:9px;">{{ date('H:i', strtotime($trx->transaction_date)) }}</span>
                        </td>
                        <td class="text-center text-muted" style="font-size:9px;">{{ $trx->transaction_code ?? '-' }}</td>
                        <td><strong>{{ $trx->warehouse_item->name ?? 'Barang Dihapus' }}</strong></td>
                        <td class="text-center">{{ $trx->warehouse_item->category ?? '-' }}</td>
                        <td class="text-center"><span class="badge {{ $badgeClass }}">{{ $tipeLabel }}</span></td>
                        <td class="text-center">{{ $trx->quantity }} {{ $trx->warehouse_item->unit ?? '' }}</td>
                        <td class="text-right">Rp {{ number_format($trx->unit_price, 0, ',', '.') }}</td>
                        <td class="text-right {{ $valueClass }}">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                        <td style="font-size:9px; color:#666;">{{ Str::limit($trx->notes ?? '-', 45) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center" style="padding:20px; color:#888;">
                            Tidak ada transaksi pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="summary-wrap">
            <div class="summary-box">
                <table>
                    <tr>
                        <th>Total Nilai Masuk:</th>
                        <td class="text-success">Rp {{ number_format($summary['total_in_value'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Total Nilai Keluar:</th>
                        <td class="text-danger">Rp {{ number_format($summary['total_out_value'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Total Transaksi:</th>
                        <td>{{ $summary['total_transactions'] ?? 0 }} transaksi</td>
                    </tr>
                    <tr class="summary-total">
                        <th>Selisih Bersih:</th>
                        <td>Rp {{ number_format(($summary['total_in_value'] ?? 0) - ($summary['total_out_value'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Keterangan Tipe Transaksi --}}
        <div class="legend-box">
            <div class="legend-title">Keterangan Tipe Transaksi</div>
            <table class="legend-grid">
                <tr>
                    <td><span class="badge badge-masuk">Barang Masuk</span> &nbsp;Stok bertambah — barang dibeli atau diterima dari supplier</td>
                    <td><span class="badge badge-keluar">Barang Keluar</span> &nbsp;Stok berkurang — barang dipakai operasional hotel (dapur, laundry, dll)</td>
                </tr>
                <tr>
                    <td><span class="badge badge-revisi">Revisi (+/-)</span> &nbsp;Koreksi stok manual oleh admin (misal: hasil opname fisik)</td>
                    <td style="color:#888; font-style:italic;">Selisih Bersih = Total Nilai Masuk dikurangi Total Nilai Keluar</td>
                </tr>
            </table>
        </div>

    @endif


    {{-- ============================================================ --}}
    {{-- LAPORAN REKAP STATUS STOK (TANPA STOK AWAL) --}}
    {{-- ============================================================ --}}
    @if($reportType === 'comparison')

        <table>
            <thead>
                <tr>
                    <!-- Total 100% -->
                    <th style="width:6%">Kode</th>
                    <th style="width:20%">Nama Barang</th>
                    <th style="width:11%">Kategori</th>
                    <th style="width:6%">Sat.</th>
                    <th style="width:9%" class="text-right">Total Masuk</th>
                    <th style="width:9%" class="text-right">Total Keluar</th>
                    <th style="width:9%" class="text-right">Stok Skrg</th>
                    <th style="width:8%" class="text-right">Stok Min</th>
                    <th style="width:12%" class="text-right">Nilai Aset</th>
                    <th style="width:10%" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $currentStock = floatval($item->current_stock);
                        $minStock     = floatval($item->min_stock);
                        $totalIn      = floatval($item->total_in  ?? 0);
                        $totalOut     = floatval($item->total_out ?? 0);
                        $assetValue   = $currentStock * floatval($item->cost_price ?? 0);

                        if ($currentStock <= 0) {
                            $statusLabel = 'Habis';
                            $statusClass = 'badge-habis';
                        } elseif ($currentStock <= $minStock) {
                            $statusLabel = 'Perlu Restock';
                            $statusClass = 'badge-defisit';
                        } elseif ($currentStock > $minStock * 2) {
                            $statusLabel = 'Stok Banyak';
                            $statusClass = 'badge-surplus';
                        } else {
                            $statusLabel = 'Aman';
                            $statusClass = 'badge-normal';
                        }
                    @endphp
                    <tr>
                        <td class="text-center text-muted" style="font-size:9px;">{{ $item->code }}</td>
                        <td><strong>{{ $item->name }}</strong></td>
                        <td class="text-center">{{ $item->category }}</td>
                        <td class="text-center">{{ $item->unit }}</td>
                        <td class="text-right text-success">+{{ number_format($totalIn, 2, ',', '.') }}</td>
                        <td class="text-right text-danger">-{{ number_format($totalOut, 2, ',', '.') }}</td>
                        <td class="text-right" style="font-weight:bold; color:#0d1a59;">{{ number_format($currentStock, 2, ',', '.') }}</td>
                        <td class="text-right text-muted">{{ number_format($minStock, 2, ',', '.') }}</td>
                        <td class="text-right" style="color:#5a3e00;">Rp {{ number_format($assetValue, 0, ',', '.') }}</td>
                        <td class="text-center"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center" style="padding:20px; color:#888;">
                            Tidak ada data barang di gudang.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="summary-wrap">
            <div class="summary-box">
                <table>
                    <tr>
                        <th>Total Jenis Barang:</th>
                        <td>{{ $summary['total_items'] ?? 0 }} item</td>
                    </tr>
                    <tr>
                        <th>Perlu Restock:</th>
                        <td class="text-danger">{{ $summary['low_stock_count'] ?? 0 }} item</td>
                    </tr>
                    <tr>
                        <th>Stok Habis:</th>
                        <td class="text-danger">{{ $summary['out_of_stock_count'] ?? 0 }} item</td>
                    </tr>
                    <tr class="summary-total">
                        <th>Total Nilai Aset Gudang:</th>
                        <td>Rp {{ number_format($summary['total_asset_value'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Keterangan Status Stok --}}
        <div class="legend-box">
            <div class="legend-title">Panduan Membaca Status Stok</div>
            <table class="legend-grid">
                <tr>
                    <td>
                        <span class="badge badge-normal">Aman</span>
                        &nbsp;Jumlah stok mencukupi untuk kebutuhan normal. Tidak perlu tindakan segera.
                    </td>
                    <td>
                        <span class="badge badge-defisit">Perlu Restock</span>
                        &nbsp;Stok sudah menyentuh atau di bawah batas minimum. Segera lakukan pembelian.
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="badge badge-surplus">Stok Banyak</span>
                        &nbsp;Stok melebihi 2× batas minimum. Pertimbangkan untuk tidak membeli dulu.
                    </td>
                    <td>
                        <span class="badge badge-habis">Habis</span>
                        &nbsp;Stok sudah nol. Barang tidak tersedia — segera lakukan pengadaan.
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="color:#888; font-style:italic; padding-top:5px;">
                        Stok Minimum = batas paling rendah yang ditetapkan oleh admin untuk masing-masing barang.
                        Nilai Aset = Stok Sekarang × Harga Satuan Beli.
                    </td>
                </tr>
            </table>
        </div>

    @endif

    {{-- ============= FOOTER ============= --}}
    <div class="footer">
        Aplikasi Manajemen Hotel &copy; {{ date('Y') }} &nbsp;|&nbsp; Dokumen ini digenerate otomatis oleh sistem
    </div>

</body>
</html>