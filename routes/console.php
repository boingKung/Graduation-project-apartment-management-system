<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
// ให้ระบบรันคำสั่งคำนวณค่าปรับทุกวันเวลาเที่ยงคืนหนึ่งนาที app:calculate-late-fees
// Schedule::command('app:calculate-late-fees')->dailyAt('00:01');
// รันทุกชั่วโมง เฉพาะช่วงเช้า 07:00 - 09:00 (รอบสำรองเผื่อเปิดเครื่องสาย)
// Schedule::command('app:calculate-late-fees')
//         ->everyMinute()
//         ->between('07:00', '15:00')
//         ->withoutOverlapping(); // ป้องกันไม่ให้รันซ้อนกันหากรอบก่อนหน้ายังไม่จบ
Schedule::command('app:calculate-late-fees')->dailyAt('00:01')->withoutOverlapping();

// ระบบ back up ฐานข้อมูลทุกวัน โดยแบ่งเป็น 2 ขั้นตอน
// 1. เคลียร์ไฟล์ Backup เก่าทิ้งก่อน (ทำตอน ตี 1 ครึ่ง)
// withoutOverlapping() ป้องกันไม่ให้รันซ้อนกันถ้าเซิร์ฟเวอร์ค้าง
Schedule::command('backup:clean')->dailyAt('01:30')->withoutOverlapping();

// 2. ทำการ Backup ฐานข้อมูลก้อนใหม่ (ทำตอน ตี 2 ตรง)
Schedule::command('backup:run --only-db')->dailyAt('02:00')->withoutOverlapping();