<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    //
    protected $fillable = [
        'name',
    ];

    public function roomPrices()
    {
        return $this->hasMany(RoomPrices::class);
    }
    public function rooms()
    {
        // ความสัมพันธ์: 1 ตึก มีหลาย ห้อง (One-to-Many) (เชื่อมผ่าน RoomPrices)
       return $this->hasManyThrough(
            Room::class,        // ตารางปลายทาง (rooms)
            RoomPrices::class,  // ตารางตัวกลาง (room_prices)
            'building_id',      // Foreign Key ในตารางตัวกลาง (room_prices.building_id)
            'room_price_id',    // Foreign Key ในตารางปลายทาง (rooms.room_price_id)
            'id',               // Local Key ในตารางต้นทาง (buildings.id)
            'id'                // Local Key ในตารางตัวกลาง (room_prices.id)
        );
    }
}
