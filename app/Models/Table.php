<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'slug',
        'type_id', 
        'location', 
        'status', // available, occupied, reserved
        'capacity'
    ];
}