<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // เรียกใช้งาน Auth Facade
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\Building;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AccountingTransaction;
use App\Models\AccountingCategory;

// นำเข้า Trait
use App\Traits\FormatHelper;

use Illuminate\Support\Facades\Http; // สำหรับส่ง HTTP Request (แจ้ง LINE)
class TenantController extends Controller
{
    use FormatHelper; // เรียกใช้ Trait ใน Class
    // จัดการผู้เช่า Tenant
    // 1. แสดงรายการผู้เช่าพร้อมตัวกรอง

    public function tenantShow(Request $request)
    {
        // 🌟 1. เปลี่ยนค่าเริ่มต้น (Default) ให้เป็นการเรียงตาม 'room_number' แบบ 'asc' (น้อยไปมาก)
        $sortBy = $request->input('sort_by', 'room_number'); 
        $sortDir = $request->input('sort_dir', 'asc');

        $query = Tenant::with('room.roomPrice.building')
            ->leftJoin('rooms', 'rooms.id', '=', 'tenants.room_id')
            ->leftJoin('room_prices', 'room_prices.id', '=', 'rooms.room_price_id')
            ->leftJoin('buildings', 'buildings.id', '=', 'room_prices.building_id')
            ->select('tenants.*')
            ->whereIn('tenants.status', ['กำลังใช้งาน', 'สิ้นสุดสัญญา'])
            ->when($request->filter_room, function ($q) use ($request) {
                $q->whereHas('room', fn($roomQuery) => $roomQuery->where('room_number', 'like', "%{$request->filter_room}%"));
            })
            ->when($request->filter_building, function ($q) use ($request) {
                $q->where('buildings.id', $request->filter_building);
            })
            ->when($request->filter_name, function ($q) use ($request) {
                $q->where(fn($query) => $query->where('first_name', 'like', "%{$request->filter_name}%")
                    ->orWhere('last_name', 'like', "%{$request->filter_name}%"));
            })
            ->when($request->filter_phone, function ($q) use ($request) {
                $q->where('phone', 'like', "%{$request->filter_phone}%");
            })
            ->when($request->filter_resident_count, function ($q) use ($request) {
                $q->where('resident_count', $request->filter_resident_count);
            })
            ->when($request->filter_start_date, function ($q) use ($request) {
                $q->whereDate('start_date', $request->filter_start_date);
            })
            ->when($request->filter_end_date, function ($q) use ($request) {
                $q->whereDate('end_date', $request->filter_end_date);
            })
            ->when($request->filter_status, function ($q) use ($request) {
                $q->where('tenants.status', $request->filter_status);
            })
            ->when($request->has('filter_parking') && $request->filter_parking !== null, function ($q) use ($request) {
                $q->where('has_parking', $request->filter_parking);
            });

        // 🌟 2. เงื่อนไขการจัดเรียงที่แก้ไขใหม่
        if ($sortBy === 'start_date' || $sortBy === 'end_date') {
            // ถ้าเลือกเรียงตามวันที่
            $query->orderBy("tenants.{$sortBy}", $sortDir)
                ->orderByRaw("CASE WHEN buildings.name LIKE '%2%' THEN 1 WHEN buildings.name LIKE '%4%' THEN 2 WHEN buildings.name LIKE '%5%' THEN 3 ELSE 4 END")
                ->orderByRaw('CAST(rooms.room_number AS UNSIGNED) ASC');
        } else {
            // Default: เรียงตามตึก และ เลขห้อง (ASC หรือ DESC ตามที่กด)
            $query->orderByRaw("CASE WHEN buildings.name LIKE '%2%' THEN 1 WHEN buildings.name LIKE '%4%' THEN 2 WHEN buildings.name LIKE '%5%' THEN 3 ELSE 4 END")
                ->orderByRaw("CAST(rooms.room_number AS UNSIGNED) {$sortDir}");
        }

        $tenants = $query->paginate(10)->withQueryString();

        foreach ($tenants as $tenant) {
            $tenant->thai_start_date = $this->toThaiDate($tenant->start_date, true, true);
            $tenant->thai_end_date = $tenant->end_date ? $this->toThaiDate($tenant->end_date, true, true) : '-';
        }

        $rooms = Room::with('roomPrice.building')->where('status', 'ว่าง')->get();
        $buildings = Building::all();

        // 🌟 3. คำนวณข้อมูลสำหรับการ์ดสรุปยอด (Summary Cards)
        $statActive = Tenant::where('status', 'กำลังใช้งาน')->count();
        $statResidents = Tenant::where('status', 'กำลังใช้งาน')->sum('resident_count');
        $statLine = Tenant::where('status', 'กำลังใช้งาน')->whereNotNull('line_id')->count();
        $statTerminated = Tenant::where('status', 'สิ้นสุดสัญญา')->count();

        return view('admin.tenants.show', compact('tenants', 'rooms', 'buildings', 'statActive', 'statResidents', 'statLine', 'statTerminated'));
    }

