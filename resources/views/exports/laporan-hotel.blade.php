<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Hotel - {{ ucfirst($period) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 30px 35px;
        }

        /* HEADER */
        .header {
            text-align: center;
            border-bottom: 3px solid #F68B1E;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .header h1 {
            color: #F68B1E;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .header .sub {
            font-size: 10px;
            color: #999;
            margin-bottom: 7px;
        }
        .header .period {
            font-size: 10px;
            font-weight: bold;
            color: #D97706;
            background: #FFF5EB;
            border: 1px solid #F68B1E;
            padding: 3px 14px;
            border-radius: 20px;
            display: inline-block;
        }

        /* SECTION TITLE */
        .sec {
            font-size: 10px;
            font-weight: bold;
            color: #fff;
            background: #F68B1E;
            padding: 5px 10px;
            margin: 14px 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* CLEARFIX */
        .cf:after { content: ""; display: table; clear: both; }

        /* ─── STAT BOXES ─── */
        .box {
            float: left;
            padding: 10px 8px;
            border-radius: 4px;
            text-align: center;
            border: 1px solid #eee;
        }
        .box .lbl { font-size: 9px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; }
        .box .val { font-size: 13px; font-weight: bold; line-height: 1.3; }
        .box .sub { font-size: 9px; color: #888; margin-top: 3px; }

        .b-blue   { border-top: 3px solid #009EF7; background: #F1FAFF; }
        .b-blue   .lbl { color: #009EF7; } .b-blue   .val { color: #0078ab; }
        .b-red    { border-top: 3px solid #F1416C; background: #FFF5F8; }
        .b-red    .lbl { color: #F1416C; } .b-red    .val { color: #c73058; }
        .b-green  { border-top: 3px solid #50CD89; background: #E8FFF3; }
        .b-green  .lbl { color: #50CD89; } .b-green  .val { color: #38b06e; }
        .b-orange { border-top: 3px solid #F68B1E; background: #FFF5EB; }
        .b-orange .lbl { color: #F68B1E; } .b-orange .val { color: #c4690d; }
        .b-purple { border-top: 3px solid #7239EA; background: #F8F5FF; }
        .b-purple .lbl { color: #7239EA; } .b-purple .val { color: #5530c9; }
        .b-gray   { border-top: 3px solid #7E8299; background: #F5F8FA; }
        .b-gray   .lbl { color: #7E8299; } .b-gray   .val { color: #5e6278; }

        /* ─── TABLES ─── */
        table { width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 10px; }
        th, td { padding: 6px 9px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #FFF5EB; color: #D97706; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        tr:nth-child(even) td { background: #FAFAFA; }
        tfoot tr td, tfoot tr th { background: #FFF0DC; }
        tfoot th { font-size: 11px; color: #333; }
        .tr { text-align: right; }
        .tc { text-align: center; }

        /* ─── TWO COLUMN ─── */
        .col-l { float: left;  width: 48.5%; }
        .col-r { float: right; width: 48.5%; }

        /* ─── OP BOX ─── */
        .op {
            background: #F9F9F9;
            padding: 10px 12px;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        .op h3 {
            font-size: 9px;
            color: #777;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 5px;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .op p { margin-bottom: 4px; line-height: 1.6; font-size: 10px; }

        /* ─── COLOR TEXT ─── */
        .c-blue   { color: #0078ab; font-weight: bold; }
        .c-red    { color: #c73058; font-weight: bold; }
        .c-green  { color: #38b06e; font-weight: bold; }
        .c-orange { color: #c4690d; font-weight: bold; }
        .c-purple { color: #5530c9; font-weight: bold; }
        .c-gray   { color: #7E8299; font-weight: bold; }

        /* ─── BADGE ─── */
        .badge {
            padding: 1px 7px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        .bg-blue   { background: #F1FAFF; color: #0078ab; border: 1px solid #b3dff8; }
        .bg-orange { background: #FFF5EB; color: #c4690d; border: 1px solid #fcd9b3; }
        .bg-green  { background: #E8FFF3; color: #38b06e; border: 1px solid #bcf0d4; }
        .bg-red    { background: #FFF5F8; color: #c73058; border: 1px solid #f8d7e0; }

        /* ─── CHARTS ─── */
        .chart-label {
            font-size: 10px;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
            text-align: center;
        }
        .chart-img { width: 100%; }
        .chart-img img { width: 100%; border: 1px solid #eee; border-radius: 5px; display: block; }
        .chart-err {
            background: #F9F9F9;
            border: 1px dashed #DDD;
            border-radius: 5px;
            padding: 18px;
            text-align: center;
            color: #bbb;
            font-size: 10px;
        }

        /* ─── PAGE BREAK ─── */
        .pb { page-break-before: always; }

        /* ─── FOOTER ─── */
        .footer {
            margin-top: 18px;
            border-top: 1px solid #eee;
            padding-top: 8px;
            font-size: 9px;
            color: #bbb;
            text-align: center;
        }

        /* ─── SPACER ─── */
        .gap8  { margin-bottom: 8px; }
        .gap12 { margin-bottom: 12px; }
        .gap16 { margin-bottom: 16px; }
    </style>
</head>
<body>

{{-- ════ HEADER ════ --}}
<div class="header">
    <h1>Laporan Operasional Hotel</h1>
    <p class="sub">Dokumen digenerate otomatis oleh sistem manajemen hotel</p>
    <span class="period">Periode: {{ $period_label }} &nbsp;|&nbsp; {{ $start_date }} &ndash; {{ $end_date }}</span>
</div>

{{-- ════ RINGKASAN KEUANGAN ════ --}}
<div class="sec">Ringkasan Keuangan</div>
<div class="cf gap12">
    <div class="box b-blue" style="width:30%;margin-right:5%;">
        <div class="lbl">Total Pendapatan</div>
        <div class="val" style="font-size:14px;">Rp&nbsp;{{ number_format($revenue['total_revenue'],0,',','.') }}</div>
        <div class="sub">{{ $revenue['room_bookings']+$revenue['restaurant_orders']+$revenue['laundry_orders'] }} transaksi</div>
    </div>
    <div class="box b-red" style="width:30%;margin-right:5%;">
        <div class="lbl">Total Pengeluaran</div>
        <div class="val" style="font-size:14px;">Rp&nbsp;{{ number_format($expense['total_expense'],0,',','.') }}</div>
        <div class="sub">&nbsp;</div>
    </div>
    <div class="box {{ $profit['amount']>=0?'b-green':'b-red' }}" style="width:30%;">
        <div class="lbl">Laba / Rugi Bersih</div>
        <div class="val" style="font-size:14px;">Rp&nbsp;{{ number_format(abs($profit['amount']),0,',','.') }}</div>
        <div class="sub">
            <span class="badge {{ $profit['amount']>=0?'bg-green':'bg-red' }}">{{ $profit['status'] }}</span>
            &nbsp;Margin {{ $profit['margin'] }}%
        </div>
    </div>
</div>

{{-- ════ RINCIAN PENDAPATAN ════ --}}
<div class="sec">Rincian Pendapatan per Sumber</div>
@php
    $tot = $revenue['total_revenue'];
    $p1  = $tot > 0 ? round(($revenue['room_revenue']/$tot)*100,1)        : 0;
    $p2  = $tot > 0 ? round(($revenue['restaurant_revenue']/$tot)*100,1)  : 0;
    $p3  = $tot > 0 ? round(($revenue['laundry_revenue']/$tot)*100,1)     : 0;
@endphp
<div class="gap12">
<table>
    <thead>
        <tr>
            <th>Sumber Pendapatan</th>
            <th class="tc">Transaksi</th>
            <th class="tr">Nominal (Rp)</th>
            <th class="tc">Kontribusi</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Kamar Hotel</strong></td>
            <td class="tc c-blue">{{ $revenue['room_bookings'] }}</td>
            <td class="tr">Rp {{ number_format($revenue['room_revenue'],0,',','.') }}</td>
            <td class="tc"><span class="badge bg-blue">{{ $p1 }}%</span></td>
        </tr>
        <tr>
            <td><strong>Restoran &amp; POS</strong></td>
            <td class="tc c-blue">{{ $revenue['restaurant_orders'] }}</td>
            <td class="tr">Rp {{ number_format($revenue['restaurant_revenue'],0,',','.') }}</td>
            <td class="tc"><span class="badge bg-orange">{{ $p2 }}%</span></td>
        </tr>
        <tr>
            <td><strong>Layanan Laundry</strong></td>
            <td class="tc c-blue">{{ $revenue['laundry_orders'] }}</td>
            <td class="tr">Rp {{ number_format($revenue['laundry_revenue'],0,',','.') }}</td>
            <td class="tc"><span class="badge bg-green">{{ $p3 }}%</span></td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="2" class="tr">TOTAL PENDAPATAN KOTOR</th>
            <th class="tr" style="color:#D97706;font-size:12px;">Rp {{ number_format($revenue['total_revenue'],0,',','.') }}</th>
            <th class="tc"><span class="badge bg-orange">100%</span></th>
        </tr>
    </tfoot>
</table>
</div>

{{-- ════ KAMAR & TAMU ════ --}}
<div class="sec">Statistik Kamar &amp; Tamu</div>
<div class="cf gap12">
    <div class="col-l">
        <div class="op">
            <h3>Status Kamar Saat Ini</h3>
            <p>Kamar Tersedia (Total): <strong>{{ $occupancy['total_rooms'] }} kamar</strong></p>
            <p>Terisi: <span class="c-orange">{{ $occupancy['occupied_rooms'] }} kamar</span></p>
            <p>Kosong / Siap: <span class="c-green">{{ $occupancy['available_rooms'] }} kamar</span></p>
            <p>Perlu Dibersihkan: <span class="c-red">{{ $occupancy['dirty_rooms'] }} kamar</span></p>
            <p>Dalam Perawatan: <span class="c-gray">{{ $occupancy['maintenance_rooms'] }} kamar</span></p>
            <p style="margin-top:7px;">Tingkat Keterisian:
                <strong class="c-orange" style="font-size:14px;">{{ $occupancy['occupancy_rate'] }}%</strong>
            </p>
        </div>
    </div>
    <div class="col-r">
        <div class="op">
            <h3>Statistik Tamu</h3>
            <p>Tamu Periode Ini: <strong>{{ $guests['total_period'] }} orang</strong></p>
            <p>Tamu Menginap Saat Ini: <span class="c-blue">{{ $guests['currently_staying'] }} orang</span></p>
            <p>Check-in Hari Ini: <span class="c-green">{{ $guests['check_in_today'] }} orang</span></p>
            <p>Check-out Hari Ini: <span class="c-orange">{{ $guests['check_out_today'] }} orang</span></p>
            <p>Rata-rata Lama Menginap: <strong>{{ $guests['avg_stay_days'] }} malam</strong></p>
        </div>
    </div>
</div>

{{-- ════ RESERVASI ════ --}}
<div class="sec">Statistik Reservasi</div>
<div class="cf gap12">
    <div class="box b-blue"   style="width:13.5%;margin-right:2%;">
        <div class="lbl">Total</div>
        <div class="val">{{ $reservations['total'] }}</div>
    </div>
    <div class="box b-green"  style="width:13.5%;margin-right:2%;">
        <div class="lbl">Online</div>
        <div class="val">{{ $reservations['online'] }}</div>
    </div>
    <div class="box b-gray"   style="width:13.5%;margin-right:2%;">
        <div class="lbl">Offline</div>
        <div class="val">{{ $reservations['offline'] }}</div>
    </div>
    <div class="box b-orange" style="width:13.5%;margin-right:2%;">
        <div class="lbl">Konfirmasi</div>
        <div class="val">{{ $reservations['confirmed'] }}</div>
    </div>
    <div class="box b-purple" style="width:13.5%;margin-right:2%;">
        <div class="lbl">Check-in</div>
        <div class="val">{{ $reservations['checked_in'] }}</div>
    </div>
    <div class="box b-red"    style="width:13.5%;">
        <div class="lbl">Dibatalkan</div>
        <div class="val">{{ $reservations['cancelled'] }}</div>
        <div class="sub">{{ $reservations['cancellation_rate'] }}%</div>
    </div>
</div>

{{-- ════ KAMAR POPULER & MAINTENANCE ════ --}}
<div class="cf gap8">
    <div class="col-l">
        <div class="op">
            <h3>Tipe Kamar Paling Diminati</h3>
            @if(count($rooms['popular_types']) > 0)
                <table>
                    <thead><tr><th>Tipe Kamar</th><th class="tc">Jumlah Booking</th></tr></thead>
                    <tbody>
                        @foreach($rooms['popular_types'] as $t)
                        <tr>
                            <td>{{ $t->type }}</td>
                            <td class="tc c-blue">{{ $t->total_bookings }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color:#bbb;text-align:center;padding:8px;">Belum ada data</p>
            @endif
        </div>
    </div>
    <div class="col-r">
        <div class="op">
            <h3>Kamar dalam Perawatan (Maintenance)</h3>
            @if(count($rooms['maintenance_rooms']) > 0)
                <table>
                    <thead><tr><th>No. Kamar</th><th>Tipe</th></tr></thead>
                    <tbody>
                        @foreach($rooms['maintenance_rooms'] as $r)
                        <tr>
                            <td><strong>{{ $r->room_number }}</strong></td>
                            <td><span class="badge bg-red">{{ $r->type }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color:#38b06e;text-align:center;padding:8px;font-weight:bold;">&#10003; Tidak ada kamar dalam perawatan</p>
            @endif
        </div>
    </div>
</div>

{{-- ════════════ HALAMAN 2: GRAFIK ════════════ --}}
<div class="pb"></div>

<div class="sec" style="margin-top:0;">Visualisasi Data &mdash; {{ $period_label }}</div>

{{-- Grafik 1: Tren Pendapatan (full width) --}}
<p class="chart-label">Grafik 1 &mdash; Tren Pendapatan per Sumber</p>
<div class="gap12">
    @if(!empty($chart_revenue_trend))
        <img src="{{ $chart_revenue_trend }}" style="width:100%;border:1px solid #eee;border-radius:5px;display:block;" alt="Tren Pendapatan">
    @else
        <div class="chart-err">Grafik tren pendapatan gagal dimuat.</div>
    @endif
</div>

{{-- Grafik 2 & 3: Pie + Reservasi berdampingan --}}
<div class="cf gap12">
    <div class="col-l">
        <p class="chart-label">Grafik 2 &mdash; Komposisi Pendapatan (%)</p>
        @if(!empty($chart_pie_revenue))
            <img src="{{ $chart_pie_revenue }}" style="width:100%;border:1px solid #eee;border-radius:5px;display:block;" alt="Komposisi">
        @else
            <div class="chart-err">Grafik tidak tersedia.</div>
        @endif
    </div>
    <div class="col-r">
        <p class="chart-label">Grafik 3 &mdash; Volume Reservasi</p>
        @if(!empty($chart_reservations))
            <img src="{{ $chart_reservations }}" style="width:100%;border:1px solid #eee;border-radius:5px;display:block;" alt="Reservasi">
        @else
            <div class="chart-err">Grafik tidak tersedia.</div>
        @endif
    </div>
</div>

{{-- Grafik 4: Tren Okupansi (full width) --}}
<p class="chart-label">Grafik 4 &mdash; Tren Okupansi &amp; Check-in Tamu</p>
<div class="gap12">
    @if(!empty($chart_occupancy_trend))
        <img src="{{ $chart_occupancy_trend }}" style="width:100%;border:1px solid #eee;border-radius:5px;display:block;" alt="Okupansi">
    @else
        <div class="chart-err">Grafik tren okupansi gagal dimuat.</div>
    @endif
</div>

{{-- Grafik 5: Booking per tipe kamar --}}
@if(!empty($chart_room_types))
<p class="chart-label">Grafik 5 &mdash; Booking per Tipe Kamar</p>
<div class="gap12">
    <img src="{{ $chart_room_types }}" style="width:100%;border:1px solid #eee;border-radius:5px;display:block;" alt="Tipe Kamar">
</div>
@endif

<div class="footer">
    Laporan digenerate pada {{ now()->format('d M Y, H:i') }} WIB &nbsp;&bull;&nbsp; Sistem Manajemen Hotel &nbsp;&bull;&nbsp; Dokumen ini bersifat rahasia
</div>

</body>
</html>