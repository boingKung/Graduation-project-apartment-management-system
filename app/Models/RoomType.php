<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    //
    protected $fillable = [
        'name',
    ];

    public function roomPrices()
    {
        return $this->hasMany(RoomPrices::class);
    }
}