    // ฟังก์ชันใหม่ สำหรับเปิดหน้าดูรายละเอียด
    public function tenantDetail($id)
    {
        $tenant = Tenant::with('room.roomPrice.building')->findOrFail($id);
        $tenant->thai_start_date = $this->toThaiDate($tenant->start_date);
        $tenant->thai_end_date = $tenant->end_date ? $this->toThaiDate($tenant->end_date) : '-';
        $tenant->thai_id_card_issue_date = $tenant->id_card_issue_date ? $this->toThaiDate($tenant->id_card_issue_date) : '-';
        $tenant->thai_id_card_expiry_date = $tenant->id_card_expiry_date ? $this->toThaiDate($tenant->id_card_expiry_date) : '-';
        // 1. ดึงบิลค้างชำระ (Invoice) ของผู้เช่าคนนี้
        $pendingInvoices = Invoice::where('tenant_id', $id)
            ->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน', 'ส่งบิลแล้ว'])
            ->orderBy('billing_month', 'desc')
            ->paginate(10, ['*'], 'invoice_page');

        foreach ($pendingInvoices as $inv) {
            $inv->thai_billing_month = $this->toThaiDate($inv->billing_month, false); // แปลงเดือน
            $inv->thai_due_date = $this->toThaiDate($inv->due_date); // แปลงวันครบกำหนด
        }

        // 2. ดึงประวัติการรับเงิน (Payments) ของผู้เช่าคนนี้ (ทั้งค่าเช่า และ มัดจำ)
        $payments = Payment::with(['invoice', 'admin', 'accounting_transactions'])
            ->whereHas('invoice', function ($q) use ($id) {
                // หา Payment ที่ผูกกับ Invoice ของผู้เช่าคนนี้
                $q->where('tenant_id', $id);
            })
            ->orWhereHas('accounting_transactions', function ($q) use ($id) {
                // หรือหา Payment ที่ผูกกับ AccountingTransaction (เงินมัดจำแรกเข้า) ของผู้เช่าคนนี้
                $q->where('tenant_id', $id);
            })
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'payment_page');

        foreach ($payments as $pay) {
            $pay->thai_payment_date = $this->toThaiDate($pay->payment_date);
            
            // สร้าง Title ให้ดูง่ายในตาราง
            if ($pay->invoice) {
                $pay->display_title = "ชำระค่าเช่า รอบ " . $this->toThaiDate($pay->invoice->billing_month, false);
            } else {
                $transaction = $pay->accounting_transactions->first();
                $pay->display_title = $transaction ? $transaction->title : 'เงินมัดจำแรกเข้า';
            }
        }

