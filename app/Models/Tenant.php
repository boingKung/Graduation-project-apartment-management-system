<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Room;

class Tenant extends Authenticatable
{
    protected $fillable = [
        'room_id',
        'id_card',
        'password',
        'first_name',
        'last_name',
        'age',                       // 🌟 อายุ
        'id_card_issue_date',        // 🌟 วันออกบัตร
        'id_card_expiry_date',       // 🌟 บัตรหมดอายุ
        'id_card_issue_place',       // 🌟 สถานที่ออกบัตร
        'id_card_issue_province',    // 🌟 จังหวัดที่ออกบัตร
        'address_no',
        'moo',
        'street',                    // 🌟 ถนน
        'alley',                     // 🌟 ตรอก/ซอย
        'sub_district',
        'district',
        'province',
        'postal_code',
        'phone',
        'workplace',                 // 🌟 สถานที่ทำงาน
        'start_date',
        'end_date',
        'has_parking',
        'resident_count',
        'deposit_amount',
        'deposit_payment_method', 
        'deposit_slip',
        'rental_contract',
        'status',
        'line_id',
        'line_avatar'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',

            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'id_card_issue_date' => 'date:Y-m-d',   
            'id_card_expiry_date' => 'date:Y-m-d',

            'has_parking' => 'boolean',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function invoices()
    {
        // เชื่อมไปยัง Model Invoice โดยใช้คอลัมน์ tenant_id
        return $this->hasMany(Invoice::class, 'tenant_id', 'id');
    }
}
