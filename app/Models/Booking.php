<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var array<int, string>
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
        'is_incognito',
    ];

    /**
     * Mendefinisikan relasi bahwa setiap booking dimiliki oleh satu kamar.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Mendefinisikan relasi bahwa setiap booking (opsional) dimiliki oleh satu user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guest()
{
    return $this->belongsTo(Guest::class);
}
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
