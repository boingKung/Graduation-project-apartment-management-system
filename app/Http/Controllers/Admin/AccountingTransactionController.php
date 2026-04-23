<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // เรียกใช้งาน Auth Facade
use Barryvdh\DomPDF\Facade\Pdf; // ใช้งานสำหรับ pdf
// excel
use App\Exports\SummaryReportExport;
use App\Exports\IncomeReportExport;
use App\Exports\ExpenseReportExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Building;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\AccountingTransaction;
use App\Models\AccountingCategory;

// นำเข้า Trait
use App\Traits\FormatHelper;
class AccountingTransactionController extends Controller
{
    use FormatHelper; // เรียกใช้ Trait ใน Class

    // =========================================================================
    // 🌟 จัดการ รายรับรายจ่าย accounting_transaction
    // =========================================================================

    public function accountingTransactionShow(Request $request)
    {
        // 🌟 แปลงวันที่จากตัวกรอง พ.ศ. -> ค.ศ.
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        // รับค่าจากฟอร์มและแถว Filter ในตาราง
        $startDate = $request->input('date_start');
        $endDate = $request->input('date_end');
        $typeId = $request->input('type_id');
        $categoryId = $request->input('category_id');
        $searchBuilding = $request->input('search_building'); // 🌟 เพิ่มค้นหาตึก
        $searchRoom = $request->input('search_room');
        $searchDetail = $request->input('search_detail'); 
        $filterAdmin = $request->input('filter_admin'); 
        $filterStatus = $request->input('filter_status');

        // 🌟 อัปเดต Eager Loading ให้ดึงข้อมูลตึกและห้องมาให้ครบ
        $query = AccountingTransaction::with(['category.type', 'tenant.room.roomPrice.building', 'room.roomPrice.building', 'building', 'admin']);

        // --- Logic การกรองข้อมูล (Filter) ---
        if ($startDate && $endDate) {
            $query->whereBetween('entry_date', [$startDate, $endDate]);
        }
        if ($typeId) {
            $query->whereHas('category', function ($q) use ($typeId) {
                $q->where('type_id', $typeId);
            });
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($searchDetail) {
            $query->where('title', 'like', "%{$searchDetail}%");
        }
        
        // 🌟 กรองตึก (รองรับโครงสร้างใหม่และเก่า)
        if ($searchBuilding) {
            $query->where(function($q) use ($searchBuilding) {
                $q->where('building_id', $searchBuilding) // ระบบใหม่ (ตึกตรงๆ)
                  ->orWhereHas('room.roomPrice', fn($r) => $r->where('building_id', $searchBuilding)) // ระบบใหม่ (ผ่านห้อง)
                  ->orWhereHas('tenant.room.roomPrice', fn($t) => $t->where('building_id', $searchBuilding)); // ระบบเก่า (ผ่านผู้เช่า)
            });
        }

        // 🌟 กรองห้อง (รองรับโครงสร้างใหม่และเก่า)
        if ($searchRoom) {
            $query->where(function($q) use ($searchRoom) {
                $q->whereHas('room', fn($r) => $r->where('room_number', 'like', "%{$searchRoom}%")) // ระบบใหม่
                  ->orWhereHas('tenant.room', fn($t) => $t->where('room_number', 'like', "%{$searchRoom}%")); // ระบบเก่า
            });
        }

        if ($filterAdmin) {
            $query->whereHas('admin', function ($q) use ($filterAdmin) {
                $q->where('firstname', 'like', "%{$filterAdmin}%")
                    ->orWhere('lastname', 'like', "%{$filterAdmin}%");
            });
        }
        if ($filterStatus) {
            $query->where('status', $filterStatus);
        }
        
        $transactions = $query->orderBy('entry_date', 'desc')->paginate(20);

        // ดึงรายการตึกมาแสดงใน Dropdown Filter
        $buildings = \App\Models\Building::all();

        // 🌟 ส่วนที่ปรับปรุง: กำหนดข้อความแสดงหัวข้อ
        if ($startDate && $endDate) {
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            // ถ้าเลือกช่วงวันที่ภายในเดือนเดียวกัน
            if ($start->format('Y-m') === $end->format('Y-m')) {
                $displayDate = "ประจำเดือน " . $this->toThaiDate($startDate, false);
            } else {
                // ถ้าเลือกข้ามเดือน
                $displayDate = $this->toThaiDate($startDate, false) . " - " . $this->toThaiDate($endDate, false);
            }
        } else {
            // กรณีไม่ได้เลือกวันที่ (แสดงทั้งหมด)
            $displayDate = "รายการธุรกรรมทั้งหมด";
        }

        $categories = AccountingCategory::with('type')
            ->get()
            ->groupBy(function ($item) {
                return $item->type->name; 
            });

        // 🌟 หาชื่อตึกและห้องของแต่ละ Transaction มาเตรียมไว้
        foreach ($transactions as $t) {
            $t->thai_entry_date = $this->toThaiDate($t->entry_date);
            
            // หาชื่อตึก
            if ($t->building) { $t->display_building = $t->building->name; }
            elseif ($t->room && $t->room->roomPrice && $t->room->roomPrice->building) { $t->display_building = $t->room->roomPrice->building->name; }
            elseif ($t->tenant && $t->tenant->room && $t->tenant->room->roomPrice && $t->tenant->room->roomPrice->building) { $t->display_building = $t->tenant->room->roomPrice->building->name; }
            else { $t->display_building = '-'; }

            // หาเลขห้อง
            if ($t->room) { $t->display_room = $t->room->room_number; }
            elseif ($t->tenant && $t->tenant->room) { $t->display_room = $t->tenant->room->room_number; }
            else { $t->display_room = '-'; }
        }

        return view('admin.accounting_transactions.statementShow', compact(
            'transactions', 'categories', 'buildings', // 🌟 ส่ง buildings ไปที่ view
            'startDate', 'endDate', 'typeId', 'categoryId',
            'searchBuilding', 'searchRoom', 'searchDetail',
            'filterAdmin', 'filterStatus', 'displayDate'
        ));
    }

    public function getTransactionDetail($id)
    {
        $transaction = AccountingTransaction::with(['category.type', 'tenant.room.roomPrice.building', 'room.roomPrice.building', 'building', 'admin', 'payment'])
            ->findOrFail($id);
            
        $payment_ref = 'บันทึกด้วยตนเอง';
        if ($transaction->payment_id) {
            if ($transaction->payment->invoice) {
                $payment_ref = "ชำระจากใบแจ้งหนี้ #" . $transaction->payment->invoice->invoice_number;
            } else {
                $payment_ref = "รายการชำระเงิน (ไม่มีใบแจ้งหนี้/มัดจำ)";
            }
        }

        // 🌟 หาชื่อตึกและห้องอย่างฉลาด
        $displayBuilding = '-';
        if ($transaction->building) { $displayBuilding = $transaction->building->name; }
        elseif ($transaction->room && $transaction->room->roomPrice && $transaction->room->roomPrice->building) { $displayBuilding = $transaction->room->roomPrice->building->name; }
        elseif ($transaction->tenant && $transaction->tenant->room && $transaction->tenant->room->roomPrice && $transaction->tenant->room->roomPrice->building) { $displayBuilding = $transaction->tenant->room->roomPrice->building->name; }

        $displayRoom = '-';
        if ($transaction->room) { $displayRoom = $transaction->room->room_number; }
        elseif ($transaction->tenant && $transaction->tenant->room) { $displayRoom = $transaction->tenant->room->room_number; }

        return response()->json([
            'title' => $transaction->title,
            'amount' => number_format($transaction->amount, 2),
            'type' => $transaction->category->type->name,
            'category' => $transaction->category->name,
            'date' => $this->toThaiDate($transaction->entry_date),
            'building' => $displayBuilding, // 🌟 เพิ่มข้อมูลตึก
            'room' => $displayRoom,         // 🌟 อัปเดตข้อมูลห้อง
            'admin' => ($transaction->admin->firstname ?? 'System') . ' ' . ($transaction->admin->lastname ?? ''),
            'description' => $transaction->description ?? '-',
            'payment_ref' => $payment_ref
        ]);
    }
    public function accountingTransactionCreate(Request $request)
    {
        $typeId = $request->query('type_id', 1); // รับค่าจากปุ่ม ถ้าไม่มีให้ default เป็น 1 (รายรับ)
        $typeName = ($typeId == 1) ? 'รายรับ' : 'รายจ่าย';

        // ดึงเฉพาะหมวดหมู่ที่ตรงกับประเภทที่เลือก
        $categories = AccountingCategory::where('type_id', $typeId)->get();

        // ดึงรายชื่อผู้เช่าเพื่อผูกกับเลขห้อง
        $tenants = Tenant::with('room')->where('status', 'พักอาศัย')->get();

        // ดึงข้อมูลตึก พร้อมรายชื่อห้องที่อยู่ในตึกนั้น เพื่อนำไปทำ Dropdown 2 ชั้น
        $buildings = Building::with('rooms')->get();

        return view('admin.accounting_transactions.create', compact('categories', 'tenants', 'typeId', 'typeName', 'buildings'));
    }
    public function accountingTransactionStore(Request $request)
    {
        // 🌟 แปลงวันที่บันทึกรายการก่อน Validation
        $request->merge([
            'entry_date' => $this->convertThaiYearToAD($request->entry_date),
        ]);
        $request->validate([
            'entry_date' => 'required|date',
            'items' => 'required|array',
            'items.*.title' => 'required|string',
            'items.*.category_id' => 'required|exists:accounting_categories,id',
            'items.*.building_id' => 'nullable|exists:buildings,id', // 🌟 รับค่าตึก
            'items.*.room_id' => 'nullable|exists:rooms,id',         // 🌟 รับค่าห้อง
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                
                // 🌟 หากมีการเลือกห้อง ให้ระบบหา "ผู้เช่าที่กำลังพักอาศัย" ในห้องนั้นมาผูกให้อัตโนมัติ
                $tenantId = null;
                if (!empty($item['room_id'])) {
                    $activeTenant = Tenant::where('room_id', $item['room_id'])
                                          ->where('status', 'กำลังใช้งาน')
                                          ->first();
                    $tenantId = $activeTenant ? $activeTenant->id : null;
                }

                AccountingTransaction::create([
                    'category_id' => $item['category_id'],
                    'building_id' => $item['building_id'] ?? null, // 🌟 บันทึกตึก
                    'room_id'     => $item['room_id'] ?? null,     // 🌟 บันทึกห้อง
                    'tenant_id'   => $tenantId,                    // 🌟 ผูกผู้เช่าอัตโนมัติ (ถ้ามี)
                    'user_id'     => Auth::id(), 
                    'title'       => $item['title'],
                    'amount'      => (float) $item['amount'],
                    'entry_date'  => $request->entry_date,
                    'description' => $item['description'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('admin.accounting_transactions.show')->with('success', 'บันทึกรายการสำเร็จ');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    public function voidTransaction($id)
    {
        DB::beginTransaction();
        try {
            $transaction = AccountingTransaction::findOrFail($id);

            if ($transaction->status === 'void') {
                return back()->with('error', 'รายการนี้ถูกยกเลิกไปแล้ว');
            }

            // ⚠️ ตรวจสอบว่ามาจากระบบจ่ายบิลหรือไม่
            if ($transaction->payment_id) {
                return back()->with('error', 'ไม่สามารถยกเลิกรายการนี้โดยตรงได้ เนื่องจากผูกกับใบแจ้งหนี้ กรุณายกเลิกผ่านหน้า "ประวัติการชำระเงิน"');
            }

            // เปลี่ยนสถานะเป็น void สำหรับรายการที่บันทึกเอง
            $transaction->status = 'void';
            $transaction->save();

            DB::commit();
            return back()->with('success', 'ยกเลิกรายการธุรกรรมเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // ระบบรายงานทางการเงิน รายรับรายจ่าย

    public function reportSummary(Request $request)
    {
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        $startDate = $request->input('date_start') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('date_end') ?? now()->endOfMonth()->format('Y-m-d');

        // 1. ดึงข้อมูลธุรกรรมทั้งหมดในช่วงเวลา
        $transactions = AccountingTransaction::with(['category.type', 'tenant.room.roomPrice.building'])
            ->where('status', 'active')
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        // 2. แยกรายรับที่ต้องจัดกลุ่มตามตึก (เฉพาะ ค่าเช่า และ ค่าไฟ)
        $buildingIncome = $transactions->where('category.type_id', 1)
            ->filter(function ($t) {
                return str_contains($t->title, 'ค่าเช่าห้อง') || str_contains($t->title, 'ค่าไฟ');
            })
            ->groupBy(function ($t) {
                return $t->tenant->room->roomPrice->building->name ?? 'ตึกทั่วไป';
            });

        // 3. รายรับอื่นๆ (ที่ไม่ใช่ค่าเช่า/ค่าไฟของตึก เช่น ค่าน้ำ, ที่จอดรถ, มัดจำ) [cite: 6]
        $otherIncome = $transactions->where('category.type_id', 1)
            ->filter(function ($t) {
                return !str_contains($t->title, 'ค่าเช่าห้อง') && !str_contains($t->title, 'ค่าไฟ');
            })
            ->groupBy('category.name')->map->sum('amount');

        // 4. รายจ่ายทั้งหมด
        $expenseByCats = $transactions->where('category.type_id', 2)
            ->groupBy('category.name')->map->sum('amount');

        // 5. คำนวณยอดค้างรับจากใบแจ้งหนี้
        $outstandingAmount = Invoice::whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->get()
            ->sum('remaining_balance');

        $displayDate = $this->toThaiDate($startDate, false);
        $thai_startDate = $this->toThaiDate($startDate);
        $thai_endDate = $this->toThaiDate($endDate);

        // dd($buildingIncome, $otherIncome, $expenseByCats, $outstandingAmount);
        return view('admin.accounting_transactions.summary', compact(
            'buildingIncome',
            'otherIncome',
            'expenseByCats',
            'outstandingAmount',
            'displayDate',
            'startDate',
            'endDate',
            'thai_startDate',
            'thai_endDate'
        ));
    }
    // ajax ดึงรายละเอียด summary
    public function getSummaryDetails(Request $request)
    {
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        $startDate = $request->date_start;
        $endDate = $request->date_end;
        $target = $request->target;
        $name = $request->name;

        // 📝 กรณีที่ 1: ยอดค้างรับ (ดึงจาก Invoice และเชื่อม InvoiceDetails)
        if ($target === 'unpaid') {
            // ค้นหา Invoice ที่มีสถานะค้างชำระ ในช่วงวันที่เลือก และเรียงวันที่จากน้อยไปมาก
            $data = Invoice::with(['tenant.room', 'details'])
                ->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->orderBy('issue_date', 'asc') //  เรียงจากน้อยไปมาก
                ->get()
                ->map(function ($inv) {
                    //  แก้ไข: ใช้ Carbon::parse เพื่อป้องกัน Error กรณี issue_date เป็น String
    
                    $formattedDate = $this->toThaiDate($inv->issue_date, true, true);
                    return [
                        'date' => $formattedDate,
                        'title' => "ใบแจ้งหนี้ " . $inv->tenant->first_name . " " . $inv->tenant->last_name,
                        'description' => "-",
                        'room' => $inv->tenant->room->room_number ?? '-',
                        'amount' => number_format($inv->remaining_balance, 2), // ใช้ฟิลด์เสมือนคำนวณยอดคงเหลือ
                        'class' => 'text-danger'
                    ];
                });

            return response()->json(['title' => 'รายชื่อห้องที่ค้างชำระ (เรียงตามวันที่)', 'items' => $data]);
        }

        // 💸 กรณีที่ 2: ธุรกรรมรับ-จ่าย (เรียงจากน้อยไปมาก)
        $query = AccountingTransaction::with(['tenant.room', 'category'])
            ->where('status', 'active')
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_date', 'asc'); //  เรียงจากน้อยไปมาก

        if ($target === 'building_rent') {
            $query->where('title', 'like', '%ค่าเช่าห้อง%')
                ->whereHas('tenant.room.roomPrice.building', fn($q) => $q->where('name', $name));
            $modalTitle = "รายการค่าเช่า: $name";
        } elseif ($target === 'building_electric') {
            $query->where('title', 'like', '%ค่าไฟ%')
                ->whereHas('tenant.room.roomPrice.building', fn($q) => $q->where('name', $name));
            $modalTitle = "รายการค่าไฟ: $name";
        } elseif ($target === 'other_income') {
            $query->whereHas('category', fn($q) => $q->where('name', $name));
            $modalTitle = "รายการรายรับ: $name";
        } elseif ($target === 'expense') {
            $query->whereHas('category', fn($q) => $q->where('name', $name));
            $modalTitle = "รายการรายจ่าย: $name";
        }

        $items = $query->get()->map(function ($t) {
            $formattedDate = $this->toThaiDate($t->entry_date, true, true);
            return [
                'date' => $formattedDate,
                'title' => $t->title,
                'description' => $t->description ?? '-',
                'room' => $t->tenant->room->room_number ?? '-',
                'amount' => number_format($t->amount, 2),
                'class' => $t->category->type_id == 1 ? 'text-success' : 'text-danger'
            ];
        });

        return response()->json(['title' => $modalTitle ?? 'รายละเอียด', 'items' => $items]);
    }
    public function reportIncome(Request $request)
    {
        // 🌟 ต้องใส่ตรงนี้! เพื่อให้ Query ข้อมูลออกมาถูกเดือน
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        $startDate = $request->input('date_start') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('date_end') ?? now()->endOfMonth()->format('Y-m-d');

        // 1. ดึงข้อมูลรายรับทั้งหมด (type_id = 1) และโหลดความสัมพันธ์ตึก
        $incomeTransactions = AccountingTransaction::with(['category', 'tenant.room.roomPrice.building'])
            ->where('status', 'active')
            ->whereHas('category', fn($q) => $q->where('type_id', 1))
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        // 2. จัดกลุ่มรายรับตามหมวดหมู่ใหญ่ และจัดกลุ่มย่อยตามตึก
        $incomeByGroup = $incomeTransactions->groupBy('category.name')->map(function ($items) {
            return $items->groupBy(function ($item) {
                return $item->tenant->room->roomPrice->building->name ?? '';
            });
        });

        // 3. คำนวณยอดค้างรับจาก Invoice (ค้างชำระ/ชำระบางส่วน)
        $outstandingDetails = Invoice::with('tenant.room')
            ->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->get();

        $outstandingAmount = $outstandingDetails->sum('remaining_balance');

        $displayDate = $this->toThaiDate($startDate, false);
        $thai_startDate = $this->toThaiDate($startDate);
        $thai_endDate = $this->toThaiDate($endDate);

        return view('admin.accounting_transactions.income', compact(
            'incomeByGroup',
            'outstandingAmount',
            'outstandingDetails',
            'displayDate',
            'startDate',
            'endDate',
            'thai_startDate',
            'thai_endDate'
        ));
    }

        public function reportExpense(Request $request)
    {
        // 🌟 ต้องใส่ตรงนี้! เพื่อให้ Query ข้อมูลออกมาถูกเดือน
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        $startDate = $request->input('date_start') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('date_end') ?? now()->endOfMonth()->format('Y-m-d');

        // 1. ดึงข้อมูลรายจ่ายทั้งหมด (type_id = 2)
        $expenseTransactions = AccountingTransaction::with(['category', 'admin'])
            ->where('status', 'active')
            ->whereHas('category', fn($q) => $q->where('type_id', 2))
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        // 2. จัดกลุ่มรายจ่ายตามชื่อหมวดหมู่
        $expenseByGroup = $expenseTransactions->groupBy('category.name');

        $displayDate = $this->toThaiDate($startDate, false);
        $thai_startDate = $this->toThaiDate($startDate);
        $thai_endDate = $this->toThaiDate($endDate);

        return view('admin.accounting_transactions.expense', compact(
            'expenseByGroup',
            'displayDate',
            'startDate',
            'endDate',
            'thai_startDate',
            'thai_endDate'
        ));
    }
    // print summary

    public function printSummaryPdf(Request $request)
    {
        // 🌟 ต้องใส่ตรงนี้! เพื่อให้ Query ข้อมูลออกมาถูกเดือน
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        $startDate = $request->input('date_start') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('date_end') ?? now()->endOfMonth()->format('Y-m-d');

        // 1. ดึงข้อมูลธุรกรรม
        $transactions = AccountingTransaction::with(['category.type', 'tenant.room.roomPrice.building'])
            ->where('status', 'active')
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        // 2. จัดกลุ่มรายรับตึก
        $buildingIncome = $transactions->where('category.type_id', 1)
            ->filter(fn($t) => str_contains($t->title, 'ค่าเช่าห้อง') || str_contains($t->title, 'ค่าไฟ'))
            ->groupBy(fn($t) => $t->tenant->room->roomPrice->building->name ?? 'ตึกทั่วไป');

        // 3. รายรับอื่นๆ
        $otherIncome = $transactions->where('category.type_id', 1)
            ->filter(fn($t) => !str_contains($t->title, 'ค่าเช่าห้อง') && !str_contains($t->title, 'ค่าไฟ'))
            ->groupBy('category.name')->map->sum('amount');

        // 4. รายจ่าย
        $expenseByCats = $transactions->where('category.type_id', 2)
            ->groupBy('category.name')->map->sum('amount');

        // 5. ยอดค้างรับ
        $outstandingAmount = Invoice::whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->get()
            ->sum('remaining_balance');

        $displayDate = $this->toThaiDate($startDate, false);
        $thai_startDate = $this->toThaiDate($startDate);
        $thai_endDate = $this->toThaiDate($endDate);
        $apartment = DB::table('apartment')->first();

        // 3. สร้าง PDF
        $pdf = Pdf::loadView('admin.accounting_transactions.pdf.print_summary_pdf', compact(
            'buildingIncome',
            'otherIncome',
            'expenseByCats',
            'outstandingAmount',
            'displayDate',
            'startDate',
            'endDate',
            'thai_startDate',
            'thai_endDate',
            'apartment'
        ))->setPaper('a4', 'portrait'); // งบสรุปแนะนำแนวตั้ง (Portrait)

        return $pdf->stream('Accounting_Summary_' . $startDate . '.pdf');
    }

    public function printIncomePdf(Request $request)
    {
        // 🌟 ต้องใส่ตรงนี้! เพื่อให้ Query ข้อมูลออกมาถูกเดือน
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        $startDate = $request->input('date_start') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('date_end') ?? now()->endOfMonth()->format('Y-m-d');

        // 1. ดึงข้อมูลรายรับ (Logic เดียวกับ reportIncome)
        $incomeTransactions = AccountingTransaction::with(['category', 'tenant.room.roomPrice.building'])
            ->where('status', 'active')
            ->whereHas('category', fn($q) => $q->where('type_id', 1))
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        $incomeByGroup = $incomeTransactions->groupBy('category.name')->map(function ($items) {
            return $items->groupBy(function ($item) {
                return $item->tenant->room->roomPrice->building->name ?? '';
            });
        });

        // 2. ข้อมูลยอดค้างรับ
        $outstandingDetails = Invoice::with(['tenant.room', 'details'])
            ->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->get();

        $outstandingAmount = $outstandingDetails->sum('remaining_balance');
        $displayDate = $this->toThaiDate($startDate, false);
        $thai_startDate = $this->toThaiDate($startDate);
        $thai_endDate = $this->toThaiDate($endDate);
        $apartment = DB::table('apartment')->first();

        // 3. สร้าง PDF ในแนวตั้ง (Portrait)
        $pdf = Pdf::loadView('admin.accounting_transactions.pdf.print_income_pdf', compact(
            'incomeByGroup',
            'outstandingAmount',
            'outstandingDetails',
            'displayDate',
            'startDate',
            'endDate',
            'thai_startDate',
            'thai_endDate',
            'apartment'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('Income_Report_' . $startDate . '.pdf');
    }

    public function printExpensePdf(Request $request)
    {
        // 🌟 ต้องใส่ตรงนี้! เพื่อให้ Query ข้อมูลออกมาถูกเดือน
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        $startDate = $request->input('date_start') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('date_end') ?? now()->endOfMonth()->format('Y-m-d');

        $expenseTransactions = AccountingTransaction::with(['category', 'admin'])
            ->where('status', 'active')
            ->whereHas('category', fn($q) => $q->where('type_id', 2))
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        $expenseByGroup = $expenseTransactions->groupBy('category.name');

        $displayDate = $this->toThaiDate($startDate, false);
        $thai_startDate = $this->toThaiDate($startDate);
        $thai_endDate = $this->toThaiDate($endDate);
        $apartment = DB::table('apartment')->first();

        $pdf = Pdf::loadView('admin.accounting_transactions.pdf.print_expense_pdf', compact(
            'expenseByGroup',
            'displayDate',
            'startDate',
            'endDate',
            'thai_startDate',
            'thai_endDate',
            'apartment'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('Expense_Report_' . $startDate . '.pdf');
    }

    // excel
    // ฟังก์ชัน Export Summary
    public function exportSummaryExcel(Request $request) {
        // 🌟 ต้องใส่ตรงนี้! เพื่อให้ Query ข้อมูลออกมาถูกเดือน
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        $startDate = $request->input('date_start') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('date_end') ?? now()->endOfMonth()->format('Y-m-d');

        // 1. ดึงข้อมูลธุรกรรม
        $transactions = AccountingTransaction::with(['category.type', 'tenant.room.roomPrice.building'])
            ->where('status', 'active')
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        // 2. จัดกลุ่มรายรับตึก
        $buildingIncome = $transactions->where('category.type_id', 1)
            ->filter(fn($t) => str_contains($t->title, 'ค่าเช่าห้อง') || str_contains($t->title, 'ค่าไฟ'))
            ->groupBy(fn($t) => $t->tenant->room->roomPrice->building->name ?? 'ตึกทั่วไป');

        // 3. รายรับอื่นๆ
        $otherIncome = $transactions->where('category.type_id', 1)
            ->filter(fn($t) => !str_contains($t->title, 'ค่าเช่าห้อง') && !str_contains($t->title, 'ค่าไฟ'))
            ->groupBy('category.name')->map->sum('amount');

        // 4. รายจ่าย
        $expenseByCats = $transactions->where('category.type_id', 2)
            ->groupBy('category.name')->map->sum('amount');

        // 5. ยอดค้างรับ
        $outstandingAmount = Invoice::whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->get()
            ->sum('remaining_balance');

        $displayDate = $this->toThaiDate($startDate, false);
        $thai_startDate = $this->toThaiDate($startDate);
        $thai_endDate = $this->toThaiDate($endDate);
        $apartment = DB::table('apartment')->first();
        
        $data = compact('buildingIncome', 'otherIncome', 'expenseByCats', 'outstandingAmount', 'displayDate', 'startDate', 'endDate', 'thai_startDate', 'thai_endDate', 'apartment');
        return Excel::download(new SummaryReportExport($data), 'Accounting_Summary_' . $startDate . '.xlsx');
    }

    // ฟังก์ชัน Export Income
    public function exportIncomeExcel(Request $request) {
        // 🌟 ต้องใส่ตรงนี้! เพื่อให้ Query ข้อมูลออกมาถูกเดือน
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
         $startDate = $request->input('date_start') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('date_end') ?? now()->endOfMonth()->format('Y-m-d');

        // 1. ดึงข้อมูลรายรับ (Logic เดียวกับ reportIncome)
        $incomeTransactions = AccountingTransaction::with(['category', 'tenant.room.roomPrice.building'])
            ->where('status', 'active')
            ->whereHas('category', fn($q) => $q->where('type_id', 1))
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        $incomeByGroup = $incomeTransactions->groupBy('category.name')->map(function ($items) {
            return $items->groupBy(function ($item) {
                return $item->tenant->room->roomPrice->building->name ?? '';
            });
        });

        // 2. ข้อมูลยอดค้างรับ
        $outstandingDetails = Invoice::with(['tenant.room', 'details'])
            ->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->get();

        $outstandingAmount = $outstandingDetails->sum('remaining_balance');
        $displayDate = $this->toThaiDate($startDate, false);
        $thai_startDate = $this->toThaiDate($startDate);
        $thai_endDate = $this->toThaiDate($endDate);
        $apartment = DB::table('apartment')->first();

        $data = compact('incomeByGroup', 'outstandingAmount', 'outstandingDetails', 'displayDate', 'startDate', 'endDate', 'thai_startDate', 'thai_endDate', 'apartment');
        return Excel::download(new IncomeReportExport($data), 'Income_Report_' . $startDate . '.xlsx');
    }

    // ฟังก์ชัน Export Expense
    public function exportExpenseExcel(Request $request) {
        // 🌟 ต้องใส่ตรงนี้! เพื่อให้ Query ข้อมูลออกมาถูกเดือน
        $request->merge([
            'date_start' => $this->convertThaiYearToAD($request->date_start),
            'date_end' => $this->convertThaiYearToAD($request->date_end),
        ]);
        $startDate = $request->input('date_start') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('date_end') ?? now()->endOfMonth()->format('Y-m-d');

        $expenseTransactions = AccountingTransaction::with(['category', 'admin'])
            ->where('status', 'active')
            ->whereHas('category', fn($q) => $q->where('type_id', 2))
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        $expenseByGroup = $expenseTransactions->groupBy('category.name');

        $displayDate = $this->toThaiDate($startDate, false);
        $thai_startDate = $this->toThaiDate($startDate);
        $thai_endDate = $this->toThaiDate($endDate);
        $apartment = DB::table('apartment')->first();

        $data = compact('expenseByGroup', 'displayDate', 'startDate', 'endDate', 'thai_startDate', 'thai_endDate', 'apartment');
        return Excel::download(new ExpenseReportExport($data), 'Expense_Report_' . $startDate . '.xlsx');
    }

    private function convertThaiYearToAD($dateString)
    {
        if (!$dateString) return null;

        try {
            $parts = explode('-', $dateString);
            if (count($parts) >= 2) {
                $year = (int) $parts[0];
                // ถ้าปี > 2400 แสดงว่าเป็น พ.ศ. แน่นอน (เช่น 2569)
                if ($year > 2400) {
                    $year = $year - 543;
                    $parts[0] = $year;
                    return implode('-', $parts);
                }
            }
        } catch (\Exception $e) {
            return $dateString;
        }
        return $dateString;
    }

}
