<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'laundry_order_id',
        'laundry_service_id',
        'qty',
        'subtotal',
    ];

    /**
     * Relasi balik ke Induk Order
     */
    public function order()
    {
        return $this->belongsTo(LaundryOrder::class, 'laundry_order_id');
    }

    /**
     * Relasi ke Master Layanan Laundry (untuk melihat nama layanan, harga, dan bahan)
     */
    public function service()
    {
        return $this->belongsTo(LaundryService::class, 'laundry_service_id');
    }
}