# คู่มือระบบแจ้งซ่อม (Maintenance System)

เอกสารนี้อัปเดตให้ตรงกับโค้ดปัจจุบันในโปรเจกต์ (Admin + Tenant) เพื่อแก้ปัญหา `Base table or view not found` และให้ใช้งานได้ทันที

---

## 1) โครงสร้างฐานข้อมูล (Migration)
**ไฟล์ปัจจุบัน:** `database/migrations/2026_02_16_162434_create_maintenances_table.php`

โค้ดในระบบตอนนี้ใช้งานฟิลด์ต่อไปนี้:
- `room_id`, `title`, `details`, `status`, `cost`, `repair_date`, `finish_date`

ถ้าเจอ error ว่าไม่มีคอลัมน์ ให้ปรับ schema ให้ตรงกับโค้ด (ตัวอย่างด้านล่าง)

```php
Schema::create('maintenances', function (Blueprint $blueprint) {
    $blueprint->id();
    $blueprint->foreignId('room_id')->constrained()->onDelete('cascade');
    $blueprint->string('title');
    $blueprint->text('details')->nullable();
    $blueprint->string('status')->default('pending');
    $blueprint->decimal('cost', 10, 2)->default(0);
    $blueprint->date('repair_date');
    $blueprint->date('finish_date')->nullable();
    $blueprint->timestamps();
});
```

จากนั้นรัน:
```
php artisan migrate
```

## 2) โมเดล (Model)
**ไฟล์:** `app/Models/Maintenance.php`

```php
class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'title',
        'details',
        'status',
        'cost',
        'repair_date',
        'finish_date'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
```

## 3) ฝั่งผู้เช่า (Tenant)
**ไฟล์:** `app/Http/Controllers/TenantController.php`

ฟังก์ชันที่ใช้:
- `sendMaintenance()` รับฟอร์มจากหน้า Dashboard (Modal)
- ใช้ `Auth::guard('tenant')` เพื่อดึงผู้เช่าและ `room_id`

**Route:**
```
POST /tenant/maintenance/send  (name: tenant.maintenance.send)
```

**View:** `resources/views/tenant/index.blade.php`
- ปุ่มเปิด Modal แจ้งซ่อม
- ฟอร์มส่ง `title`, `details`

## 4) ฝั่งผู้ดูแล (Admin)
**ไฟล์:** `app/Http/Controllers/AdminController.php`

ฟังก์ชันหลัก:
- `insertMaintenance()` บันทึกงานซ่อมจากหน้าประวัติห้อง
- `maintenanceIndex()` แสดงรายการแจ้งซ่อมทั้งหมด + filter สถานะ
- `updateMaintenanceStatus()` อัปเดตสถานะ / รายละเอียด / ค่าใช้จ่าย
- `roomHistory()` ดึงประวัติการซ่อมของห้อง

**Routes:**
```
POST /admin/maintenance/insert           (admin.maintenance.insert)
GET  /admin/maintenance                  (admin.maintenance.index)
PUT  /admin/maintenance/update/{id}      (admin.maintenance.update_status)
```

**Views:**
- `resources/views/admin/maintenance/index.blade.php` (หน้ารายการทั้งหมด + modal update)
- `resources/views/admin/rooms/history.blade.php` (ประวัติห้อง + modal เพิ่มแจ้งซ่อม)

## 5) Checklist
- [ ] ปรับ schema ตาราง `maintenances` ให้ตรงกับโค้ด (fields + types)
- [ ] รัน `php artisan migrate`
- [ ] ตรวจสอบ `Maintenance.php` ให้มีฟิลด์ครบ
- [ ] ตรวจสอบ route ของ Admin/Tenant ตรงตามด้านบน
- [ ] ทดลองส่งแจ้งซ่อมจากหน้า Tenant Dashboard