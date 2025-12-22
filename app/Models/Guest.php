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
        'id_card_number', 
        'is_verified',
        'address',
        'ktp_image',
        'is_blacklisted',   
        'blacklist_reason'  
    ];

    public function getKtpImageUrlAttribute()
    {
        return $this->ktp_image ? asset('storage/' . $this->ktp_image) : null;
    }
    protected $appends = ['ktp_image_url'];

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}