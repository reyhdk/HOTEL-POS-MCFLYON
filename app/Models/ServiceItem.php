<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceItem extends Model
{
    protected $fillable = [
        'name',
        'photo_url',
        'category',
        'description',
        'max_quantity',
        'is_active',
        'warehouse_item_id',
        'assigned_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_quantity' => 'integer',
    ];

    /**
     * Petugas yang di-assign ke item ini secara default.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Semua permintaan yang menggunakan item ini.
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    // --- Scope ---
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function warehouseItem()
    {
        return $this->belongsTo(WarehouseItem::class, 'warehouse_item_id');
    }
}