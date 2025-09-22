<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use Uuid, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['photo_url', 'all_permissions', 'role'];

    /**
     * Event listener untuk model.
     */
    protected static function booted()
    {
        static::deleted(function ($user) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
        });
    }

    /**
     * [DITAMBAHKAN] Memberitahu Laravel untuk menggunakan kolom 'uuid' pada route model binding.
     * Ini adalah kunci untuk memperbaiki error 404 Anda.
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // --- JWT METHODS ---

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // --- RELATIONS ---

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // --- ATTRIBUTES & ACCESSORS ---

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo ? Storage::url($this->photo) : null,
        );
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->roles()->first(),
        );
    }

    protected function allPermissions(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getAllPermissions()->pluck('name'),
        );
    }
}