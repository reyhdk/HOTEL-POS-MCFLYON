<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'image',
        'stock',
        'category',
    ];

    // Pastikan appends ini ada agar image_url selalu muncul di API
    protected $appends = ['image_url'];

    // Accessor ini akan membuat properti 'image_url' secara otomatis
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? Storage::url($this->image) : null,
        );
    }
}