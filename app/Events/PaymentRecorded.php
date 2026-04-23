<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $tenantId;
    public string $message;
    public string $status;
    public string $amountPaid;
    public string $remaining;

    public function __construct(Invoice $invoice, float $amountPaid)
    {
        $this->tenantId  = $invoice->tenant_id;
        $this->status    = $invoice->status;        // ชำระแล้ว / ชำระบางส่วน
        $this->amountPaid = number_format($amountPaid, 2);
        $this->remaining  = number_format(max(0, $invoice->remaining_balance), 2);

        if ($invoice->status === 'ชำระแล้ว') {
            $this->message = '✅ ชำระเงินครบแล้ว ' . $this->amountPaid . ' บาท — บิลนี้เสร็จสมบูรณ์';
        } else {
            $this->message = '💰 รับชำระ ' . $this->amountPaid . ' บาท — ค้างอีก ' . $this->remaining . ' บาท';
        }
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->tenantId)];
    }

    public function broadcastAs(): string
    {
        return 'payment.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'message'    => $this->message,
            'status'     => $this->status,
            'amountPaid' => $this->amountPaid,
            'remaining'  => $this->remaining,
        ];
    }
}
