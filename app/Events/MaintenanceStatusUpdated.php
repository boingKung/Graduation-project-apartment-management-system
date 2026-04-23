<?php

namespace App\Events;

use App\Models\Maintenance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $tenantId;
    public string $title;
    public string $status;
    public string $message;
    public ?string $technicianName;

    public function __construct(Maintenance $maintenance)
    {
        $this->tenantId       = $maintenance->tenant_id;
        $this->title          = $maintenance->title;
        $this->status         = $maintenance->status;
        $this->technicianName = $maintenance->technician_name;

        $this->message = match ($maintenance->status) {
            'processing' => '🔧 งานซ่อม "' . $maintenance->title . '" กำลังดำเนินการแล้ว',
            'finished'   => '✅ งานซ่อม "' . $maintenance->title . '" เสร็จสิ้นแล้ว',
            'cancelled'  => '❌ งานซ่อม "' . $maintenance->title . '" ถูกยกเลิก',
            default      => 'สถานะงานซ่อมอัปเดตแล้ว',
        };
    }

    /** Private channel ของ tenant นั้นๆ — คนอื่นดูไม่เห็น */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }

    /** ชื่อ event ที่ฝั่ง JS จะ listen */
    public function broadcastAs(): string
    {
        return 'maintenance.status.updated';
    }

    /** ข้อมูลที่จะส่งไปยัง browser */
    public function broadcastWith(): array
    {
        return [
            'message'        => $this->message,
            'title'          => $this->title,
            'status'         => $this->status,
            'technicianName' => $this->technicianName,
        ];
    }
}
