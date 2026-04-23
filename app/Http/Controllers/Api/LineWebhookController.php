<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\MeterReading;
use Carbon\Carbon;

// นำเข้า Trait
use App\Traits\FormatHelper;

class LineWebhookController extends Controller
{
    use FormatHelper; // เรียกใช้ Trait ใน Class
    public function webhook(Request $request)
    {
        // อ่านข้อมูลที่ LINE ส่งมา
        $events = $request->input('events');

        if (is_null($events)) {
            return response('OK', 200);
        }

        // ดึง Token จาก .env
        $channelAccessToken = env('LINE_BOT_CHANNEL_ACCESS_TOKEN');

        foreach ($events as $event) {
            // เช็คว่าเป็นการส่งข้อความตัวอักษรมาหรือไม่
            if ($event['type'] === 'message' && $event['message']['type'] === 'text') {
                $replyToken = $event['replyToken'];
                $userMessage = trim($event['message']['text']);
                $lineId = $event['source']['userId']; // ID ของไลน์คนที่ทักมา

                $replyText = null;
                
                // 🌟 ค้นหาข้อมูลผู้เช่า โดยดึงข้อมูลล่าสุด (latest)
                $tenant = Tenant::with('room')->where('line_id', $lineId)->latest()->first();

                // -----------------------------------------------------------------
                // เมนู: เช็คข้อมูลประจำเดือน (รวมค่าเช่าและมิเตอร์)
                // * ใช้ in_array เพื่อรองรับข้อความจาก Rich menu เก่าและใหม่
                // -----------------------------------------------------------------
                if (in_array($userMessage, ['เช็คมิเตอร์น้ำไฟและค่าเช่าเดือนนี้','ค่าเช่าเดือนนี้', 'เช็คมิเตอร์น้ำไฟ', 'เช็คยอดชำระ'])) {
                    
                    if (!$tenant) {
                        $replyText = "เรียนผู้ใช้งาน\nระบบยังไม่พบข้อมูลบัญชีของท่าน กรุณากดเมนู 'ผูกบัญชีผู้เช่า' เพื่อเข้าสู่ระบบก่อนทำรายการครับ";
                    } 
                    elseif ($tenant->status === 'สิ้นสุดสัญญา') {
                        $replyText = "เรียนผู้เช่า\nสัญญาเช่าของท่านได้สิ้นสุดลงแล้ว ระบบจึงไม่สามารถแสดงข้อมูลได้ ขอขอบพระคุณที่ไว้วางใจใช้บริการกับเราครับ";
                    }
                    elseif ($tenant->status === 'รออนุมัติ' || is_null($tenant->room_id)) {
                        $replyText = "เรียนผู้เช่า\nข้อมูลการจองของท่านกำลังอยู่ระหว่างการตรวจสอบ กรุณารอเจ้าหน้าที่ดำเนินการจัดสรรห้องพักสักครู่ครับ";
                    } 
                    else {
                        // ดึงข้อมูลประจำเดือนปัจจุบัน
                        $currentMonth = Carbon::now()->format('Y-m');
                        $displayMonth = Carbon::now()->locale('th')->isoFormat('MMMM YYYY');
                        $roomNumber = $tenant->room->room_number ?? '-';

                        $replyText = "🏢 ข้อมูลประจำเดือน {$displayMonth}\nเรียนผู้เช่าห้อง {$roomNumber}\n\n";

                        // --- 1. ส่วนข้อมูลมิเตอร์ ---
                        $replyText .= "📊 ข้อมูลมิเตอร์น้ำ-ไฟ:\n";
                        $meters = MeterReading::where('tenant_id', $tenant->id)
                                              ->where('billing_month', $currentMonth)
                                              ->get();

                        if ($meters->count() > 0) {
                            foreach ($meters as $meter) {
                                $typeTh = (str_contains(strtolower($meter->meter_type), 'water') || str_contains($meter->meter_type, 'น้ำ')) ? '💧 น้ำประปา' : '⚡ ไฟฟ้า';
                                $replyText .= "{$typeTh}\n- เลขมิเตอร์ล่าสุด: {$meter->current_value}\n- ปริมาณที่ใช้: {$meter->units_used} หน่วย\n\n";
                            }
                        } else {
                            $replyText .= "⏳ ขณะนี้เจ้าหน้าที่กำลังอยู่ระหว่างการจดบันทึกมิเตอร์ประจำเดือนครับ\n\n";
                        }

                        // --- 2. ส่วนข้อมูลใบแจ้งหนี้ ---
                        $replyText .= "🧾 ข้อมูลค่าเช่าและบริการ:\n";
                        $invoice = Invoice::where('tenant_id', $tenant->id)
                                          ->where('billing_month', $currentMonth)
                                          ->first();

                        if ($invoice) {
                            $replyText .= "- ยอดรวมทั้งสิ้น: " . number_format($invoice->total_amount, 2) . " บาท\n";
                            $replyText .= "- สถานะ: {$invoice->status}\n";
                            
                            if (in_array($invoice->status, ['ค้างชำระ', 'ชำระบางส่วน'])) {
                                $due_date = $this->toThaiDate($invoice->due_date, true);
                                $replyText .= "- ยอดที่ต้องชำระ: " . number_format($invoice->remaining_balance, 2) . " บาท\n";
                                $replyText .= "- กำหนดชำระ: " . $due_date . "\n";
                            }
                        } else {
                            $replyText .= "⏳ ขณะนี้เจ้าหน้าที่กำลังอยู่ระหว่างการจัดทำใบแจ้งหนี้ประจำเดือนครับ";
                        }
                    }
                }
                
                // 🌟 ทำการส่งข้อความตอบกลับไปยัง LINE
                if ($replyText) {
                    Http::withToken($channelAccessToken)->post('https://api.line.me/v2/bot/message/reply', [
                        'replyToken' => $replyToken,
                        'messages' => [
                            [
                                'type' => 'text',
                                'text' => $replyText
                            ]
                        ]
                    ]);
                }
            }
        }

        return response('OK', 200);
    }
}