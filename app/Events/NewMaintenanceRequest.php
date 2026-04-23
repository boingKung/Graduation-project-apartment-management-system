<?php

namespace App\Events;

use App\Models\Maintenance;
use App\Models\Tenant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMaintenanceRequest implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $title;
    public string $roomNumber;
    public string $tenantName;
    public string $message;

    public function __construct(Maintenance $maintenance)
    {
        $room   = $maintenance->room;
        $tenant = Tenant::find($maintenance->tenant_id);

        $this->title      = $maintenance->title;
        $this->roomNumber = $room?->room_number ?? '-';
        $this->tenantName = $tenant ? ($tenant->first_name . ' ' . $tenant->last_name) : 'ผู้เช่า';
        $this->message    = 'ห้อง ' . $this->roomNumber . ' แจ้งซ่อม: "' . $this->title . '"';
    }

    /** Broadcast ไปยัง channel admin (public channel — ทุก admin เห็น) */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin-notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.maintenance.request';
    }

    public function broadcastWith(): array
    {
        return [
            'message'    => $this->message,
            'title'      => $this->title,
            'roomNumber' => $this->roomNumber,
            'tenantName' => $this->tenantName,
        ];
    }
}
