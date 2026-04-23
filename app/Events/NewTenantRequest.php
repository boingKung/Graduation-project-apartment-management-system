<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTenantRequest implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function broadcastOn()
    {
        // 🌟 ส่งไปที่ช่องของ Admin
        return new Channel('admin-notifications');
    }

    public function broadcastAs()
    {
        // 🌟 ต้องชื่อตรงกับที่ JS รอฟังอยู่ (.new.tenant.request)
        return 'new.tenant.request';
    }

    public function broadcastWith()
    {
        // 🌟 สร้างข้อความที่จะให้ไปโชว์ใน Popup
        $fullName = $this->tenant->first_name . ' ' . $this->tenant->last_name;
        
        return [
            'message' => "คุณ {$fullName} กำลังรอการอนุมัติเข้าพัก",
        ];
    }
}