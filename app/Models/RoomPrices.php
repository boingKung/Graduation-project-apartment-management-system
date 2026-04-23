<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomPrices extends Model
{
    //
    protected $fillable = [
        'room_type_id',
        'building_id',
        'floor_num',
        'color_code',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
