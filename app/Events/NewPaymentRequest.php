<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPaymentRequest implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $payment;

    public function __construct(Payment $payment)
    {
        // โหลดข้อมูลบิล ห้อง และผู้เช่ามาด้วยเพื่อใช้ทำข้อความแจ้งเตือน
        $this->payment = $payment->load('invoice.tenant.room');
    }

    public function broadcastOn()
    {
        // 🌟 ต้องส่งเข้า Channel ของ Admin
        return new Channel('admin-notifications');
    }

    public function broadcastAs()
    {
        // 🌟 ชื่อ Event ให้ตรงกับที่เขียนไว้ใน Javascript (admin.layout.blade.php)
        return 'new.payment.request';
    }

    public function broadcastWith()
    {
        $roomNumber = $this->payment->invoice->tenant->room->room_number ?? '-';
        $amount = number_format($this->payment->amount_paid, 2);

        return [
            'title' => 'แจ้งโอนเงินใหม่',
            'message' => "ยอดโอน: {$amount} บาท (รอตรวจสอบสลิป)",
            'roomNumber' => $roomNumber,
            'amountPaid' => $this->payment->amount_paid,
        ];
    }
}