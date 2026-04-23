<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $tenantId;
    public string $message;
    public string $billingMonth;
    public string $totalAmount;

    public function __construct(Invoice $invoice)
    {
        $this->tenantId     = $invoice->tenant_id;
        $this->billingMonth = $invoice->billing_month;
        $this->totalAmount  = number_format($invoice->total_amount, 2);
        $this->message      = '📋 มีบิลใหม่รอบเดือน ' . $this->billingMonth
                            . ' ยอด ' . $this->totalAmount . ' บาท';
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->tenantId)];
    }

    public function broadcastAs(): string
    {
        return 'invoice.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message'      => $this->message,
            'status'       => 'invoice_sent',
            'billingMonth' => $this->billingMonth,
            'totalAmount'  => $this->totalAmount,
        ];
    }
}
