<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chef extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialization',
        'is_active',
        'max_concurrent_orders',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_concurrent_orders' => 'integer'
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Orders yang di-assign ke chef ini
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'chef_id', 'user_id');
    }

    /**
     * Get active orders for this chef
     */
    public function activeOrders()
    {
        return $this->orders()
                    ->whereIn('status', ['pending', 'processing'])
                    ->whereDate('created_at', today());
    }

    /**
     * Check if chef can take more orders
     */
    public function canTakeOrder()
    {
        if (!$this->is_active) {
            return false;
        }
        
        $currentLoad = $this->activeOrders()->count();
        return $currentLoad < $this->max_concurrent_orders;
    }

    /**
     * Get current workload
     */
    public function getCurrentWorkload()
    {
        return $this->activeOrders()->count();
    }

    /**
     * Scope: Only active chefs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Available chefs (active and not overloaded)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                     ->whereHas('user') // Pastikan user masih ada
                     ->get()
                     ->filter(function($chef) {
                         return $chef->canTakeOrder();
                     });
    }
}