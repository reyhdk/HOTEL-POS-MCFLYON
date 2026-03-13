<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pembelian Barang Gudang</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        
        /* Modifikasi Header */
        .header-table { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #F68B1E; padding-bottom: 10px; }
        .header-table td { border: none; padding: 0; }
        .header-title { text-align: center; }
        .header-title h2 { margin: 0; color: #F68B1E; font-size: 22px; text-transform: uppercase; }
        .header-title p { margin: 5px 0 0 0; color: #555; font-size: 12px;}
        .header-title .print-date { font-size: 10px; color: #777; font-style: italic; margin-top: 3px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 7px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; color: #444; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-orange { color: #F68B1E; }
        .fw-bold { font-weight: bold; }
        
        .summary-box { margin-top: 20px; width: 320px; float: right; }
        .summary-box table th { background-color: #fff; border: none; padding: 5px; text-align: left;}
        .summary-box table td { border: none; padding: 5px; text-align: right; font-weight: bold; }
        .border-top { border-top: 2px solid #333 !important; }
        
        .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>

    @php
        $periode = 'Semua Waktu';
        if ($startDate && $endDate) {
            $periode = date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate));
        }
        
        // Kalkulasi Summary Tambahan
        $totalQty = $transactions->sum('quantity');
        $totalTrx = $transactions->count();
    @endphp

    <!-- HEADER RESMI MENGGUNAKAN TABEL -->
    <table class="header-table">
        <tr>
            <!-- Kolom Logo (Kiri) -->
            <td style="width: 25%; text-align: left; vertical-align: middle;">
                @if(isset($logo) && !empty($logo))
                    <img src="{{ $logo }}" style="max-height: 60px; max-width: 160px; object-fit: contain;">
                @else
                    <h1 style="color: #444; margin:0; font-size:18px;">HOTEL POS</h1>
                @endif
            </td>
            
            <!-- Kolom Judul & Tanggal (Tengah) -->
            <td style="width: 50%; vertical-align: middle;" class="header-title">
                <h2>Laporan Pembelian Gudang</h2>
                <p>Periode: <strong>{{ $periode }}</strong></p>
                <p class="print-date">Dicetak pada: {{ now()->format('d M Y H:i:s') }}</p>
            </td>
            
            <!-- Kolom Kosong (Kanan) untuk penyeimbang -->
            <td style="width: 25%;"></td>
        </tr>
    </table>

    <!-- TABEL DATA -->
    <table>
        <thead>
            <tr>
                <th width="12%">Tanggal</th>
                <th width="12%">Kode Trx</th>
                <th width="20%">Nama Barang</th>
                <th width="12%">Kategori</th>
                <th width="6%" class="text-center">Qty</th>
                <th width="13%" class="text-right">Harga Satuan (Rp)</th>
                <th width="13%" class="text-right">Total (Rp)</th>
                <th width="12%">Penginput</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $trx)
            <tr>
                <td>{{ date('d M Y', strtotime($trx->transaction_date)) }}</td>
                <td style="font-size: 10px;">{{ $trx->transaction_code }}</td>
                <td>
                    {{ $trx->warehouseItem ? $trx->warehouseItem->name : 'Barang Dihapus' }}
                    @if($trx->notes)
                        <br><small style="color:#777; font-style:italic;">*{{ $trx->notes }}</small>
                    @endif
                </td>
                <td>{{ $trx->warehouseItem ? $trx->warehouseItem->category : '-' }}</td>
                <td class="text-center fw-bold">{{ $trx->quantity }} <span style="font-size: 9px; font-weight:normal;">{{ $trx->warehouseItem ? $trx->warehouseItem->unit : '' }}</span></td>
                <td class="text-right">{{ number_format($trx->unit_price, 0, ',', '.') }}</td>
                <td class="text-right text-orange fw-bold">{{ number_format($trx->total_price, 0, ',', '.') }}</td>
                <td style="font-size: 10px;">{{ $trx->creator ? $trx->creator->name : 'Sistem' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada histori pembelian (stok masuk) pada periode atau filter ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- KOTAK SUMMARY BAWAH -->
    <div class="summary-box">
        <table>
            <tr>
                <th>Total Nota Transaksi:</th>
                <td>{{ $totalTrx }} Trx</td>
            </tr>
            <tr>
                <th>Total Kuantitas Masuk:</th>
                <td>{{ $totalQty }} Item</td>
            </tr>
            <tr>
                <th class="border-top" style="font-size: 13px;">Total Nilai Pembelian:</th>
                <td class="border-top text-orange" style="font-size: 14px;">
                    Rp {{ number_format($totalValue, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        Aplikasi Manajemen Hotel &copy; {{ date('Y') }} - Divisi Gudang & Pengadaan
    </div>

</body>
</html>