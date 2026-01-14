<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, Uuid;

    protected $fillable = [
        'app',
        'description',
        'check_in_time',     
        'check_out_time',    
        'early_check_in_fee', 
        'logo',
        'bg_auth',
        'bg_landing',
        'banner',
        'telepon',
        'alamat',
        'dinas',
        'pemerintah',
        'email'
    ];
}