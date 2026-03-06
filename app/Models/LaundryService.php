<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'price',
        'estimated_materials',
        'is_active',
    ];

    // Casting kolom JSON menjadi array secara otomatis agar mudah diakses di Controller/Vue
    protected $casts = [
        'estimated_materials' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Order Item
     */
    public function orderItems()
    {
        return $this->hasMany(LaundryOrderItem::class);
    }
}