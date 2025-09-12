<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute; // <-- 1. Import Attribute
use Illuminate\Support\Facades\Storage;             // <-- 2. Import Storage

class Facility extends Model
{
    use HasFactory;

    /**
     * Properti yang diizinkan untuk diisi secara massal.
     */
    protected $fillable = ['name', 'icon', 'description'];

    /**
     * 3. Pastikan 'icon_url' selalu ditambahkan ke hasil JSON.
     */
    protected $appends = ['icon_url'];

    /**
     * Relasi many-to-many ke model Room.
     * Pivot table kamu bernama 'room_facility', jika berbeda sesuaikan di sini.
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_facility');
    }

    /**
     * 4. Tambahkan Accessor untuk mendapatkan URL lengkap dari ikon.
     * Ini akan mengubah path 'public/facilities/xyz.svg' menjadi URL yang bisa diakses.
     */
    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->icon ? Storage::url($this->icon) : null,
        );
    }
}