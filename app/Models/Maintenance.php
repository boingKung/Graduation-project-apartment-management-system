<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'room_id',
        'title',
        'details',
        'status',
        'technician_name',
        'technician_time',
        'repair_date',
        'finish_date'
    ];

    // เชื่อมกลับไปหาห้องพัก
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}