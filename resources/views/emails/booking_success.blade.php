<!DOCTYPE html>

<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Booking - {{ $booking->midtrans_order_id }}</title>
<style>
/* Reset & Base Styles */
body {
margin: 0;
padding: 0;
background-color: #f5f8fa;
font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
-webkit-font-smoothing: antialiased;
}
table { border-collapse: collapse; width: 100%; }
img { border: 0; outline: none; text-decoration: none; }

    /* Layout */
    .email-container {
        max-width: 600px;
        margin: 0 auto;
        background-color: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-top: 40px;
        margin-bottom: 40px;
    }

    /* Header Modern Orange */
    .header {
        background: linear-gradient(135deg, #f68b1e 0%, #d97814 100%);
        padding: 40px 20px;
        text-align: center;
        color: #ffffff;
    }
    .header h1 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .header p {
        margin-top: 10px;
        opacity: 0.9;
        font-size: 14px;
    }

    /* Content Area */
    .content {
        padding: 40px;
    }
    .greeting {
        font-size: 18px;
        color: #1a1a1a;
        font-weight: 700;
        margin-bottom: 15px;
    }
    .message {
        color: #5e6278;
        line-height: 1.6;
        font-size: 15px;
        margin-bottom: 30px;
    }

    /* Summary Card */
    .summary-card {
        background-color: #fffaf5;
        border: 1px dashed #f68b1e;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 30px;
    }
    .summary-title {
        color: #f68b1e;
        font-weight: 800;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 15px;
        display: block;
    }
    .detail-row {
        margin-bottom: 10px;
        display: table;
        width: 100%;
    }
    .detail-label {
        color: #7e8299;
        font-size: 14px;
        display: table-cell;
        width: 40%;
    }
    .detail-value {
        color: #181c32;
        font-weight: 700;
        font-size: 14px;
        display: table-cell;
        text-align: right;
    }

    /* Status Badge */
    .badge-paid {
        background-color: #e8fff3;
        color: #50cd89;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    /* Footer */
    .footer {
        background-color: #f5f8fa;
        padding: 30px;
        text-align: center;
        border-top: 1px solid #eff2f5;
    }
    .footer-text {
        color: #b5b5c3;
        font-size: 12px;
        line-height: 1.5;
    }
    
    /* Button Style */
    .btn-action {
        display: inline-block;
        background-color: #f68b1e;
        color: #ffffff !important;
        padding: 12px 25px;
        text-decoration: none;
        font-weight: 700;
        border-radius: 6px;
        font-size: 14px;
        margin-top: 20px;
    }
</style>


</head>
<body>

<table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f5f8fa">
    <tr>
        <td align="center">
            <div class="email-container">
                
                <!-- Header -->
                <div class="header">
                    <div style="font-size: 32px; margin-bottom: 10px;">🏨</div>
                    <h1>Pembayaran Diterima</h1>
                    <p>ID Pesanan: #{{ $booking->midtrans_order_id }}</p>
                </div>

                <!-- Body -->
                <div class="content">
                    <div class="greeting">Halo, {{ $booking->guest->name }}!</div>
                    <p class="message">
                        Terima kasih telah memilih kami untuk menginap. Kami dengan senang hati menginformasikan bahwa pembayaran Anda telah berhasil diverifikasi dan reservasi Anda kini telah <strong>Dikonfirmasi</strong>.
                    </p>

                    <div class="summary-card">
                        <span class="summary-title">Ringkasan Reservasi</span>
                        
                        <div class="detail-row">
                            <span class="detail-label">Kamar</span>
                            <span class="detail-value">No. {{ $booking->room->room_number }} ({{ $booking->room->type }})</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Check-in</span>
                            <span class="detail-value">{{ \Carbon\Carbon::parse($booking->check_in_date)->format('d F Y') }}</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Check-out</span>
                            <span class="detail-value">{{ \Carbon\Carbon::parse($booking->check_out_date)->format('d F Y') }}</span>
                        </div>

                        <div style="margin: 15px 0; border-top: 1px solid #f1f1f1;"></div>

                        <div class="detail-row">
                            <span class="detail-label">Total Pembayaran</span>
                            <span class="detail-value" style="color: #f68b1e; font-size: 18px;">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                        </div>

                        <div class="detail-row" style="margin-top: 10px;">
                            <span class="detail-label">Status</span>
                            <span class="detail-value"><span class="badge-paid">Lunas</span></span>
                        </div>
                    </div>

                    <p class="message" style="text-align: center; font-size: 14px;">
                        Harap tunjukkan email ini kepada staf resepsionis kami saat tiba untuk mempercepat proses check-in.
                    </p>

                    <div style="text-align: center;">
                        <a href="{{ config('app.url') }}" class="btn-action">Lihat Detail Pesanan</a>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <p class="footer-text">
                        <strong>{{ config('app.name') }}</strong><br>
                        Jl. Kemewahan No. 1, Kota Wisata<br>
                        Layanan Pelanggan: (021) 1234-5678
                    </p>
                    <p class="footer-text" style="margin-top: 20px;">
                        Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini secara langsung.
                    </p>
                </div>

            </div>
        </td>
    </tr>
</table>


</body>
</html>