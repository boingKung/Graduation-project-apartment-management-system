<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Events\InvoiceSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // เรียกใช้งาน Auth Facade
use Barryvdh\DomPDF\Facade\Pdf; // ใช้งานสำหรับ pdf
// export Excel
use App\Exports\CollectionReportExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Building;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\TenantExpense;
use App\Models\MeterReading;
use App\Models\Invoice;
use App\Models\InvoiceDetail;

// นำเข้า Trait
use App\Traits\FormatHelper;

// line
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // เผื่อเก็บ Error ของ LINE
class InvoiceController extends Controller
{
    use FormatHelper; // เรียกใช้ Trait ใน Class
    // จัดการระบบ Invoice
    public function invoiceShow(Request $request)
    {
        $request->merge(['billing_month' => $this->convertThaiYearToAD($request->billing_month)]);
        $billing_month = $request->billing_month ?? date('Y-m');
        $searchRoom = $request->input('search_room');
        $searchPrice = $request->input('search_price');
        $searchStatus = $request->input('search_status');
        $searchBuilding = $request->input('search_building');

        $dateObj = \Carbon\Carbon::parse($billing_month);
        $endOfMonth = $dateObj->endOfMonth()->format('Y-m-d');

        $buildings = Building::orderBy('name')->get();
        $query = Room::with([
            'tenants' => fn($q) => $q->where('start_date', '<=', $endOfMonth)->where('status', 'กำลังใช้งาน'),
            'roomPrice.building',
            'roomPrice.roomType'
        ])->where('status', 'มีผู้เช่า');

        if ($searchBuilding) {
            // ค้นหาตาม ID ของตึกที่ผูกกับราคาห้อง
            $query->whereHas('roomPrice', function($q) use ($searchBuilding) {
                $q->where('building_id', $searchBuilding);
            });
        }
        if ($searchRoom) $query->where('room_number', 'like', "%{$searchRoom}%");

        $rooms = $query->get()->filter(fn($room) => $room->tenants->isNotEmpty());

        $rooms->each(function ($room) use ($billing_month) {
            $tenant = $room->tenants->first();
            $room->tenant_id = $tenant->id;
            
            $prevMonth = \Carbon\Carbon::parse($billing_month)->subMonth()->format('Y-m');
            $room->prev_water = MeterReading::where('room_id', $room->id)->where('meter_type', 'water')->where('billing_month', $prevMonth)->value('current_value') ?? 0;
            $room->prev_electric = MeterReading::where('room_id', $room->id)->where('meter_type', 'electric')->where('billing_month', $prevMonth)->value('current_value') ?? 0;

            $readings = MeterReading::where('room_id', $room->id)->where('billing_month', $billing_month)->get();
            $room->can_create_invoice = $readings->where('meter_type', 'water')->isNotEmpty() && $readings->where('meter_type', 'electric')->isNotEmpty();
            
            $invoice = Invoice::where('room_id', $room->id)->where('billing_month', $billing_month)->first();
            // ปรับปรุงการตั้งค่าสถานะเริ่มต้น
            if (!$room->can_create_invoice) {
                // กรณียังไม่ได้จดมิเตอร์
                $room->invoice_status = 'ยังไม่จดมิเตอร์'; 
                $room->invoice_color = 'danger';
                $room->display_total = $room->price ?? 0;
            } elseif (!$invoice) {
                $room->invoice_status = 'ยังไม่ได้สร้างบิล';
                $room->invoice_color = 'secondary';
                $room->display_total = $room->price ?? 0; // ยอดตั้งต้นก่อนทำบิล
            } else {
                $room->invoice_status = $invoice->status;
                // ปรับสีสถานะใหม่ให้ดูทางการ
                if($invoice->status == 'ชำระแล้ว') $room->invoice_color = 'success';
                elseif($invoice->status == 'ค้างชำระ') $room->invoice_color = 'danger';
                elseif($invoice->status == 'ชำระบางส่วน') $room->invoice_color = 'warning text-dark';
                else $room->invoice_color = 'info text-dark';

                $room->invoice_total = $invoice->total_amount;
                $room->invoice_id = $invoice->id;
                $room->display_total = $invoice->total_amount; // ยอดสุทธิหลังทำบิล
            }

            // เช็คสีมิเตอร์
            $room->meter_status = $room->can_create_invoice ? 'จดมิเตอร์แล้ว' : 'ยังไม่จดมิเตอร์';
            $room->meter_color = $room->can_create_invoice ? 'success' : 'danger';
        });

        // --- กรองข้อมูลหลังคำนวณเสร็จ ---
        if ($searchStatus) {
            $rooms = $rooms->filter(fn($r) => $r->invoice_status == $searchStatus);
        }
        
        // ย้ายมากรองตรงนี้ เพื่อให้ใช้ค่า display_total ที่คำนวณมาแล้ว
        if ($searchPrice) {
            $rooms = $rooms->filter(fn($r) => $r->display_total >= $searchPrice);
        }

        $groupedRooms = $rooms->groupBy(fn($r) => $r->roomPrice->building->name ?? 'ตึกทั่วไป');
        $thai_billing_month = $this->toThaiDate($billing_month, false);

        return view('admin.invoices.show', compact('groupedRooms', 'billing_month', 'thai_billing_month', 'searchRoom','buildings', 'searchPrice', 'searchStatus','searchBuilding'));
    }

