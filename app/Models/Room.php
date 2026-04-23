<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    //

    protected $fillable = [
        'room_number',
        'building_id',
        'room_type_id',
        'price',
        'status',
        'remark',
        //จัดการ planfloor
        'pos_x',
        'pos_y',
        'width',
        'height'
    ];

    public function roomPrice()
    {
        return $this->belongsTo(RoomPrices::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
    //8:29 1/9/2026 เพิ่มส่วนนี้เข้าไปเพื่อดึงชั้นมา
    // --- เพิ่มฟังก์ชันเพื่อให้รู้ว่าห้องนี้อยู่ตึกไหน
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function getFloorAttribute()
    {
        return substr($this->room_number, 0, 1);
    }
    public function maintenances()
    {
        return $this->hasMany(Maintenance::class)->orderBy('repair_date', 'desc');
    }
}
