<!DOCTYPE html>

<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verifikasi KTP Ditolak</title>
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f7; }
.container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.header { background-color: #e3342f; color: white; padding: 30px; text-align: center; }
.content { padding: 30px; }
.reason-box { background-color: #fff5f5; border-left: 4px solid #e3342f; padding: 15px; margin: 20px 0; border-radius: 4px; }
.footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #718096; }
.button { display: inline-block; padding: 14px 30px; background-color: #3490dc; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
.alert-text { color: #e3342f; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
<div class="header">
<h2 style="margin:0;">Verifikasi Identitas</h2>
</div>
<div class="content">
<h3>Halo, {{ $guest->name }}</h3>
<p>Terima kasih telah melakukan pemesanan. Namun, kami menemukan kendala pada dokumen identitas (KTP) yang Anda unggah.</p>

        <p class="alert-text">Status: Dokumen Ditolak</p>
        
        <div class="reason-box">
            <strong>Alasan Penolakan:</strong><br>
            {{ $reason }}
        </div>

        <p>Mohon unggah kembali foto KTP Anda yang asli, jelas (tidak blur), dan tidak terpotong untuk melanjutkan proses verifikasi.</p>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/ktp-update" class="button">Unggah Ulang KTP Sekarang</a>
        </div>

        <p style="margin-top: 30px; font-size: 14px; color: #4a5568;">
            Jika tombol di atas tidak berfungsi, salin dan tempel tautan berikut ke browser Anda:<br>
            <a href="{{ config('app.url') }}/ktp-update">{{ config('app.url') }}/ktp-update</a>
        </p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Seluruh hak cipta dilindungi.</p>
    </div>
</div>


</body>
</html>