<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'room_number',
        'staff_id',
        'status',
        'total_price',
    ];

    /**
     * Relasi ke User (Tamu yang memesan)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke User (Petugas yang menangani)
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Relasi ke Detail Item Order
     */
    public function items()
    {
        return $this->hasMany(LaundryOrderItem::class, 'laundry_order_id');
    }
}