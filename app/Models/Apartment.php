<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    //
    protected $table = 'apartments';
    protected $fillable = [
        'name',
        
        // ข้อมูลที่อยู่และเบอร์ติดต่อ (อ้างอิงตามใน Controller)
        'address_no',
        'moo',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'phone',

        // 🌟 3 คอลัมน์ที่เพิ่มเข้ามาใหม่ สำหรับจัดการระบบบิล
        'invoice_due_day',
        'late_fee_grace_days',
        
        // (คอลัมน์เดิมของคุณ ถ้าไม่ได้ใช้แล้วสามารถลบทิ้งได้ แต่ถ้ายังเก็บไว้เผื่อใช้ก็ใส่ไว้ได้ครับ)
        'location',
        'price',
        'size',
        'bedrooms',
        'bathrooms',
        'description',
    ];
}
