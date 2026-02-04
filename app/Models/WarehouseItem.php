<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'min_stock',
        'current_stock',
        'cost_price',
        'selling_price',
        'supplier',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'min_stock' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Relasi ke transaksi stok
     */
    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    /**
     * Relasi ke resep (jika digunakan dalam menu)
     */
    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * Scope untuk item aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk kategori tertentu
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope untuk stok rendah
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock');
    }
}