<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Arus Kas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        
        /* Modifikasi Header */
        .header-table { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #F68B1E; padding-bottom: 10px; }
        .header-table td { border: none; padding: 0; }
        .header-title { text-align: center; }
        .header-title h2 { margin: 0; color: #F68B1E; font-size: 24px; text-transform: uppercase; }
        .header-title p { margin: 5px 0 0 0; color: #555; }
        .header-title .print-date { font-size: 11px; color: #777; font-style: italic; margin-top: 3px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; color: #444; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        
        .summary-box { margin-top: 20px; width: 300px; float: right; }
        .summary-box table th { background-color: #fff; border: none; padding: 5px; }
        .summary-box table td { border: none; padding: 5px; text-align: right; font-weight: bold; }
        .border-top { border-top: 2px solid #333 !important; }
        
        .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>

    <!-- HEADER RESMI MENGGUNAKAN TABEL -->
    <table class="header-table">
        <tr>
            <!-- Kolom Logo (Kiri) -->
            <td style="width: 25%; text-align: left; vertical-align: middle;">
                @if(!empty($logo))
                    <img src="{{ $logo }}" style="max-height: 70px; max-width: 180px; object-fit: contain;">
                @endif
            </td>
            
            <!-- Kolom Judul & Tanggal (Tengah) -->
            <td style="width: 50%; vertical-align: middle;" class="header-title">
                <h2>Laporan Arus Kas Hotel</h2>
                <p>Periode: <strong>{{ $periode }}</strong></p>
                <p class="print-date">Dicetak tanggal: {{ $printed_at }}</p>
            </td>
            
            <!-- Kolom Kosong (Kanan) untuk penyeimbang agar judul tetap di tengah -->
            <td style="width: 25%;"></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="15%">Tanggal</th>
                <th width="10%">Tipe</th>
                <th width="10%">Kategori</th>
                <th width="30%">Deskripsi</th>
                <th width="15%">Metode &amp; Ref</th>
                <th width="10%" class="text-right">Masuk (Rp)</th>
                <th width="10%" class="text-right">Keluar (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $trx)
            <tr>
                <td>{{ date('d M Y H:i', strtotime($trx->transaction_date)) }}</td>
                <td>{{ $trx->type == 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                <td>{{ ucfirst($trx->category) }}</td>
                <td>{{ $trx->description }}</td>
                <td>
                    {{ $trx->payment_method }}
                    @if($trx->reference_id)
                        <br><small style="color:#777">ID: {{ $trx->reference_id }}</small>
                    @endif
                </td>
                <td class="text-right text-success">
                    {{ $trx->type == 'income' ? number_format($trx->amount, 0, ',', '.') : '-' }}
                </td>
                <td class="text-right text-danger">
                    {{ $trx->type == 'expense' ? number_format($trx->amount, 0, ',', '.') : '-' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Tidak ada transaksi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <table>
            <tr>
                <th>Total Pemasukan:</th>
                <td class="text-success">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Pengeluaran:</th>
                <td class="text-danger">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th class="border-top" style="font-size: 14px;">Saldo Bersih:</th>
                <td class="border-top" style="font-size: 14px; color: {{ $netBalance >= 0 ? '#28a745' : '#dc3545' }}">
                    Rp {{ number_format($netBalance, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Aplikasi Manajemen Hotel &copy; {{ date('Y') }}
    </div>

</body>
</html>