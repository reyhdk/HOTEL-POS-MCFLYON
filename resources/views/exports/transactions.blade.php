<!DOCTYPE html>
<html>
<head>
    <title>Laporan Transaksi</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; background-color: #ffe0b2; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Riwayat Transaksi Hotel</h2>
        <p>Dicetak pada: {{ date('d-m-Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Order</th>
                <th>Tamu</th>
                <th>Kamar</th>
                <th>Metode Bayar</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $index => $order)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>#{{ $order->id }}</td>
                <td>{{ $order->guest->name ?? $order->user->name ?? 'Umum' }}</td>
                <td>{{ $order->room->room_number ?? '-' }}</td>
                <td>{{ $order->payment_method ?? 'Tunai' }}</td>
                <td>{{ $order->updated_at->format('d-m-Y H:i') }}</td>
                <td>{{ ucfirst($order->status) }}</td>
                <td style="text-align: right;">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="7" style="text-align: center;">TOTAL PENDAPATAN</td>
                <td style="text-align: right;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>