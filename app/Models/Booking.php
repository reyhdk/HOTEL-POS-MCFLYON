<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Booking extends Model
{
    use HasFactory;

    /**
     * ✅ FIXED: Tambahkan semua kolom yang digunakan di controllers
     */
    protected $fillable = [
        'room_id',
        'user_id',
        'guest_id',
        'check_in_date',
        'check_out_date',
        'total_price',
        'status',
        'midtrans_order_id',
        'midtrans_checkout_id',      // ✅ DITAMBAHKAN
        'is_incognito',
        'payment_method',             // ✅ DITAMBAHKAN
        'checked_in_at',              // ✅ DITAMBAHKAN

        // 🔥 KRITIS: Kolom Verifikasi KTP
        'ktp_image',                  // ✅ DITAMBAHKAN
        'verification_status',        // ✅ DITAMBAHKAN
        'rejection_reason',           // ✅ DITAMBAHKAN
    ];

    /**
     * ✅ Cast tanggal agar mudah dipakai
     */
    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'checked_in_at' => 'datetime',
        'is_incognito' => 'boolean',
    ];

    /**
     * Relasi: Booking dimiliki oleh satu kamar
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Relasi: Booking dimiliki oleh satu user (opsional)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi: Booking dimiliki oleh satu guest
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * ✅ Accessor: Generate URL lengkap untuk foto KTP
     */
    public function getKtpImageUrlAttribute(): ?string
    {
        if (empty($this->ktp_image)) {
            return null;
        }

        // Jika sudah URL lengkap (http/https), return langsung
        if (str_starts_with($this->ktp_image, 'http')) {
            return $this->ktp_image;
        }

        // Generate URL dari storage
        return asset('storage/' . $this->ktp_image);
    }

    /**
     * Scope: Filter booking yang sudah selesai
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Filter booking yang perlu verifikasi KTP
     */
    public function scopeNeedsVerification($query)
    {
        return $query->where('verification_status', 'pending')
            ->whereNotNull('ktp_image')
            ->where('ktp_image', '!=', '');
    }
}
