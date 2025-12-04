<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * Izinkan pengisian massal untuk kolom ini.
     * PENTING: Kolom 'status' dan 'payment_method' wajib ada di sini
     * agar fitur pembayaran berfungsi.
     */
    protected $fillable = [
        'room_id',
        'user_id',
        'guest_id',
        'booking_id',       // Tambahan: Agar bisa menyimpan ID Booking saat check-in
        'total_price',
        'midtrans_order_id',
        'status',           // Tambahan: Agar bisa ubah status jadi 'paid'
        'payment_method',   // Tambahan: Agar bisa simpan 'cash'/'qris'
        'updated_at'        // Tambahan: Agar waktu bayar bisa dimanipulasi
    ];

    /**
     * Mendapatkan user yang membuat pesanan online.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan kamar yang memiliki pesanan ini.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Mendapatkan semua item di dalam pesanan ini.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Sebuah Order dimiliki oleh satu Guest.
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
    
    /**
     * (Opsional) Relasi ke Booking jika diperlukan kedepannya
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}