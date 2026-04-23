<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeterReading extends Model
{
    //
    protected $fillable = [
        'room_id',
        'tenant_id',
        'meter_type', 
        'previous_value',
        'current_value',
        'units_used',
        'billing_month',
        'reading_date'
    ];

    public function room() {
        return $this->belongsTo(Room::class);
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }

}
