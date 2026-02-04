<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'warehouse_item_id',
        'transaction_type',
        'quantity',
        'unit_price',
        'total_price',
        'reference_type',
        'reference_id',
        'notes',
        'transaction_date',
        'created_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'transaction_date' => 'date'
    ];

    /**
     * Generate transaction code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transaction_code)) {
                $prefix = 'TRX';
                $date = date('Ymd');
                $last = self::where('transaction_code', 'like', "{$prefix}%{$date}%")->orderBy('id', 'desc')->first();
                
                $number = $last ? intval(substr($last->transaction_code, -4)) + 1 : 1;
                $model->transaction_code = sprintf('%s/%s/%04d', $prefix, $date, $number);
            }
        });

        static::created(function ($model) {
            // Update stok barang ketika transaksi dibuat
            if ($model->transaction_type === 'in') {
                $model->warehouseItem->increment('current_stock', $model->quantity);
            } elseif ($model->transaction_type === 'out') {
                $model->warehouseItem->decrement('current_stock', $model->quantity);
            }
        });

        static::updated(function ($model) {
            // Handle jika quantity diubah
            // Ini lebih kompleks, untuk sementara kita skip
        });

        static::deleting(function ($model) {
            // Kembalikan stok jika transaksi dihapus
            if ($model->transaction_type === 'in') {
                $model->warehouseItem->decrement('current_stock', $model->quantity);
            } elseif ($model->transaction_type === 'out') {
                $model->warehouseItem->increment('current_stock', $model->quantity);
            }
        });
    }

    /**
     * Relasi ke warehouse item
     */
    public function warehouseItem()
    {
        return $this->belongsTo(WarehouseItem::class);
    }

    /**
     * Relasi ke user yang membuat transaksi
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope untuk transaksi masuk
     */
    public function scopeIncoming($query)
    {
        return $query->where('transaction_type', 'in');
    }

    /**
     * Scope untuk transaksi keluar
     */
    public function scopeOutgoing($query)
    {
        return $query->where('transaction_type', 'out');
    }

    /**
     * Scope berdasarkan rentang tanggal
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope berdasarkan jenis referensi
     */
    public function scopeByReference($query, $type, $id = null)
    {
        $query = $query->where('reference_type', $type);
        if ($id) {
            $query = $query->where('reference_id', $id);
        }
        return $query;
    }
}