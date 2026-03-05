<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'user_id',
        'service_name',
        'service_item_id',
        'category',
        'quantity',
        'notes',
        'status',
        'cleaning_time',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function serviceItem()
    {
        return $this->belongsTo(ServiceItem::class, 'service_name', 'name');
    }
}