        return view('admin.tenants.detail', compact('tenant', 'pendingInvoices', 'payments'));
    }

    // 1.5 แสดงฟอร์มลงทะเบียนผู้เช่า (GET)
    public function createTenantForm(Request $request)
    {
        // ดึงห้องที่ว่างเพื่อแสดงเป็นตัวเลือก
        $rooms = Room::with('roomPrice', 'building')->where('status', 'ว่าง')->get();

        // ถ้ามี room_id จาก query parameter ให้ดึง room นั้นมา pre-select
        $selectedRoom = null;
        if ($request->has('room_id')) {
            $selectedRoom = Room::with('roomPrice', 'building')->findOrFail($request->room_id);
        }

        $buildings = Building::all();

        return view('admin.tenants.create', compact('rooms', 'selectedRoom', 'buildings'));
    }

    // 2. ลงทะเบียนผู้เช่าใหม่พร้อมอัปโหลดสัญญาเช่า และรับเงินมัดจำ
    public function insertTenant(Request $request)
    {
        // 🌟 แปลงวันที่จาก พ.ศ. เป็น ค.ศ. ก่อนเริ่ม Validation
        $request->merge([
            'id_card_issue_date' => $this->convertThaiYearToAD($request->id_card_issue_date),
            'id_card_expiry_date' => $this->convertThaiYearToAD($request->id_card_expiry_date),
            'start_date' => $this->convertThaiYearToAD($request->start_date),
            'end_date' => $this->convertThaiYearToAD($request->end_date),
            'deposit_date' => $this->convertThaiYearToAD($request->deposit_date),
        ]);

        DB::beginTransaction();
        try {
            // 🌟 1. นำ 'rental_contract' ออกจาก Validation และเพิ่มคอลัมน์ใหม่เข้าไป
            $request->validate([
                'room_id' => 'required|exists:rooms,id',
                'id_card' => 'nullable',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                
                // 🌟 ข้อมูลที่เพิ่มเข้ามาใหม่
                'age' => 'nullable|integer',
                'id_card_issue_date' => 'nullable|date',
                'id_card_expiry_date' => 'nullable|date',
                'id_card_issue_place' => 'nullable|string|max:255',
                'id_card_issue_province' => 'nullable|string|max:255',
                'street' => 'nullable|string|max:255',
                'alley' => 'nullable|string|max:255',
                'workplace' => 'nullable|string|max:255',

                'address_no' => 'required|string|max:255',
                'moo' => 'required|string|max:3',
                'sub_district' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'postal_code' => 'required|string|max:5',
                'phone' => 'required|digits:10',
                'start_date' => 'required|date',
                'resident_count' => 'required|integer|min:1',
                'deposit_amount' => 'required|numeric|min:1',
                'deposit_payment_method' => 'required|string',
                'deposit_slip' => 'nullable|image|max:2048',
                'deposit_date' => 'required|date', // 🌟 เพิ่ม Validate ให้ deposit_date
            ]);

            // จัดการไฟล์สลิปเงินมัดจำ
            $depositSlipPath = null;
            if ($request->hasFile('deposit_slip')) {
                $file = $request->file('deposit_slip');
                // 🌟 กรณีไม่มี id_card ให้ใช้เบอร์โทรศัพท์เป็นชื่อไฟล์รูปสลิปแทน
                $fileIdentifier = $request->id_card ?: $request->phone;
                $filename = time() . '_' . $fileIdentifier . '_slip.' . $file->getClientOriginalExtension();
                $depositSlipPath = $file->storeAs('slips', $filename, 'public');
            }

            // 🌟 1.5 กำหนดรหัสผ่าน (Password Logic)
            // ถ้ามีเลขบัตร ให้ใช้เลขบัตร ถ้าไม่มี ให้ใช้เบอร์โทรศัพท์
            $passwordSource = $request->id_card ?: $request->phone;

            // 🌟 2. บันทึกข้อมูลผู้เช่า
            $tenant = Tenant::create([
                'room_id' => $request->room_id,
                'id_card' => $request->id_card,
                'password' => Hash::make($passwordSource), // 🌟 ใช้ค่าที่เช็คมาแล้ว
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                
                // 🌟 ข้อมูลที่เพิ่มเข้ามาใหม่
                'age' => $request->age,
                'id_card_issue_date' => $request->id_card_issue_date,
                'id_card_expiry_date' => $request->id_card_expiry_date,
                'id_card_issue_place' => $request->id_card_issue_place,
                'id_card_issue_province' => $request->id_card_issue_province,
                'street' => $request->street,
                'alley' => $request->alley,
                'workplace' => $request->workplace,

                'address_no' => $request->address_no,
                'moo' => $request->moo,
                'sub_district' => $request->sub_district,
                'district' => $request->district,
                'province' => $request->province,
                'postal_code' => $request->postal_code,
                'phone' => $request->phone,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'has_parking' => $request->has('has_parking'),
                'resident_count' => $request->resident_count,
                'deposit_amount' => $request->deposit_amount,
                'deposit_payment_method' => $request->deposit_payment_method,
                'deposit_slip' => $depositSlipPath,
                'rental_contract' => null, 
                'status' => 'กำลังใช้งาน',
            ]);

            // ดึงข้อมูล room เพื่อเอาไปใช้
            $room = Room::with('roomPrice')->where('id', $request->room_id)->first();
            $apartment = DB::table('apartment')->first(); // เผื่อเอาชื่อหอพักไปใส่หัวสัญญา
            
            // 🌟 ดึงข้อมูลจากตาราง tenant_expenses ทั้งหมด
            $tenant_expenses = DB::table('tenant_expenses')->get();
            $room_price_thai = $this->bahtText($room->price ?? 0);
            $deposit_amount_thai = $this->bahtText($request->deposit_amount ?? 0);
            
            // 🌟 3. สร้างเอกสาร PDF สัญญาเช่าอัตโนมัติด้วย DomPDF
            $pdf = Pdf::loadView('admin.tenants.pdf.contract', compact(
                'tenant', 
                'room', 
                'apartment',
                'tenant_expenses',
                'room_price_thai', 
                'deposit_amount_thai'
            ))
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            // 🌟 ตั้งชื่อไฟล์สัญญา: ถ้าไม่มี id_card ให้ใช้เบอร์โทรศัพท์แทน
            $pdfIdentifier = $tenant->id_card ?: $tenant->phone;
            $pdfFilename = time() . '_' . $pdfIdentifier . '_contract.pdf';
            
            $pdfPath = 'contracts/' . $pdfFilename;
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // 🌟 4. อัปเดต Path สัญญากลับเข้าไปที่ผู้เช่า
            $tenant->update(['rental_contract' => $pdfPath]);

            // บันทึกลงตาราง Payments
            $payment = Payment::create([
                'invoice_id' => null,
                'user_id' => Auth::id(),
                'amount_paid' => $request->deposit_amount,
                'payment_date' => $request->deposit_date,
                'payment_method' => $request->deposit_payment_method,
                'slip_image' => $depositSlipPath,
                'note' => 'ชำระเงินมัดจำแรกเข้า ห้อง ' . $room->room_number,
                'status' => 'active'
            ]);

            // บันทึกลง Accounting Transaction
            $category = AccountingCategory::findOrFail(2);
            AccountingTransaction::create([
                'category_id' => $category->id,
                'payment_id' => $payment->id,
                'tenant_id' => $tenant->id,
                'user_id' => Auth::id(),
                'title' => $category->name . " (ห้อง " . $room->room_number . ")",
                'amount' => $request->deposit_amount,
                'entry_date' => $request->deposit_date,
                'description' => "รับเงินมัดจำจาก " . $tenant->first_name . " " . $tenant->last_name,
                'status' => 'active'
            ]);

            // อัปเดตสถานะห้องพัก
            DB::table('rooms')->where('id', $request->room_id)->update([
                'status' => 'มีผู้เช่า',
                'updated_at' => now()
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'ลงทะเบียนผู้เช่าและสร้างสัญญาเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::RollBack();
            return redirect()->back()->withErrors(['error' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage()])->withInput();
        }
    }

    private function convertThaiYearToAD($dateString)
    {
        if (!$dateString) return null;

        try {
            // แยกส่วนประกอบของวันที่
            $parts = explode('-', $dateString);
            if (count($parts) === 3) {
                $year = (int) $parts[0];
                // ถ้าปีมีค่ามากกว่า 2400 (ซึ่งเป็น พ.ศ. แน่นอน) ให้ลบออก 543
                if ($year > 2400) {
                    $year = $year - 543;
                    return $year . '-' . $parts[1] . '-' . $parts[2];
                }
            }
        } catch (\Exception $e) {
            return $dateString;
        }
        return $dateString;
    }
    // ----------------------------------------------------
    // 3 เพิ่มฟังก์ชันใหม่ (ตรวจสอบว่ามีบิลมัดจำ Active ไหม)
    // ----------------------------------------------------
    
    public function checkDepositStatus($tenantId)
    {
        $activeDeposit = AccountingTransaction::where('tenant_id', $tenantId)
            ->where('category_id', 2)
            ->where('status', 'active')
            ->first();

        return response()->json([
            'has_active_deposit' => (bool)$activeDeposit,
            // 🌟 ใช้ format('Y-m-d') บังคับก่อนส่งกลับไปเป็น JSON
            'deposit_date' => $activeDeposit ? \Carbon\Carbon::parse($activeDeposit->entry_date)->format('Y-m-d') : null 
        ]);
    }

    // ----------------------------------------------------
    // 4. ฟังก์ชันอัปเดตผู้เช่า
    // ----------------------------------------------------

    public function updateTenant(Request $request, $id)
    {
        // 🌟 แปลงวันที่จาก พ.ศ. เป็น ค.ศ. ก่อนเริ่ม
        $request->merge([
            'id_card_issue_date' => $this->convertThaiYearToAD($request->id_card_issue_date),
            'id_card_expiry_date' => $this->convertThaiYearToAD($request->id_card_expiry_date),
            'start_date' => $this->convertThaiYearToAD($request->start_date),
            'end_date' => $this->convertThaiYearToAD($request->end_date),
            'deposit_date' => $this->convertThaiYearToAD($request->deposit_date),
        ]);

        DB::beginTransaction();
        try {
            $tenant = Tenant::findOrFail($id);

            // 1. Validation 
            $request->validate([
                'id_card' => 'nullable|string|max:13', // 🌟 เปลี่ยนจาก digits เป็น string|max เพื่อรองรับรูปแบบแปลกๆ
                'password' => 'nullable|string|min:6',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'age' => 'nullable|integer',
                'id_card_issue_date' => 'nullable|date',
                'id_card_expiry_date' => 'nullable|date',
                'id_card_issue_place' => 'nullable|string|max:255',
                'id_card_issue_province' => 'nullable|string|max:255',
                'address_no' => 'required|string|max:255',
                'moo' => 'required|string|max:3',
                'street' => 'nullable|string|max:255',
                'alley' => 'nullable|string|max:255',
                'sub_district' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'postal_code' => 'required|string|max:5',
                'phone' => 'required|digits:10',
                'workplace' => 'nullable|string|max:255',
                'start_date' => 'required|date',
                'resident_count' => 'required|integer|min:1',
                'deposit_amount' => 'required|numeric|min:1',
                'deposit_payment_method' => 'nullable|string',
                'deposit_slip' => 'nullable|image|max:2048',
                // 🌟 เพิ่มเงื่อนไขว่า ถ้าไม่มีมัดจำเดิม ต้องกรอกวันที่มัดจำใหม่มานะ
                'deposit_date' => 'required_without:active_deposit|nullable|date', 
            ]);

            // 2. ตรวจสอบมัดจำเดิม
            $activeDeposit = AccountingTransaction::where('tenant_id', $tenant->id)
                ->where('category_id', 2)
                ->where('status', 'active')
                ->first();

            // 3. เตรียมข้อมูลสำหรับตาราง Tenants
            $tenantData = $request->only([
                'id_card', 'first_name', 'last_name', 'age', 'id_card_issue_date', 
                'id_card_expiry_date', 'id_card_issue_place', 'id_card_issue_province',
                'address_no', 'moo', 'street', 'alley', 'sub_district', 'district', 
                'province', 'postal_code', 'phone', 'workplace', 'start_date', 
                'end_date', 'resident_count'
            ]);
            
            $tenantData['has_parking'] = $request->has('has_parking');
            $tenantData['updated_at'] = now();

            // 🌟 จัดการรหัสผ่าน: ถ้าพิมพ์มาใหม่ให้เปลี่ยน
            if ($request->filled('password')) {
                $tenantData['password'] = Hash::make($request->password);
            } 
            // 🌟 แต่ถ้ามีการเปลี่ยน id_card หรือ phone โดยไม่ได้แก้รหัสผ่าน ให้จับเปลี่ยนรหัสผ่านเป็นค่าใหม่ด้วย
            // เพื่อป้องกันเคสที่แก้จากมีเลขบัตร เป็นไม่มีเลขบัตร รหัสผ่านเดิมจะยังเป็นเลขบัตรอยู่
            else if ($request->id_card !== $tenant->id_card || $request->phone !== $tenant->phone) {
                $passwordSource = $request->id_card ?: $request->phone;
                $tenantData['password'] = Hash::make($passwordSource);
            }

            // อัปเดตข้อมูลผู้เช่า
            $tenant->update($tenantData);

            // 4. จัดการมัดจำ (กรณีไม่มีมัดจำเดิมที่ Active = มีการจ่ายมัดจำใหม่เข้ามาตอนแก้)
            if (!$activeDeposit) {
                
                // 🌟 ป้องกันบัค: ถ้าไม่มี deposit_date ให้ใช้วัน start_date แทน
                $safeDepositDate = $request->deposit_date ?: $request->start_date;

                $depositSlipPath = $tenant->deposit_slip;
                if ($request->hasFile('deposit_slip')) {
                    $file = $request->file('deposit_slip');
                    // 🌟 ป้องกันบัคชื่อไฟล์: ถ้าไม่มี id_card ให้ใช้เบอร์โทรแทน
                    $fileIdentifier = $request->id_card ?: $request->phone;
                    $filename = time() . '_' . $fileIdentifier . '_slip.' . $file->getClientOriginalExtension();
                    $depositSlipPath = $file->storeAs('slips', $filename, 'public');
                }

                // สร้าง Payment 
                $payment = Payment::create([
                    'invoice_id' => null,
                    'user_id' => Auth::id(),
                    'amount_paid' => $request->deposit_amount,
                    'payment_date' => $safeDepositDate, // 🌟 ใช้ safe date
                    'payment_method' => $request->deposit_payment_method ?? 'เงินสด',
                    'slip_image' => $depositSlipPath,
                    'note' => 'ชำระเงินมัดจำใหม่ (ห้อง ' . $tenant->room->room_number . ')',
                    'status' => 'active'
                ]);

                // บันทึกธุรกรรมบัญชี
                AccountingTransaction::create([
                    'category_id' => 2,
                    'payment_id' => $payment->id,
                    'tenant_id' => $tenant->id,
                    'user_id' => Auth::id(),
                    'title' => "เงินมัดจำ (ห้อง " . $tenant->room->room_number . ")",
                    'amount' => $request->deposit_amount,
                    'entry_date' => $safeDepositDate, // 🌟 ใช้ safe date
                    'description' => "รับเงินมัดจำรอบใหม่จาก " . $tenant->first_name,
                    'status' => 'active'
                ]);
                
                // อัปเดตยอดมัดจำกลับไปที่ตัวผู้เช่าด้วย
                $tenant->update(['deposit_amount' => $request->deposit_amount, 'deposit_slip' => $depositSlipPath]);
            }

            // 5. รีเฟรชข้อมูลและสร้างสัญญา PDF ใหม่ (โค้ดคุณเขียนมาดีแล้วครับ ไม่ต้องแก้)
            // ... (ดึงข้อมูล $room, $apartment, $tenant_expenses ... ฯลฯ)
            $tenant->refresh();
            $room = Room::with('roomPrice')->where('id', $tenant->room_id)->first();
            $apartment = DB::table('apartment')->first();
            $tenant_expenses = DB::table('tenant_expenses')->get();
            $room_price_thai = $this->bahtText($room->price ?? 0);
            $deposit_amount_thai = $this->bahtText($tenant->deposit_amount ?? 0);
            
            $pdf = Pdf::loadView('admin.tenants.pdf.contract', compact(
                'tenant', 'room', 'apartment', 'tenant_expenses', 'room_price_thai', 'deposit_amount_thai'
            ))
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            // 🌟 ป้องกันบัคชื่อไฟล์สัญญา: ถ้าไม่มี id_card ให้ใช้เบอร์โทรแทน
            $pdfIdentifier = $tenant->id_card ?: $tenant->phone;
            $pdfFilename = time() . '_' . $pdfIdentifier . '_contract.pdf';
            
            $pdfPath = 'contracts/' . $pdfFilename;

            // ลบไฟล์สัญญาเก่า (ถ้ามี) 
            if ($tenant->rental_contract && Storage::disk('public')->exists($tenant->rental_contract)) {
                Storage::disk('public')->delete($tenant->rental_contract);
            }

            // บันทึกไฟล์ใหม่ และอัปเดต Path ใน Database
            Storage::disk('public')->put($pdfPath, $pdf->output());
            $tenant->update(['rental_contract' => $pdfPath]);

            DB::commit();
            return back()->with('success', 'อัปเดตข้อมูลผู้เช่าและสร้างสัญญาใหม่เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'การอัปเดตล้มเหลว: ' . $e->getMessage()]);
        }
    }

    // สิ้นสุดสัญญา (อัปเดตสถานะผู้เช่า และห้องพัก)
    public function updateStatusTenant(Request $request, $id)
    {
        $request->merge([
            'end_date' => $this->convertThaiYearToAD($request->end_date),
        ]);
        DB::beginTransaction();
        try {
            $tenant = Tenant::findOrFail($id);

            // 1. เช็คว่าสถานะสิ้นสุดไปแล้วหรือยัง (ป้องกันการกดย้ำ)
            if ($tenant->status == 'สิ้นสุดสัญญา') {
                DB::rollBack(); // คืนค่ากลับ เพราะไม่มีอะไรเปลี่ยน
                if ($request->ajax()) {
                    return response()->json(['message' => 'สัญญานี้ได้สิ้นสุดลงก่อนหน้านี้แล้ว'], 422);
                }
                return redirect()->back()->withErrors(['error' => 'สัญญานี้ได้สิ้นสุดลงก่อนหน้านี้แล้ว']);
            }

            // 2.  แก้ไข Logic การตรวจบิลค้าง 
            // ดึงบิลทั้งหมดของผู้เช่ารายนี้ ที่ "ยังชำระไม่เสร็จสมบูรณ์" (ป้องกันสถานะ 'กรุณาส่งบิล' หลุดรอด)
            $unpaidInvoices = Invoice::where('tenant_id', $id)
                ->where('status', '!=', 'ชำระแล้ว')
                ->get();

            if ($unpaidInvoices->isNotEmpty()) {
                // แปลงรอบเดือนแต่ละใบเป็นภาษาไทย และรวมเป็นข้อความเดียว
                $months = $unpaidInvoices->map(function ($inv) {
                    return $this->toThaiDate($inv->billing_month, false); // false เพื่อเอาเฉพาะเดือน/ปี
                })->unique()->implode(', ');

                $errorMsg = "ไม่สามารถสิ้นสุดสัญญาได้! เนื่องจากยังมีบิลรอบเดือน: [{$months}] ที่ยังไม่ได้ชำระให้เสร็จสิ้น";

                DB::rollBack(); // คืนค่ากลับ
                if ($request->ajax()) {
                    return response()->json(['message' => $errorMsg], 422);
                }
                return back()->withErrors(['error' => $errorMsg])->withInput();
            }

            // 3. จัดการวันที่สิ้นสุดสัญญา (ถ้าฟอร์มไม่ส่งมา ให้ใช้วันนี้)
            $endDate = $request->end_date ?: now();

            // 4. อัปเดตสถานะผู้เช่า (ให้ออกจากระบบ)
            $tenant->update([
                'status' => 'สิ้นสุดสัญญา',
                'end_date' => $endDate,
                'updated_at' => now()
            ]);

            // 5. อัปเดตสถานะห้องพัก คืนสถานะให้เป็น 'ว่าง' เพื่อพร้อมรับคนใหม่
            DB::table('rooms')->where('id', $tenant->room_id)->update([
                'status' => 'ว่าง',
                'updated_at' => now()
            ]);

            // 6. บันทึก "รายจ่าย" กรณีมีการคืนเงินมัดจำ
            $refundAmount = (float) $request->refund_amount;
            if ($refundAmount > 0) {
                // ดึงชื่อหมวดหมู่ให้ถูกต้อง (สมมติ ID 20 คือ คืนเงินมัดจำ)
                $categoryName = DB::table('accounting_categories')->where('id', 20)->value('name') ?? 'คืนเงินมัดจำ';

                AccountingTransaction::create([
                    'category_id' => 20,
                    'payment_id' => null, // ไม่มี payment_id เพราะเป็นการจ่ายเงินออก (ไม่ใช่รับเข้า)
                    'tenant_id' => $tenant->id,
                    'user_id' => Auth::id(),
                    'title' => "{$categoryName} (ห้อง " . $tenant->room->room_number . ")",
                    'amount' => $refundAmount,
                    'entry_date' => $endDate,
                    'description' => "คืนเงินให้ผู้เช่า: {$tenant->first_name} {$tenant->last_name} (สิ้นสุดสัญญา)",
                    'status' => 'active'
                ]);
            }

            DB::commit();

            // ====================================================================
            // 🌟 ส่วนที่เพิ่มเข้ามา: ส่งแจ้งเตือนขอบคุณผ่าน LINE (ถ้าย้ายออกและคืนห้องแล้ว)
            // ====================================================================
            if (!empty($tenant->line_id)) {
                $channelAccessToken = env('LINE_BOT_CHANNEL_ACCESS_TOKEN');
                $roomNumber = $tenant->room->room_number ?? '-';
                
                // จัดรูปแบบวันที่ออกให้ดูสวยงาม
                $displayEndDate = $this->toThaiDate($endDate, true);

                $messageText = "👋 เรียน คุณ {$tenant->first_name}\n\n";
                $messageText .= "การคืนห้องพักและสิ้นสุดสัญญาเช่า (ห้อง {$roomNumber}) เมื่อวันที่ {$displayEndDate} ได้รับการดำเนินการเรียบร้อยแล้วในระบบครับ\n\n";
                
                if ($refundAmount > 0) {
                    $messageText .= "💰 ทางอพาร์ทเม้นท์ได้ดำเนินการคืนเงินมัดจำ จำนวน " . number_format($refundAmount, 2) . " บาท ให้ท่านเรียบร้อยแล้ว\n\n";
                }

                $messageText .= "ทางเราขอขอบพระคุณเป็นอย่างยิ่งที่ไว้วางใจเลือกพักกับเรา หวังว่าในอนาคตจะมีโอกาสได้ดูแลคุณอีกครั้งนะครับ 😊🙏\n\n";
                $messageText .= "ขอให้โชคดีและเดินทางปลอดภัยครับ!";

                try {
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
                    // เก็บ Log เงียบๆ ถ้ามีปัญหาเรื่อง LINE เพื่อไม่ให้เว็บพัง
                    \Illuminate\Support\Facades\Log::error("Line Notify Error (Move out - Tenant ID {$tenant->id}): " . $e->getMessage());
                }
            }
            // ====================================================================

            $successMsg = 'สิ้นสุดสัญญาและคืนห้องพักเรียบร้อยแล้ว' . ($refundAmount > 0 ? ' (พร้อมบันทึกบัญชีคืนเงินมัดจำ)' : '');

            if ($request->ajax()) {
                return response()->json(['message' => $successMsg]);
            }
            return back()->with('success', $successMsg);

        } catch (\Exception $e) {
            DB::rollBack();
            $errorMsg = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['message' => $errorMsg], 500);
            }
            return redirect()->back()->withErrors(['error' => $errorMsg]);
        }
    }
    // 4. ลบข้อมูลผู้เช่า
    public function deleteTenant($id)
    {
        try {
            DB::beginTransaction();
            $tenant = Tenant::findOrFail($id);

            // ลบไฟล์สัญญา
            if ($tenant->rental_contract) {
                Storage::disk('public')->delete($tenant->rental_contract);
            }

            // คืนสถานะห้องให้ว่างก่อนลบผู้เช่า
            Room::where('id', $tenant->room_id)->update(['status' => 'ว่าง']);

            $tenant->delete();
            DB::commit();
            return redirect()->back()->with('success', 'ลบข้อมูลผู้เช่าเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // -----------------------------------------
    // จัดการการลงทะเบียนออนไลน์ผ่าน LINE 
    // แสดงหน้ารายการจอง (รออนุมัติ / ยกเลิก)

    public function registrationShow(Request $request)
    {
        $query = Tenant::query()
            ->whereIn('status', ['รออนุมัติ', 'ยกเลิกการจอง'])
            ->orderBy('created_at', 'desc'); // เรียงคนที่เพิ่งจองขึ้นก่อน

        // ตัวกรองพื้นฐาน
        if ($request->filter_name) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->filter_name}%")
                  ->orWhere('last_name', 'like', "%{$request->filter_name}%");
            });
        }
        if ($request->filter_phone) {
            $query->where('phone', 'like', "%{$request->filter_phone}%");
        }
        if ($request->filter_status) {
            $query->where('status', $request->filter_status);
        }

        $registrations = $query->paginate(10)->withQueryString();
        // เพิ่มลูปตรงนี้: เรียกใช้ toThaiDate จาก FormatHelper
        foreach ($registrations as $reg) {
            // ใส่ true, true เพื่อให้แสดงเวลาด้วย (อิงตามที่คุณเคยใช้ใน tenantShow)
            $reg->thai_created_at = $this->toThaiDate($reg->created_at, true, true); 
        }
        // 🌟 เพิ่มลูปตรงนี้: เรียกใช้ toThaiDate จาก FormatHelper สำหรับฟิลด์วันที่ทั้งหมด
        foreach ($registrations as $reg) {
            // วันที่จอง (เอาเวลาด้วย)
            $reg->thai_created_at = $this->toThaiDate($reg->created_at, true, true); 
            
            // 🌟 วันที่ออกบัตรประชาชน (เอาแค่วันที่ ไม่เอาเวลา)
            $reg->thai_id_card_issue = $reg->id_card_issue_date ? $this->toThaiDate($reg->id_card_issue_date) : '-';
            
            // 🌟 วันหมดอายุบัตรประชาชน (เอาแค่วันที่ ไม่เอาเวลา)
            $reg->thai_id_card_expiry = $reg->id_card_expiry_date ? $this->toThaiDate($reg->id_card_expiry_date) : '-';
        }
        // ดึงห้องว่างมาเตรียมไว้ใน Modal เผื่อแอดมินกดอนุมัติและเลือกห้องให้
        $rooms = Room::with('roomPrice.building')->where('status', 'ว่าง')->get();

        return view('admin.tenants.registrations', compact('registrations', 'rooms'));
    }

    // ฟังก์ชันอัปเดตสถานะเป็น "ยกเลิกการจอง"
    public function cancelRegistration($id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'ยกเลิกการจอง','line_id'=>null,'line_avatar'=>null]);
        
        return back()->with('success', 'ยกเลิกการจองเรียบร้อยแล้ว ยกเลิกการเชื่อมต่อไลน์ ข้อมูลยังถูกเก็บไว้ในระบบ');
    }

    // ยืนยันการลงทะเบียน (อนุมัติ) และอัปเดตสถานะเป็น "กำลังใช้งาน"
    public function reviewRegistration(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        $roomId = $request->query('room_id');
        
        $selectedRoom = Room::with('roomPrice.building')->find($roomId);
        if (!$selectedRoom) {
            return redirect()->route('admin.rooms.system')->withErrors(['error' => 'ไม่พบข้อมูลห้องพักที่เลือก']);
        }

        // หน้าตาจะคล้ายหน้า Create แต่นำข้อมูลของ $tenant ไปแปะไว้ล่วงหน้า
        return view('admin.tenants.review_registration', compact('tenant', 'selectedRoom'));
    }

   // 🌟 ฟังก์ชันกดยืนยันการอนุมัติ (และแก้ไขข้อมูล)
    public function approveRegistration(Request $request, $id)
    {
        $request->merge([
            'id_card_issue_date' => $this->convertThaiYearToAD($request->id_card_issue_date),
            'id_card_expiry_date' => $this->convertThaiYearToAD($request->id_card_expiry_date),
            'start_date' => $this->convertThaiYearToAD($request->start_date),
            'end_date' => $this->convertThaiYearToAD($request->end_date),
            'deposit_date' => $this->convertThaiYearToAD($request->deposit_date),
        ]);
        DB::beginTransaction();
        try {
            $tenant = Tenant::findOrFail($id);
            $room = Room::with('roomPrice')->findOrFail($request->room_id);

            // 1. Validation (ถอด rental_contract ออก และเพิ่มเรื่องสลิปมัดจำ)
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'age' => 'required|integer|min:1',
                'id_card' => 'required|digits:13',
                'phone' => 'required|digits:10',
                'id_card_issue_date' => 'nullable|date',
                'id_card_expiry_date' => 'nullable|date',
                'address_no' => 'required|string|max:255',
                'moo' => 'required|string|max:3',
                'sub_district' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'postal_code' => 'required|string|max:5',
                'resident_count' => 'required|integer|min:1',
                'start_date' => 'required|date',
                'deposit_amount' => 'required|numeric|min:0',
                'deposit_payment_method' => 'required|string',
                'deposit_slip' => 'nullable|image|max:2048', // 🌟 รองรับการแนบสลิปใหม่
            ]);

            // 🌟 2. จัดการไฟล์สลิปเงินมัดจำ (ถ้าแอดมินอัปโหลดรูปใหม่เข้ามา)
            $depositSlipPath = $tenant->deposit_slip; // เก็บรูปเก่าไว้ก่อนเป็นค่าเริ่มต้น
            if ($request->hasFile('deposit_slip')) {
                if ($tenant->deposit_slip && Storage::disk('public')->exists($tenant->deposit_slip)) {
                    Storage::disk('public')->delete($tenant->deposit_slip);
                }
                $file = $request->file('deposit_slip');
                $filename = time() . '_' . $request->id_card . '_slip.' . $file->getClientOriginalExtension();
                $depositSlipPath = $file->storeAs('slips', $filename, 'public');
            }

            // 3. อัปเดตข้อมูลผู้เช่าทั้งหมด (รวมฟิลด์ใหม่)
            $tenant->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'age' => $request->age,
                'id_card' => $request->id_card,
                'password' => Hash::make($request->id_card),
                'phone' => $request->phone,
                'id_card_issue_date' => $request->id_card_issue_date,
                'id_card_expiry_date' => $request->id_card_expiry_date,
                'id_card_issue_place' => $request->id_card_issue_place,
                'id_card_issue_province' => $request->id_card_issue_province,
                'street' => $request->street,
                'alley' => $request->alley,
                'workplace' => $request->workplace,
                'address_no' => $request->address_no,
                'moo' => $request->moo,
                'province' => $request->province,
                'district' => $request->district,
                'sub_district' => $request->sub_district,
                'postal_code' => $request->postal_code,
                'room_id' => $room->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'has_parking' => $request->has('has_parking'),
                'resident_count' => $request->resident_count,
                
                // 🌟 อัปเดตข้อมูลมัดจำเผื่อมีการแก้ไข
                'deposit_amount' => $request->deposit_amount,
                'deposit_payment_method' => $request->deposit_payment_method,
                'deposit_slip' => $depositSlipPath,
                
                'status' => 'กำลังใช้งาน',
            ]);

            // 🌟 4. ระบบสร้างไฟล์ PDF สัญญาเช่าอัตโนมัติ 
            $apartment = DB::table('apartment')->first();
            $tenant_expenses = DB::table('tenant_expenses')->get();
            
            // หมายเหตุ: ใช้ $this->bahtText ถ้า Controller นี้ใช้ Trait FormatHelper แล้ว
            // ถ้าพัง ให้เปลี่ยนเป็น App\Helpers\FormatHelper::bahtText(...) แทนครับ
            $room_price_thai = $this->bahtText($room->price ?? 0);
            $deposit_amount_thai = $this->bahtText($request->deposit_amount ?? 0);

            $pdf = Pdf::loadView('admin.tenants.pdf.contract', compact(
                'tenant', 'room', 'apartment', 'tenant_expenses', 'room_price_thai', 'deposit_amount_thai'
            ))->setPaper('a4', 'portrait')->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            $pdfFilename = time() . '_' . $tenant->id_card . '_contract.pdf';
            $pdfPath = 'contracts/' . $pdfFilename;
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // บันทึก Path สัญญา
            $tenant->update(['rental_contract' => $pdfPath]);

            // อัปเดตสถานะห้อง
            $room->update(['status' => 'มีผู้เช่า']);

            // 5. บันทึกบัญชี (ถ้ามีเงินมัดจำ)
            if ($request->deposit_amount > 0) {
                $payment = Payment::create([
                    'invoice_id' => null, 'user_id' => Auth::id(),
                    'amount_paid' => $request->deposit_amount,
                    'payment_date' => $request->deposit_date,
                    'payment_method' => $request->deposit_payment_method,
                    'slip_image' => $depositSlipPath,
                    'note' => 'ชำระเงินมัดจำแรกเข้า (จองออนไลน์อนุมัติแล้ว) ห้อง ' . $room->room_number,
                    'status' => 'active'
                ]);

                $category = AccountingCategory::find(2); 
                if ($category) {
                    AccountingTransaction::create([
                        'category_id' => $category->id, 'payment_id' => $payment->id, 'tenant_id' => $tenant->id,
                        'user_id' => Auth::id(), 'title' => $category->name . " (ห้อง " . $room->room_number . ")",
                        'amount' => $request->deposit_amount, 'entry_date' => $request->deposit_date,
                        'description' => "รับเงินมัดจำจาก " . $request->first_name, 'status' => 'active'
                    ]);
                }
            }

            // 6. แจ้งเตือนไปยัง LINE ผู้เช่า
            if ($tenant->line_id) {
                $startDateThai = $this->toThaiDate($request->start_date, true); // แปลงวันที่เริ่มต้นเป็นรูปแบบไทย
                $channelAccessToken = env('LINE_BOT_CHANNEL_ACCESS_TOKEN');
                $messageText = "🎉 ยินดีด้วยครับ คุณ {$request->first_name}!\n\n";
                $messageText .= "การจองห้องพักของคุณได้รับการอนุมัติเรียบร้อยแล้ว\n";
                $messageText .= "🏢 ห้องพักของคุณคือ: {$room->room_number}\n";
                $messageText .= "📅 วันที่เริ่มเข้าอยู่: " . $startDateThai . "\n\n";
                $messageText .= "ขอบคุณที่เลือกพักกับเราครับ 😊";

                Http::withToken($channelAccessToken)->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $tenant->line_id,
                    'messages' => [['type' => 'text', 'text' => $messageText]]
                ]);
            }

            DB::commit();
            return redirect()->route('admin.rooms.system', ['building_id' => $room->roomPrice->building_id])
                             ->with('success', 'อนุมัติการจอง อัปเดตข้อมูล และสร้างสัญญา PDF เรียบร้อยแล้ว!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }
}
