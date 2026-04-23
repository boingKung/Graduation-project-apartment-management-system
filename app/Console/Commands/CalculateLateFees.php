<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\TenantExpense;
use Carbon\Carbon;
// line notification
use Illuminate\Support\Facades\Http; // ต้องมีบรรทัดนี้เพื่อส่ง API
use Illuminate\Support\Facades\Log;  // ต้องมีบรรทัดนี้เพื่อบันทึก Error
class CalculateLateFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-late-fees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'คำนวณค่าปรับรายวันสำหรับใบแจ้งหนี้ที่ค้างชำระ';

    /**
     * Execute the console command.
     */
    public function handle(){
        try {
            // 🌟 1. ดึงการตั้งค่าส่วนกลางจากตาราง apartment
            $apartment = DB::table('apartment')->first();
            
            // ดึงจำนวนวันสูงสุดที่คิดค่าปรับ (ถ้าไม่มีให้ default เป็น 15)
            $maxLateDays = $apartment->late_fee_grace_days ?? 15;
            
            // ดึงเรทราคาค่าปรับต่อวันจากตาราง apartment (แทนการดึงจาก TenantExpense)
            $pricePerDay = $apartment->late_fee_per_day ?? 50.00;

            // ดึงแค่ "ชื่อ" ของค่าปรับจาก TenantExpense (ID 4) เพื่อให้ชื่อรายการบิลตรงกับที่ตั้งไว้
            $lateFeeExpense = TenantExpense::find(4);
            $nameFeeExpense = $lateFeeExpense ? $lateFeeExpense->name : 'ค่าปรับล่าช้า';

            // 2. ดึงใบแจ้งหนี้ที่ "ค้างชำระ" และเลยกำหนดส่ง
            $invoices = Invoice::where('status', 'ค้างชำระ')
                ->where('due_date', '<', Carbon::now()->startOfDay())
                ->get();

            if ($invoices->isEmpty()) {
                $this->info("ไม่พบใบแจ้งหนี้ที่ค้างชำระ");
                return;
            }

            foreach ($invoices as $invoice) {
                try {
                    $today = Carbon::now()->startOfDay();
                    $due = Carbon::parse($invoice->due_date)->startOfDay();

                    // คำนวณจำนวนวันที่ค้างชำระ (ค่าบวกเสมอ)
                    $daysLate = $today->gt($due) ? $today->diffInDays($due, true) : 0;
                    
                    // 🌟 3. จำกัดจำนวนวันคิดค่าปรับตามการตั้งค่าในตาราง apartment
                    $daysToCalculate = min($daysLate, $maxLateDays);
                    $totalPenalty = $daysToCalculate * $pricePerDay;

                    if ($totalPenalty > 0) {
                        DB::transaction(function () use ($invoice, $totalPenalty, $daysToCalculate, $pricePerDay, $nameFeeExpense) {

                            // บันทึก/อัปเดต รายการค่าปรับลงตารางรายละเอียด
                            InvoiceDetail::updateOrCreate(
                                [
                                    'invoice_id' => $invoice->id,
                                    'tenant_expense_id' => 4
                                ],
                                [
                                    'name' => $nameFeeExpense,
                                    'quantity' => $daysToCalculate,
                                    'price_per_unit' => $pricePerDay,
                                    'subtotal' => $totalPenalty,
                                    'updated_at' => now()
                                ]
                            );

                            // อัปเดตยอดรวมในใบแจ้งหนี้หลัก
                            $newTotal = InvoiceDetail::where('invoice_id', $invoice->id)->sum('subtotal');
                            $invoice->update(['total_amount' => $newTotal]);
                        });

                        $this->sendLinePenaltyNotification($invoice, $pricePerDay, $totalPenalty);

                        $this->info("บันทึกค่าปรับและแจ้งเตือน LINE Invoice #{$invoice->invoice_number} สำเร็จ");
                    }
                } catch (\Exception $e) {
                    // แสดง Error บนหน้าจอ Terminal แทนการลงไฟล์ Log
                    $this->error("เกิดข้อผิดพลาดที่บิล {$invoice->id}: " . $e->getMessage());
                    continue;
                }
            }

            $this->info('ดำเนินการเสร็จสิ้น');

        } catch (\Exception $e) {
            $this->error("ระบบหลักขัดข้อง: " . $e->getMessage());
        }
    }

    private function sendLinePenaltyNotification($invoice, $pricePerDay, $currentPenalty)
    {
        try {
            $invoice->loadMissing(['tenant.room']);
            $tenant = $invoice->tenant;

            if (!$tenant || !$tenant->line_id) return;

            $channelAccessToken = env('LINE_BOT_CHANNEL_ACCESS_TOKEN');
            $roomNumber = $tenant->room->room_number ?? '-';
            
            // แปลงเดือนเป็นภาษาไทย (ถ้าไม่มี Function toThaiDate ใน Command ให้ใช้ Carbon แทน)
            $monthName = \Carbon\Carbon::parse($invoice->billing_month)->locale('th')->translatedFormat('F Y');
            
            $totalAmount = number_format($invoice->total_amount, 2);

            $messageText = "⚠️ แจ้งเตือนค่าปรับล่าช้า ห้อง {$roomNumber}\n";
            $messageText .= "เนื่องจากเลยกำหนดชำระบิลเดือน: {$monthName}\n";
            $messageText .= "------------------------------\n";
            $messageText .= "    ค่าปรับวันนี้: +" . number_format($pricePerDay, 2) . " บาท\n";
            $messageText .= "📉 ค่าปรับสะสม: " . number_format($currentPenalty, 2) . " บาท\n";
            $messageText .= "💰 ยอดรวมที่ต้องชำระ: {$totalAmount} บาท\n";
            $messageText .= "------------------------------\n";
            $messageText .= "กรุณาชำระเงินและส่งหลักฐานการชำระเงินเพื่อหยุดการเพิ่มของค่าปรับครับ 🙏";

            Http::withToken($channelAccessToken)->post('https://api.line.me/v2/bot/message/push', [
                'to' => $tenant->line_id,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $messageText
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Line Penalty Notify Error: ' . $e->getMessage());
        }
    }
}