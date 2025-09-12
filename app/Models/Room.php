<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute; 
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Order;
use App\Models\CheckIn;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'type',
        'status',
        'price_per_night',
        'description',
        'image',
        'tersedia_mulai', 
        'tersedia_sampai', 
    ];

    protected $casts = [
        'tersedia_mulai' => 'date',
        'tersedia_sampai' => 'date',
    ];
    
    protected $appends = ['image_url'];

     protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? Storage::url($this->image) : null,
        );
    }

    // --- RELASI-RELASI MODEL ---

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'room_facility');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }
}