<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Booking;
use App\Models\CheckIn; 

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone_number', 
        'address',
    ];

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}