    // เพิ่ม invoice ทีละห้อง
    public function insertInvoiceOne(Request $request)
    {
        $request->merge([
            'billing_month' => $this->convertThaiYearToAD($request->billing_month),
            'issue_date' => $this->convertThaiYearToAD($request->issue_date),
        ]);
        // 1. Validation: ตรวจสอบข้อมูลนำเข้าเบื้องต้น
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'billing_month' => 'required|string|max:7',
            'issue_date' => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            // 2. ป้องกันการสร้างบิลซ้ำในเดือนเดียวกัน
            $exists = Invoice::where('room_id', $request->room_id)
                ->where('billing_month', $request->billing_month)
                ->exists();
            if ($exists) {
                return back()->withErrors(['error' => 'ห้องนี้มีการสร้างบิลสำหรับเดือนนี้ไปแล้ว']);
            }

            // 3. ดึงข้อมูล Room, RoomPrice และ Tenant
            $room = Room::with([
                'roomPrice',
                'tenants' => function ($q) {
                    $q->where('status', 'กำลังใช้งาน');
                }
            ])->findOrFail($request->room_id);

            $tenant = $room->tenants->first();

            // 4. ตรวจสอบความพร้อมของข้อมูลผู้เช่า
            if (!$tenant) {
                throw new \Exception("ไม่พบผู้เช่าที่กำลังใช้งานในห้องนี้ ไม่สามารถสร้างบิลได้");
            }

            // 🌟 5. คำนวณเลขที่บิล และ "วันครบกำหนด" จากตาราง apartment
            $invoiceNumber = 'INV' . str_replace('-', '', $request->billing_month) . '-' . $room->room_number;
            
            // ดึงการตั้งค่าอพาร์ทเม้นท์มาใช้
            $apartmentSettings = DB::table('apartment')->first();
            $dueDay = $apartmentSettings->invoice_due_day ?? 5; // ถ้าไม่มีค่า ให้ default เป็นวันที่ 5
            
            // นำเดือนบิล + 1 เดือน แล้วเซ็ตวันที่ตามที่ตั้งค่าไว้
            $dueDate = \Carbon\Carbon::parse($request->billing_month)->addMonth()->startOfMonth()->setDay($dueDay);

            // 6. สร้าง Invoice หลัก
            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'user_id' => Auth::id(), // ระบุตัวตน Admin 
                'invoice_number' => $invoiceNumber,
                'billing_month' => $request->billing_month,
                'issue_date' => $request->issue_date,
                'total_amount' => 0, // รออัปเดตหลังจากคำนวณรายการย่อย
                'status' => 'กรุณาส่งบิล',
                'due_date' => $dueDate,
            ]);

            $totalAmount = 0;
            $expenses = TenantExpense::all(); // โหลดรายการค่าใช้จ่ายทั้งหมด

            // 7. รายการที่ 1: ค่าเช่าห้อง
            $roomPrice = $room->price ?? 0;
            InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'name' => 'ค่าเช่าห้อง',
                'quantity' => 1,
                'price_per_unit' => $roomPrice,
                'subtotal' => $roomPrice
            ]);
            $totalAmount += $roomPrice;

            // 🌟 8. รายการที่ 2: ค่าน้ำ/ค่าไฟ (อ้างอิงจาก ID 1=ไฟ, 2=น้ำ)
            $meterReadings = MeterReading::where('room_id', $room->id)
                ->where('billing_month', $request->billing_month)
                ->get();

            foreach ($meterReadings as $reading) {
                // เช็คว่ามิเตอร์นี้เป็นประเภทไหน แล้วกำหนด ID ที่ต้องไปค้นหา
                $expenseId = ($reading->meter_type == 'water') ? 2 : 1; 
                $fallbackName = ($reading->meter_type == 'water') ? 'ค่าน้ำ' : 'ค่าไฟ';
                
                // ค้นหา Expense ด้วย ID แทน Name
                $tenant_expense = $expenses->where('id', $expenseId)->first();
                $rate = $tenant_expense->price ?? 0;
                $sub = $reading->units_used * $rate;

                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'tenant_expense_id' => $tenant_expense->id ?? null,
                    'meter_reading_id' => $reading->id,
                    'name' => $tenant_expense->name ?? $fallbackName, // ใช้ชื่อจาก DB ถ้าลบไปใช้ชื่อ default
                    'previous_unit' => $reading->previous_value,
                    'current_unit' => $reading->current_value,
                    'quantity' => $reading->units_used,
                    'price_per_unit' => $rate,
                    'subtotal' => $sub
                ]);
                $totalAmount += $sub;
            }

            // ดึงค่าลิมิตจำนวนคนพักฟรีจาก Settings
            $freeLimit = $apartmentSettings->free_resident_limit ?? 2;
            // 9. รายการที่ 3: ค่าคนมาอาศัยเพิ่ม (เช็คจาก ID 5)
            if ($tenant->resident_count > $freeLimit) {
                $extraPeople = $tenant->resident_count - $freeLimit;
                $extraExpense = $expenses->where('id', 5)->first();
                $pricePerPerson = $extraExpense->price ?? 400.00;

                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'tenant_expense_id' => $extraExpense->id ?? null,
                    'name' => ($extraExpense->name ?? 'คนมาอาศัยเพิ่ม') . ' (ส่วนเกิน ' . $extraPeople . ' คน)',
                    'quantity' => $extraPeople,
                    'price_per_unit' => $pricePerPerson,
                    'subtotal' => $extraPeople * $pricePerPerson
                ]);
                $totalAmount += ($extraPeople * $pricePerPerson);
            }

            // 10. รายการที่ 4: ค่าที่จอดรถ (เช็คจาก ID 3)
            if ($tenant->has_parking) {
                $parking = $expenses->where('id', 3)->first(); 
                if ($parking) {
                    InvoiceDetail::create([
                        'invoice_id' => $invoice->id,
                        'tenant_expense_id' => $parking->id,
                        'name' => $parking->name,
                        'quantity' => 1,
                        'price_per_unit' => $parking->price,
                        'subtotal' => $parking->price
                    ]);
                    $totalAmount += $parking->price;
                }
            }

            // 11. อัปเดตยอดรวมเงินสุทธิใน Invoice หลัก
            $invoice->update(['total_amount' => $totalAmount]);

            DB::commit();
            return back()->with('success', 'สร้างบิลค่าเช่าห้อง ' . $room->room_number . ' สำเร็จ')->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    // create ใบทั้งหมดในเดือนนั้นๆ Logic หลักในการสร้างใบแจ้งหนี้
    public function insertInvoicesAll(Request $request)
    {
        $request->merge([
            'billing_month' => $this->convertThaiYearToAD($request->billing_month),
            'issue_date' => $this->convertThaiYearToAD($request->issue_date),
        ]);
        // 1. Validation ข้อมูลนำเข้า
        $request->validate([
            'billing_month' => 'required|string|max:7', // เช่น 2026-01
            'issue_date' => 'required|date'
        ]);

        $billing_month = $request->billing_month;
        $issue_date = $request->issue_date;

        try {
            DB::beginTransaction();

            // 🌟 ดึงการตั้งค่าอพาร์ทเม้นท์ครั้งเดียว เพื่อส่งเข้าไปในลูป ประหยัดการ Query
            $apartmentSettings = DB::table('apartment')->first();

            // ดึงเฉพาะห้องที่มีสถานะ "มีผู้เช่า"
            $rooms = Room::where('status', 'มีผู้เช่า')->get();
            $count = 0;

            foreach ($rooms as $room) {
                // เรียกใช้ Logic การสร้างบิลทีละห้อง พร้อมส่ง $apartmentSettings ไปด้วย
                $result = $this->generateInvoiceLogic($room->id, $billing_month, $issue_date, $apartmentSettings);
                if ($result) {
                    $count++;
                }
            }

            DB::commit();

            if ($count > 0) {
                return back()->with('success', "สร้างบิลสำเร็จจำนวน $count ห้อง (เฉพาะห้องที่จดมิเตอร์ครบแล้ว)")->withInput();
            } else {
                return back()->withErrors(['error' => "ไม่มีห้องใดที่ตรงตามเงื่อนไข (อาจจดมิเตอร์ไม่ครบ หรือมีบิลอยู่แล้ว)"])->withInput();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    // 🌟 รับพารามิเตอร์ $apartmentSettings เพิ่มเข้ามา
    private function generateInvoiceLogic($roomId, $billingMonth, $issueDate, $apartmentSettings)
    {
        // 1. ป้องกันการสร้างบิลซ้ำในเดือนเดียวกัน
        $exists = Invoice::where('room_id', $roomId)
            ->where('billing_month', $billingMonth)
            ->exists();
        if ($exists)
            return null;

        // 2. ตรวจสอบความพร้อมของข้อมูลมิเตอร์ (ต้องมีทั้งค่าน้ำและค่าไฟ)
        $meterReadings = MeterReading::where('room_id', $roomId)
            ->where('billing_month', $billingMonth)
            ->get();

        if (
            $meterReadings->where('meter_type', 'water')->isEmpty() ||
            $meterReadings->where('meter_type', 'electric')->isEmpty()
        ) {
            return null; // ข้ามห้องที่ยังจดมิเตอร์ไม่ครบ
        }

        // 3. ดึงข้อมูลห้อง ผู้เช่า และราคามาตรฐาน
        $room = Room::with([
            'roomPrice',
            'tenants' => function ($q) {
                $q->where('status', 'กำลังใช้งาน');
            }
        ])->findOrFail($roomId);

        $tenant = $room->tenants->first();
        if (!$tenant)
            return null;

        $expenses = TenantExpense::all(); // โหลดรายการค่าใช้จ่ายมาตรฐาน

        // 🌟 4. คำนวณข้อมูลบิล และวันครบกำหนดจาก Settings
        $invoiceNumber = 'INV' . str_replace('-', '', $billingMonth) . '-' . $room->room_number;
        
        $dueDay = $apartmentSettings->invoice_due_day ?? 5;
        $dueDate = \Carbon\Carbon::parse($billingMonth)->addMonth()->startOfMonth()->setDay($dueDay);

        // 5. สร้าง Invoice หลัก
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'user_id' => Auth::id(), // ระบุตัวตน Admin 
            'invoice_number' => $invoiceNumber,
            'billing_month' => $billingMonth,
            'issue_date' => $issueDate, 
            'total_amount' => 0, // รออัปเดตท้ายสุด
            'status' => 'กรุณาส่งบิล',
            'due_date' => $dueDate,
        ]);

        $totalAmount = 0;

        // 6. เพิ่มรายการย่อย (InvoiceDetails) -----------------------------------

        // รายการที่ 1: ค่าเช่าห้อง
        $roomPrice = $room->price ?? 0;
        InvoiceDetail::create([
            'invoice_id' => $invoice->id,
            'name' => 'ค่าเช่าห้อง',
            'quantity' => 1,
            'price_per_unit' => $roomPrice,
            'subtotal' => $roomPrice
        ]);
        $totalAmount += $roomPrice;

        // 🌟 รายการที่ 2: ค่าน้ำ/ค่าไฟ (อ้างอิงจาก ID 1=ไฟ, 2=น้ำ)
        foreach ($meterReadings as $reading) {
            $expenseId = ($reading->meter_type == 'water') ? 2 : 1;
            $fallbackName = ($reading->meter_type == 'water') ? 'ค่าน้ำ' : 'ค่าไฟ';

            $expense = $expenses->where('id', $expenseId)->first();
            $rate = $expense->price ?? 0;
            $sub = $reading->units_used * $rate;

            InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'tenant_expense_id' => $expense->id ?? null,
                'meter_reading_id' => $reading->id,
                'name' => $expense->name ?? $fallbackName,
                'previous_unit' => $reading->previous_value,
                'current_unit' => $reading->current_value,
                'quantity' => $reading->units_used,
                'price_per_unit' => $rate,
                'subtotal' => $sub
            ]);
            $totalAmount += $sub;
        }
        // ดึงค่าลิมิตจำนวนคนพักฟรีจาก Settings
        $freeLimit = $apartmentSettings->free_resident_limit ?? 2;
        // รายการที่ 3: ค่าคนมาอาศัยเพิ่ม (ส่วนเกินจาก 2 คน)
        if ($tenant->resident_count > $freeLimit) {
            $extraPeople = $tenant->resident_count - $freeLimit;
            $extraExpense = $expenses->where('id', 5)->first(); // สมมติ ID 5 คือค่าคนเพิ่ม
            $pricePerPerson = $extraExpense->price ?? 400.00;

            InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'tenant_expense_id' => $extraExpense->id ?? null,
                'name' => ($extraExpense->name ?? 'คนมาอาศัยเพิ่ม') . " (ส่วนเกิน $extraPeople คน)",
                'quantity' => $extraPeople,
                'price_per_unit' => $pricePerPerson,
                'subtotal' => $extraPeople * $pricePerPerson
            ]);
            $totalAmount += ($extraPeople * $pricePerPerson);
        }

        // รายการที่ 4: ค่าที่จอดรถ
        if ($tenant->has_parking) {
            $parking = $expenses->where('id', 3)->first(); // สมมติ ID 3 คือค่าจอดรถ
            if ($parking) {
                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'tenant_expense_id' => $parking->id,
                    'name' => $parking->name,
                    'quantity' => 1,
                    'price_per_unit' => $parking->price,
                    'subtotal' => $parking->price
                ]);
                $totalAmount += $parking->price;
            }
        }

        // 7. อัปเดตยอดรวมเงินสุทธิใน Invoice
        $invoice->update(['total_amount' => $totalAmount]);

        return $invoice;
    }
    // ---------
    // create มิเตอร์ 1 อัน ต่อ 1 เดือน
    public function insertInvoiceMeterReadingOne(Request $request)
    {
        $request->validate([
            'billing_month' => 'required',
            'room_id' => 'required',
            'tenant_id' => 'required',
            'water_current' => 'required|numeric|min:0',
            'electric_current' => 'required|numeric|min:0',
            'reading_date' => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            $roomId = $request->room_id;
            $tenantId = $request->tenant_id;
            $month = $request->billing_month;

            // ข้อมูลมิเตอร์จาก Modal
            $meters = [
                'water' => [
                    'prev' => (float) $request->water_prev,
                    'current' => (float) $request->water_current,
                ],
                'electric' => [
                    'prev' => (float) $request->electric_prev,
                    'current' => (float) $request->electric_current,
                ],
            ];

            foreach ($meters as $type => $values) {
                // ใช้ updateOrCreate เพื่อป้องกันข้อมูลซ้ำในเดือนเดียวกัน
                MeterReading::updateOrCreate(
                    [
                        'room_id' => $roomId,
                        'meter_type' => $type,
                        'billing_month' => $month,
                    ],
                    [
                        'tenant_id' => $tenantId,
                        'previous_value' => $values['prev'],
                        'current_value' => $values['current'],
                        'units_used' => $values['current'] - $values['prev'],
                        'reading_date' => $request->reading_date,
                    ]
                );
            }

            DB::commit();
            
            return back()->with('success', 'บันทึกเลขมิเตอร์ห้อง ' . $request->room_number . ' สำเร็จ')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()])->withInput();
        }
    }

    // ส่งแจ้งเตือนให้ผู้เช่าชำระเงิน ในรอบเดือนนั้น
    public function sendInvoiceOne(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required'
        ]);
        try {
            DB::beginTransaction();
            // ดึงข้อมูลบิลขึ้นมาก่อนอัปเดต
            $invoice = Invoice::findOrFail($request->invoice_id);
            $invoice->update([
                'status' => 'ค้างชำระ'
            ]);
            DB::commit();
            // 🌟 ส่งแจ้งเตือน LINE (ส่งบิลใหม่)
            $this->sendLineNotification($invoice, 'send');
            // 🔔 Reverb real-time
            try { InvoiceSent::dispatch($invoice->fresh()); } catch (\Throwable $e) {}
            return back()->with('success', 'ส่งบิลสำเร็จ')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด ' . $e->getMessage()])->withInput();
        }
    }

    // ส่งแจ้งเตือนให้ผู้เช่าชำระเงินทั้งหมดในรอบเดือนนั้น
    public function sendInvoiceAll(Request $request)
    {
        $request->merge(['billing_month' => $this->convertThaiYearToAD($request->billing_month)]);
        $request->validate([
            'billing_month' => 'required|string|max:7'
        ]);
        try {
            DB::beginTransaction();
            
            // 🌟 ดึงข้อมูลบิลทั้งหมดที่เข้าเงื่อนไขออกมาก่อน เพื่อเอาไปส่งไลน์ทีละคน
            $invoices = Invoice::where('billing_month', $request->billing_month)
                ->where('status', 'กรุณาส่งบิล')
                ->get();

            if ($invoices->count() > 0) {
                // อัปเดตสถานะทั้งหมดในฐานข้อมูล
                Invoice::whereIn('id', $invoices->pluck('id'))->update(['status' => 'ค้างชำระ']);
                DB::commit();

                // 🌟 วนลูปส่งไลน์หาผู้เช่าทีละห้อง
                $sentCount = 0;
                foreach ($invoices as $invoice) {
                    $this->sendLineNotification($invoice, 'send');
                    // 🔔 Reverb real-time
                    try { InvoiceSent::dispatch($invoice); } catch (\Throwable $e) {}
                    $sentCount++;
                }

                return back()->with('success', "ส่งบิลสำเร็จทั้งหมด $sentCount ห้อง และแจ้งเตือนผ่าน LINE เรียบร้อยแล้ว")->withInput();
            } else {
                DB::rollBack();
                return back()->withErrors(['error' => 'ไม่พบบิลที่พร้อมส่งในรอบเดือนนี้ หรือบิลถูกส่งไปหมดแล้ว'])->withInput();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด ' . $e->getMessage()])->withInput();
        }
    }

    // อ่านรายละเอียด invoice
    public function readInvoiceDetails($id)
    {
        // 1. ดึงข้อมูลใบแจ้งหนี้พร้อมความสัมพันธ์
        $invoice = Invoice::with([
            'details',
            'tenant',
            'tenant.room',
            'tenant.room.roomPrice',
            'details.meterReading'
        ])->findOrFail($id);

        // 2. จัดการวันที่ภาษาไทยโดยเรียกใช้ฟังก์ชัน toThaiDate

        // แปลง billing_month (ประจำเดือน) -> มกราคม 2569 (ไม่เอาวัน)
        $invoice->thai_billing_month = $this->toThaiDate($invoice->billing_month, false);

        // แปลง issue_date (วันที่ออกบิล) -> 12 มกราคม 2569
        $invoice->thai_issue_date = $this->toThaiDate($invoice->issue_date);

        // แปลง due_date (กำหนดชำระ) -> 05 กุมภาพันธ์ 2569
        $invoice->thai_due_date = $this->toThaiDate($invoice->due_date);

        // หาวันที่จดมิเตอร์จากรายการแรกที่มีข้อมูล
        $firstReading = $invoice->details->whereNotNull('meter_reading_id')->first();
        $invoice->thai_reading_date = ($firstReading && $firstReading->meterReading)
            ? $this->toThaiDate($firstReading->meterReading->reading_date)
            : '-';

        // 3. แปลงยอดรวมเป็นตัวอักษรภาษาไทย
        $invoice->total_amount_thai = $this->bahtText($invoice->total_amount);

        // 4. ดึงข้อมูลอพาร์ทเม้นท์
        $apartment = DB::table('apartment')->first();

        return view('admin.invoices.invoice_details', compact('invoice', 'apartment'));
    }
    // ปริ้นใบ invoice PDF
    public function printInvoiceDetails($id)
    {
        // 1. ดึงข้อมูล (ใช้ Logic เดียวกับ readInvoiceDetails)
        $invoice = Invoice::with(['details', 'tenant', 'tenant.room', 'details.meterReading'])->findOrFail($id);
        $invoice->thai_billing_month = $this->toThaiDate($invoice->billing_month, false);
        $invoice->thai_issue_date = $this->toThaiDate($invoice->issue_date);
        $invoice->thai_due_date = $this->toThaiDate($invoice->due_date);

        $firstReading = $invoice->details->whereNotNull('meter_reading_id')->first();
        $invoice->thai_reading_date = ($firstReading && $firstReading->meterReading)
            ? $this->toThaiDate($firstReading->meterReading->reading_date) : '-';

        $invoice->total_amount_thai = $this->bahtText($invoice->total_amount);
        $apartment = DB::table('apartment')->first();

        // 2. โหลด View และตั้งค่ากระดาษ // กำหนดขนาดกระดาษแบบ Custom [0, 0, ความกว้าง, ความสูง]
        // 9 นิ้ว x 72 = 648 pt
        // 11 นิ้ว x 72 = 792 pt
        $customPaper = [0, 0, 648, 792];
        $pdf = Pdf::loadView('admin.invoices.pdf.print_pdf_invoiceDetails', compact('invoice', 'apartment'))
            ->setPaper($customPaper, 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        // 3. ส่งไฟล์ให้ Browser แสดงผล (Stream) หรือดาวน์โหลด (Download)
        return $pdf->stream('invoice_' . $invoice->invoice_number . '.pdf');
    }
    // PDF ใบเสร็จรับเงิน ทั้งหมดในรอบเดือนนั้นๆ
    
    public function printInvoiceDetailsAll(Request $request)
    {
        $request->merge(['billing_month' => $this->convertThaiYearToAD($request->query('billing_month'))]);
        $billing_month = $request->query('billing_month');

        // 1. ดึงข้อมูลใบแจ้งหนี้ทั้งหมดในเดือนที่เลือก
        $invoices = Invoice::with(['details', 'tenant', 'tenant.room', 'details.meterReading'])
            ->where('billing_month', $billing_month)
            ->get();

        // ตรวจสอบว่ามีข้อมูลหรือไม่
        if ($invoices->isEmpty()) {
            return back()->withErrors(['error' => 'ไม่พบใบแจ้งหนี้ในรอบเดือนนี้']);
        }

        $apartment = DB::table('apartment')->first();

        // 2. วนลูปจัดการวันที่และตัวหนังสือภาษาไทยให้บิลทุกใบ
        foreach ($invoices as $invoice) {
            $invoice->thai_billing_month = $this->toThaiDate($invoice->billing_month, false);
            $invoice->thai_issue_date = $this->toThaiDate($invoice->issue_date);
            $invoice->thai_due_date = $this->toThaiDate($invoice->due_date);

            $firstReading = $invoice->details->whereNotNull('meter_reading_id')->first();
            $invoice->thai_reading_date = ($firstReading && $firstReading->meterReading)
                ? $this->toThaiDate($firstReading->meterReading->reading_date) : '-';

            $invoice->total_amount_thai = $this->bahtText($invoice->total_amount);
        }

        // 3. โหลด View ใหม่สำหรับพิมพ์หลายใบ
        //  โหลด View และตั้งค่ากระดาษ // กำหนดขนาดกระดาษแบบ Custom [0, 0, ความกว้าง, ความสูง]
        // 9 นิ้ว x 72 = 648 pt
        // 11 นิ้ว x 72 = 792 pt
        $customPaper = [0, 0, 648, 792];
        $pdf = Pdf::loadView('admin.invoices.pdf.print_pdf_invoice_Details_All', compact('invoices', 'apartment'))
            ->setPaper($customPaper, 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        // 4. แสดงไฟล์ PDF
        return $pdf->stream('invoices_all_' . $billing_month . '.pdf');
    }
    // ดึงข้อมูลสำหรับหน้าแก้ไข

    public function editInvoiceDetails($id)
    {
        $invoice = Invoice::with(['details', 'tenant.room'])->findOrFail($id);
        $apartment = DB::table('apartment')->first();

        // ดึงรายการค่าใช้จ่ายทั้งหมดเพื่อทำ Dropdown 
        // WhereNotin ยกเว้นไม่เลือก id นั้นๆ
        $expenses = TenantExpense::whereNotIn('id', [1, 2])->orderBy('id', 'asc')->get();
        $thai_billing_month = $this->toThaiDate($invoice->billing_month, false);
        return view('admin.invoices.edit_details', compact('invoice', 'apartment', 'expenses', 'thai_billing_month'));
    }

    // บันทึกการแก้ไข details
    public function updateInvoiceDetails(Request $request, $id)
    {
        $request->merge([
            'issue_date' => $this->convertThaiYearToAD($request->issue_date),
            'due_date' => $this->convertThaiYearToAD($request->due_date),
        ]);
        // แนะนำให้เพิ่ม Validate เพื่อความปลอดภัย
        $request->validate([
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
            'items' => 'required|array',
        ]);
        try {
            DB::beginTransaction();
            $invoice = Invoice::findOrFail($id);
            $invoice->details()->delete(); // ลบรายการเดิม

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $subtotal = (float) $item['quantity'] * (float) $item['price'];

                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'tenant_expense_id' => $item['expense_id'] ?? null,
                    'meter_reading_id' => $item['meter_reading_id'] ?? null, // รักษา ID มิเตอร์ไว้
                    'name' => $item['name'],
                    'previous_unit' => $item['previous_unit'] ?? null,
                    'current_unit' => $item['current_unit'] ?? null,
                    'quantity' => $item['quantity'],
                    'price_per_unit' => $item['price'],
                    'subtotal' => $subtotal,
                ]);
                $totalAmount += $subtotal;
            }

            $invoice->update([
                'total_amount' => $totalAmount, // ยอดรวม
                'issue_date' => $request->issue_date, // บันทึกเวลาใหม่
                'due_date' => $request->due_date
            ]);
            DB::commit();
            // 🌟 เช็คว่าถ้าสถานะถูกส่งไปแล้ว (ค้างชำระ) ให้ส่งไลน์แจ้งเตือนการแก้ไข
            if (in_array($invoice->status, ['ค้างชำระ', 'ชำระบางส่วน'])) {
                // ดึงข้อมูลล่าสุดหลังอัปเดตเพื่อส่งไลน์
                $invoice->refresh(); 
                $this->sendLineNotification($invoice, 'update');
            }
            return redirect()->route('admin.invoices.details', $id)->with('success', 'แก้ไขข้อมูลสำเร็จ');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
// ------------------------

    // รายงานการเก็บเงิน
    public function invoiceCollectionReport(Request $request)
    {
        $request->merge(['billing_month' => $this->convertThaiYearToAD($request->billing_month)]);
        $billing_month = $request->billing_month ?? date('Y-m');
        $status_filter = $request->input('status_filter', ['มีผู้เช่า', 'ว่าง']);
        
        // รับค่าเช็คบ็อกซ์ เปิด/ปิด คอลัมน์สถานะ
        $show_payment_status = empty($request->all()) ? true : $request->has('show_payment_status');

        $allExpenseSettings = TenantExpense::select('name')->get();
        if (!$allExpenseSettings->contains('name', 'ค่าเช่าห้อง')) {
            $allExpenseSettings->prepend((object) ['name' => 'ค่าเช่าห้อง']);
        }
        $show_columns = $request->input('show_columns', $allExpenseSettings->pluck('name')->toArray());

        //  1. ดึงห้องทั้งหมดมาก่อน (ไม่ใช้ whereIn เพื่อป้องกันบัคสถานะปัจจุบัน)
        $rooms = Room::with([
            'tenants' => function ($q) {
                $q->where('status', 'กำลังใช้งาน');
            }
        ])->get();

        $invoices = Invoice::with(['details', 'payments', 'tenant'])
            ->where('billing_month', $billing_month)
            ->get()
            ->keyBy('room_id');

        $rooms->each(function ($room) use ($invoices, $allExpenseSettings) {
            $invoice = $invoices->get($room->id);
            $room->current_invoice = $invoice;
            
            //  2. เช็คว่าในเดือนนี้มีบิลหรือไม่ ถ้ามี = เช่า, ถ้าไม่มี = ว่าง
            if ($invoice && $invoice->tenant) {
                $room->report_tenant_name = $invoice->tenant->first_name . ' ' . $invoice->tenant->last_name;
                $room->report_is_occupied = true; // ถือว่ามีการเช่าในเดือนนี้
            } else {
                $room->report_tenant_name = '-';
                $room->report_is_occupied = false; // ไม่มีบิล = ห้องว่างในเดือนนี้
            }

            // จัดการสถานะการชำระเงิน (แสดงแค่ ชำระแล้ว / ชำระบางส่วน)
            if ($invoice && in_array($invoice->status, ['ชำระแล้ว', 'ชำระบางส่วน'])) {
                $room->payment_status = $invoice->status;
            } else {
                $room->payment_status = '-';
            }

            $detailsMap = collect();
            if ($invoice) {
                foreach ($invoice->details as $detail) {
                    $matchedCategory = $allExpenseSettings->first(function ($setting) use ($detail) {
                        return str_contains($detail->name, $setting->name);
                    });
                    $key = $matchedCategory ? $matchedCategory->name : $detail->name;
                    $detailsMap[$key] = ($detailsMap[$key] ?? 0) + $detail->subtotal;
                }
            }

            $room->expense_details = $detailsMap;
            $lastPayment = $invoice ? $invoice->payments->where('status', 'active')->sortByDesc('payment_date')->first() : null;
            $room->payment_date_display = $lastPayment ? $this->toThaiDate($lastPayment->payment_date, true, true) : '-';
        });

        //  3. กรองห้องตาม Checkbox (อิงจากสถานะเดือนนั้นๆ ที่คำนวณมา)
        $rooms = $rooms->filter(function ($room) use ($status_filter) {
            $month_status = $room->report_is_occupied ? 'มีผู้เช่า' : 'ว่าง';
            return in_array($month_status, $status_filter);
        })->values(); // Reset Index หลังจาก Filter เสร็จ

        $thai_month = $this->toThaiDate($billing_month, false);

        return view('admin.invoices.collection_report', compact(
            'rooms', 'billing_month', 'thai_month', 'status_filter', 
            'allExpenseSettings', 'show_columns', 'show_payment_status'
        ));
    }

    // PDF รายงานการเก็บเงิน
    public function printCollectionReportPdf(Request $request)
    {
        $request->merge(['billing_month' => $this->convertThaiYearToAD($request->billing_month)]);
        $billing_month = $request->billing_month ?? date('Y-m');
        $status_filter = $request->input('status_filter', ['มีผู้เช่า', 'ว่าง']);
        
        $show_payment_status = empty($request->all()) ? true : $request->has('show_payment_status');

        $allExpenseSettings = TenantExpense::select('name')->get();
        if (!$allExpenseSettings->contains('name', 'ค่าเช่าห้อง')) {
            $allExpenseSettings->prepend((object) ['name' => 'ค่าเช่าห้อง']);
        }
        $show_columns = $request->input('show_columns', $allExpenseSettings->pluck('name')->toArray());

        //  1. ดึงห้องทั้งหมดมาก่อน
        $rooms = Room::with([
            'tenants' => function ($q) {
                $q->where('status', 'กำลังใช้งาน');
            }
        ])->get();

        $invoices = Invoice::with(['details', 'payments', 'tenant'])
            ->where('billing_month', $billing_month)
            ->get()
            ->keyBy('room_id');

        $rooms->each(function ($room) use ($invoices) {
            $invoice = $invoices->get($room->id);
            
            //  2. อิงสถานะจากบิลเหมือนหน้าเว็บ
            if ($invoice && $invoice->tenant) {
                $room->report_tenant_name = $invoice->tenant->first_name . ' ' . $invoice->tenant->last_name;
                $room->report_is_occupied = true;
            } else {
                $room->report_tenant_name = '-';
                $room->report_is_occupied = false;
            }

            if ($invoice && in_array($invoice->status, ['ชำระแล้ว', 'ชำระบางส่วน'])) {
                $room->payment_status = $invoice->status;
            } else {
                $room->payment_status = '-';
            }
            
            $room->expense_details = $invoice ? $invoice->details->groupBy('name')->map->sum('subtotal') : collect();
            $lastPayment = $invoice ? $invoice->payments->where('status', 'active')->sortByDesc('payment_date')->first() : null;
            $room->payment_date_display = $lastPayment ? $this->toThaiDate($lastPayment->payment_date, true, true) : '-';
        });

        //  3. กรองห้องก่อน Print PDF
        $rooms = $rooms->filter(function ($room) use ($status_filter) {
            $month_status = $room->report_is_occupied ? 'มีผู้เช่า' : 'ว่าง';
            return in_array($month_status, $status_filter);
        })->values();

        $thai_month = $this->toThaiDate($billing_month, false);
        $apartment = DB::table('apartment')->first();

        $pdf = Pdf::loadView('admin.invoices.pdf.print_collection_report_pdf', compact(
            'rooms', 'billing_month', 'thai_month', 'status_filter', 
            'show_columns', 'apartment', 'show_payment_status'
        ))->setPaper('a4', 'landscape')->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('collection_report_' . $billing_month . '.pdf');
    }

    // excel
    public function exportCollectionReportExcel(Request $request)
    {
        $request->merge(['billing_month' => $this->convertThaiYearToAD($request->billing_month)]);
        // 1. ก๊อปปี้โค้ดดึงข้อมูลทั้งหมดจาก printCollectionReportPdf มาใส่ตรงนี้
        $billing_month = $request->billing_month ?? date('Y-m');
        $status_filter = $request->input('status_filter', ['มีผู้เช่า', 'ว่าง']);
        $show_payment_status = empty($request->all()) ? true : $request->has('show_payment_status');

        $allExpenseSettings = TenantExpense::select('name')->get();
        if (!$allExpenseSettings->contains('name', 'ค่าเช่าห้อง')) {
            $allExpenseSettings->prepend((object) ['name' => 'ค่าเช่าห้อง']);
        }
        $show_columns = $request->input('show_columns', $allExpenseSettings->pluck('name')->toArray());

        $rooms = Room::with(['tenants' => fn($q) => $q->where('status', 'กำลังใช้งาน')])->get();
        $invoices = Invoice::with(['details', 'payments', 'tenant'])->where('billing_month', $billing_month)->get()->keyBy('room_id');

        $rooms->each(function ($room) use ($invoices) {
            $invoice = $invoices->get($room->id);
            if ($invoice && $invoice->tenant) {
                $room->report_tenant_name = $invoice->tenant->first_name . ' ' . $invoice->tenant->last_name;
                $room->report_is_occupied = true;
            } else {
                $room->report_tenant_name = '-';
                $room->report_is_occupied = false;
            }

            if ($invoice && in_array($invoice->status, ['ชำระแล้ว', 'ชำระบางส่วน'])) {
                $room->payment_status = $invoice->status;
            } else {
                $room->payment_status = '-';
            }
            $room->expense_details = $invoice ? $invoice->details->groupBy('name')->map->sum('subtotal') : collect();
            $lastPayment = $invoice ? $invoice->payments->where('status', 'active')->sortByDesc('payment_date')->first() : null;
            $room->payment_date_display = $lastPayment ? $this->toThaiDate($lastPayment->payment_date, true, true) : '-';
        });

        $rooms = $rooms->filter(function ($room) use ($status_filter) {
            $month_status = $room->report_is_occupied ? 'มีผู้เช่า' : 'ว่าง';
            return in_array($month_status, $status_filter);
        })->values();

        $thai_month = $this->toThaiDate($billing_month, false);
        $apartment = DB::table('apartment')->first();

        // 2. แพ็กข้อมูลลง Array และส่งให้ Excel
        $data = compact('rooms', 'billing_month', 'thai_month', 'status_filter', 'show_columns', 'apartment', 'show_payment_status');
        
        return Excel::download(new CollectionReportExport($data), 'Collection_Report_' . $billing_month . '.xlsx');
    }

    // ------------------------
    

    // ลบใบแจ้งหนี้
    public function deleteInvoiceOne($id)
    {
        try {
            DB::beginTransaction();
            DB::table('invoices')->where('id', $id)->delete();
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return back()->with('success', 'ลบใบแจ้งหนี้สำเร็จ')->withInput();
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();

        }
    }

    // =========================================================
    //  ฟังก์ชันตัวช่วย (ห้ามลืมก๊อปไปวางล่างสุดของ Class)
    // =========================================================
    private function convertThaiYearToAD($dateString)
    {
        if (!$dateString) return null;
        try {
            $parts = explode('-', $dateString);
            if (count($parts) >= 2) {
                $year = (int) $parts[0];
                if ($year > 2400) {
                    $year = $year - 543;
                    $parts[0] = $year;
                    return implode('-', $parts);
                }
            }
        } catch (\Exception $e) { return $dateString; }
        return $dateString;
    }

    // =========================================================================
    //  ฟังก์ชัน Private สำหรับส่ง LINE โดยเฉพาะ (เพิ่มไว้ล่างสุดใน Controller)
    // =========================================================================
    private function sendLineNotification($invoice, $actionType = 'send')
    {
        try {
            // โหลดข้อมูลผู้เช่าและห้องที่เชื่อมโยงกับบิลนี้
            $invoice->loadMissing(['tenant.room']);
            $tenant = $invoice->tenant;

            // ถ้าไม่มีผู้เช่า หรือผู้เช่ายังไม่ได้ผูก LINE ให้ข้ามไปเลย
            if (!$tenant || !$tenant->line_id) {
                return false; 
            }

            $channelAccessToken = env('LINE_BOT_CHANNEL_ACCESS_TOKEN');
            $roomNumber = $tenant->room->room_number ?? '-';
            $billingMonth = $this->toThaiDate($invoice->billing_month, false); // เปลี่ยน Format วันที่ตรงนี้ได้ตามต้องการ
            $totalAmount = number_format($invoice->total_amount, 2);
            $dueDate = $this->toThaiDate($invoice->due_date, true);

            // จัดเตรียมข้อความตามประเภท Action
            if ($actionType === 'update') {
                $messageText = "⚠️ มีการอัปเดต/แก้ไข บิลค่าเช่าห้อง {$roomNumber}\n";
                $messageText .= "📅 ประจำเดือน: {$billingMonth}\n";
                $messageText .= "💰 ยอดรวมใหม่: {$totalAmount} บาท\n";
                $messageText .= "กรุณาตรวจสอบรายละเอียดบิลอีกครั้งผ่านเมนู 'เข้าสู่เว็บไซต์' ครับ";
            } else {
                $messageText = "🧾 บิลค่าเช่าห้อง {$roomNumber} ออกแล้วครับ!\n";
                $messageText .= "📅 ประจำเดือน: {$billingMonth}\n";
                $messageText .= "💰 ยอดที่ต้องชำระ: {$totalAmount} บาท\n";
                $messageText .= "⏳ กำหนดชำระภายใน: {$dueDate}\n";
                $messageText .= "สามารถกดปุ่ม 'เข้าสู่เว็บไซต์' เพื่อดูรายละเอียดได้เลยครับ";
            }

            // ยิง API ไปหา LINE
            Http::withToken($channelAccessToken)->post('https://api.line.me/v2/bot/message/push', [
                'to' => $tenant->line_id,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $messageText
                    ]
                ]
            ]);

            return true;

        } catch (\Exception $e) {
            // บันทึก Error ลง Log ไว้ดูเงียบๆ ไม่ให้ระบบเว็บพัง
            Log::error('Line Notify Error (Invoice): ' . $e->getMessage());
            return false;
        }
    }
}
