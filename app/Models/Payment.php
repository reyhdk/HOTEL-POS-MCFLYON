<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     * Laravel biasanya sudah pintar mendeteksinya (payments),
     * tapi menuliskannya secara eksplisit itu baik.
     */
    protected $table = 'payments';

    /**
     * Kolom yang boleh diisi secara massal (mass assignable).
     */
    protected $guarded = ['id'];
}