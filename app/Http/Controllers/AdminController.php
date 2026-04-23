<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // เรียกใช้งาน Auth Facade
use Barryvdh\DomPDF\Facade\Pdf; // ใช้งานสำหรับ pdf

use App\Models\RoomPrices;
use App\Models\RoomType;
use App\Models\Building;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\TenantExpense;
use App\Models\MeterReading;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Payment;
use App\Models\AccountingTransaction;
use App\Models\AccountingCategory;
use App\Models\AccountingType;
use App\Models\User;
use App\Models\Maintenance;
class AdminController extends Controller
{
    // =========================================================================
    // 1. ภาพรวมหอพัก (Dashboard หลัก)
    // =========================================================================
    
    public function adminDashboard()
    {
        $now = \Carbon\Carbon::now();
        $currentMonth = $now->format('Y-m');

        // ตัวเลข Summary ด้านบน
        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'มีผู้เช่า')->count();
        $vacantRooms = $totalRooms - $occupiedRooms;

        $overdueInvoices = Invoice::with(['tenant', 'room'])
            ->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
            ->where('due_date', '<', $now->toDateString())
            ->orderBy('due_date', 'asc')
            ->get();

        $pendingMaintenance = Maintenance::with('room')->where('status', 'pending')->orderBy('created_at', 'asc')->get();
        $meterReadingsThisMonth = MeterReading::where('billing_month', $currentMonth)->count();
        $roomsNeedMeter = ($occupiedRooms * 2) - $meterReadingsThisMonth;
        
        $expiringContracts = Tenant::with('room')->where('status', 'กำลังใช้งาน')
            ->whereNotNull('end_date')
            // เปลี่ยนจาก whereBetween เป็นน้อยกว่าหรือเท่ากับ วันนี้ + 30 วัน (เพื่อให้ดึงอดีตมาด้วย)
            ->where('end_date', '<=', $now->copy()->addDays(30)->toDateString())
            ->orderBy('end_date', 'asc') // เรียงจากที่หมดอายุก่อน (เลยกำหนด) ขึ้นแสดงด้านบน
            ->get();

        $pendingRegistrations = Tenant::where('status', 'รออนุมัติ')->count();

        // ข้อมูลตัวกรอง
        $buildings = Building::all();
        $floors = DB::table('room_prices')->select('floor_num')->whereNotNull('floor_num')->distinct()->orderBy('floor_num')->pluck('floor_num');

        // คืนค่าไปยัง View แยก
        return view('admin.dashboard.overview', compact(
            'totalRooms', 'occupiedRooms', 'vacantRooms',
            'overdueInvoices', 'pendingMaintenance',
            'roomsNeedMeter', 'expiringContracts', 'now', 'buildings', 'floors',
            'pendingRegistrations'
        ));
    }

    // =========================================================================
    // 🌟 API 1: สำหรับหน้า "ภาพรวม" (Overview)
    // - ส่งข้อมูล: สัดส่วนห้องพัก (โดนัท), สถานะบิล (โดนัท), ปริมาณคนเข้า-ออก (แท่ง)
    // =========================================================================
    
    public function apiOverviewChart(Request $request)
    {
        try {
            $startMonth = $request->input('start_month') ?: now()->subMonths(5)->format('Y-m');
            $endMonth = $request->input('end_month') ?: now()->format('Y-m');
            $buildingId = $request->input('building_id');
            $floorNum = $request->input('floor_num');

            $rangeStart = \Carbon\Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth()->toDateString();
            $rangeEnd = \Carbon\Carbon::createFromFormat('Y-m', $endMonth)->endOfMonth()->toDateString();
            
            $now = \Carbon\Carbon::now();
            $currentMonth = $now->format('Y-m');

            // 1. สัดส่วนห้องพัก 
            $roomQuery = Room::query();
            if ($buildingId) $roomQuery->whereHas('roomPrice', fn($q) => $q->where('building_id', $buildingId));
            if ($floorNum) $roomQuery->whereHas('roomPrice', fn($q) => $q->where('floor_num', $floorNum));

            $totalRooms = (clone $roomQuery)->count();
            $occupiedCount = (clone $roomQuery)->whereHas('tenants', function($q) use ($rangeStart, $rangeEnd) {
                $q->where('status', 'กำลังใช้งาน')
                  ->where('start_date', '<=', $rangeEnd)
                  ->where(function($subQ) use ($rangeStart) {
                      $subQ->whereNull('end_date')->orWhere('end_date', '>=', $rangeStart);
                  });
            })->count();

            $vacantCount = $totalRooms - $occupiedCount;
            $roomStatus = ['occupied' => $occupiedCount, 'vacant' => $vacantCount, 'total' => $totalRooms];

            // 2. สถานะบิล
            $invoiceQuery = Invoice::query()->whereBetween('billing_month', [$startMonth, $endMonth]);
            if ($buildingId) $invoiceQuery->whereHas('room.roomPrice', fn($q) => $q->where('building_id', $buildingId));
            if ($floorNum) $invoiceQuery->whereHas('room.roomPrice', fn($q) => $q->where('floor_num', $floorNum));

            $invoiceStatus = [
                'paid' => (clone $invoiceQuery)->where('status', 'ชำระแล้ว')->count(),
                'partial' => (clone $invoiceQuery)->where('status', 'ชำระบางส่วน')->count(),
                'unpaid' => (clone $invoiceQuery)->where('status', 'ค้างชำระ')->count(),
                'pending_send' => (clone $invoiceQuery)->where('status', 'กรุณาส่งบิล')->count(),
            ];

            // 🌟 3. กราฟแท่ง ปริมาณการย้ายเข้า - ย้ายออก (Move-in / Move-out Trend)
            $occupancyTrendLabels = [];
            $moveInData = [];  // ยอดเข้าใหม่
            $moveOutData = []; // ยอดย้ายออก
            
            $currDate = \Carbon\Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
            $limitDate = \Carbon\Carbon::createFromFormat('Y-m', $endMonth)->startOfMonth();

            while ($currDate <= $limitDate) {
                $mStart = $currDate->copy()->startOfMonth()->toDateString();
                $mEnd = $currDate->copy()->endOfMonth()->toDateString();
                $occupancyTrendLabels[] = $currDate->locale('th')->isoFormat('MMM YY');
                
                $tenantQuery = \App\Models\Tenant::query();

                // กรองตึก/ชั้น (ถ้ามี)
                if ($buildingId || $floorNum) {
                    $tenantQuery->whereHas('room.roomPrice', function($q) use ($buildingId, $floorNum) {
                        if ($buildingId) $q->where('building_id', $buildingId);
                        if ($floorNum) $q->where('floor_num', $floorNum);
                    });
                }

                // 🌟 นับจำนวนคนที่ "ย้ายเข้า" ในเดือนนี้ (อิงตาม start_date)
                $moveInData[] = (clone $tenantQuery)
                    ->whereBetween('start_date', [$mStart, $mEnd])
                    ->count();
                
                // 🌟 นับจำนวนคนที่ "ย้ายออก" ในเดือนนี้ (อิงตาม end_date)
                $moveOutData[] = (clone $tenantQuery)
                    ->whereBetween('end_date', [$mStart, $mEnd])
                    // ไม่ต้องเช็ค status ก็ได้เผื่อออกไปแล้ว แต่ถ้าอยากเอาชัวร์ก็เช็คได้
                    // ->where('status', 'สิ้นสุดสัญญา') 
                    ->count();

                $currDate->addMonth();
            }

            // 4. คำนวณข้อมูล KPI อื่นๆ 
            $overdueQuery = Invoice::query()->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])->where('due_date', '<', $now->toDateString());
            if ($buildingId) $overdueQuery->whereHas('room.roomPrice', fn($q) => $q->where('building_id', $buildingId));
            if ($floorNum) $overdueQuery->whereHas('room.roomPrice', fn($q) => $q->where('floor_num', $floorNum));
            $overdueCount = $overdueQuery->count();

            $maintenanceQuery = Maintenance::query()->where('status', 'pending');
            if ($buildingId) $maintenanceQuery->whereHas('room.roomPrice', fn($q) => $q->where('building_id', $buildingId));
            if ($floorNum) $maintenanceQuery->whereHas('room.roomPrice', fn($q) => $q->where('floor_num', $floorNum));
            $pendingMaintenanceCount = $maintenanceQuery->count();

            $meterQuery = MeterReading::query()->where('billing_month', $currentMonth);
            if ($buildingId || $floorNum) {
                $filteredRoomIds = clone $roomQuery->pluck('id');
                $meterQuery->whereIn('room_id', $filteredRoomIds);
            }
            $meterReadingsThisMonth = $meterQuery->count();
            $roomsNeedMeter = ($occupiedCount * 2) - $meterReadingsThisMonth;
            if ($roomsNeedMeter < 0) $roomsNeedMeter = 0;

            // 5. ดึงรายชื่อสัญญาใกล้หมดอายุ (ภายใน 30 วัน จนถึงเลยกำหนด)
            $limitExpiringDate = $now->copy()->addDays(30)->toDateString();
            
            $expiringContractsQuery = Tenant::query()
                ->with(['room.roomPrice.building'])
                ->where('status', 'กำลังใช้งาน')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', $limitExpiringDate) 
                ->orderBy('end_date', 'asc'); 

            if ($buildingId) $expiringContractsQuery->whereHas('room.roomPrice', fn($q) => $q->where('building_id', $buildingId));
            if ($floorNum) $expiringContractsQuery->whereHas('room.roomPrice', fn($q) => $q->where('floor_num', $floorNum));

            $expiringContracts = $expiringContractsQuery->get()->map(function($tenant) use ($now) {
                $daysRemaining = (int) $now->diffInDays(\Carbon\Carbon::parse($tenant->end_date), false);
                return [
                    'room_number' => $tenant->room->room_number ?? '-',
                    'tenant_name' => $tenant->first_name . ' ' . $tenant->last_name, // 🌟 แก้จาก firstname เป็น first_name
                    'phone' => $tenant->phone ?? '-',
                    'building_name' => $tenant->room->roomPrice->building->name ?? '-',
                    'end_date' => \Carbon\Carbon::parse($tenant->end_date)->locale('th')->isoFormat('D MMM YY'),
                    'days_remaining' => $daysRemaining,
                    'is_overdue' => $daysRemaining < 0,
                ];
            });

            return response()->json([
                'room_status' => $roomStatus,
                'invoice_status' => $invoiceStatus,
                'occupancy_trend' => [
                    'labels' => $occupancyTrendLabels,
                    'data' => $moveInData,         // 🌟 ยอดเข้าใหม่
                    'terminated' => $moveOutData   // 🌟 ยอดย้ายออก
                ],
                'expiring_contracts' => $expiringContracts, 
                'kpi' => [
                    'overdue_invoices' => $overdueCount,
                    'pending_maintenance' => $pendingMaintenanceCount,
                    'rooms_need_meter' => $roomsNeedMeter,
                    'expiring_contracts_count' => $expiringContracts->count() 
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    // =========================================================================
    // 2. วิเคราะห์รายรับค่าเช่า
    // =========================================================================

    public function dashboardRentalIncome()
    {
        $buildings = Building::all();
        $floors = DB::table('room_prices')->select('floor_num')->whereNotNull('floor_num')->distinct()->orderBy('floor_num')->pluck('floor_num');
        return view('admin.dashboard.rental_income', compact('buildings', 'floors'));
    }
    // =========================================================================
    // 🌟 API: สำหรับหน้า "วิเคราะห์รายรับค่าเช่า" (Rental Income)
    // =========================================================================

    public function apiRentalIncomeChart(Request $request)
    {
        try {
            $startMonth = $request->input('start_month') ?: now()->subMonths(5)->format('Y-m');
            $endMonth = $request->input('end_month') ?: now()->format('Y-m');
            $buildingId = $request->input('building_id');
            $floorNum = $request->input('floor_num');

            $rangeStart = \Carbon\Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth()->toDateString();
            $rangeEnd = \Carbon\Carbon::createFromFormat('Y-m', $endMonth)->endOfMonth()->toDateString();

            // ---------------------------------------------------------
            // 1. หา "ยอดค้างรับ" จากตาราง Invoice
            // ---------------------------------------------------------
            $invoiceQuery = Invoice::query()->whereBetween('billing_month', [$startMonth, $endMonth]);
            
            if ($buildingId) {
                $invoiceQuery->whereHas('room.roomPrice', fn($q) => $q->where('building_id', $buildingId));
            }
            if ($floorNum) {
                $invoiceQuery->whereHas('room.roomPrice', fn($q) => $q->where('floor_num', $floorNum));
            }

            $unpaidAmount = (clone $invoiceQuery)->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
                                                 ->get()
                                                 ->sum('remaining_balance');

            // ---------------------------------------------------------
            // 🌟 2. หา "รายรับที่จ่ายแล้ว" (เฉพาะที่เกี่ยวกับบิลค่าเช่า)
            // ---------------------------------------------------------
            $txQuery = AccountingTransaction::query()
                ->where('status', 'active')
                ->whereHas('category', fn($q) => $q->where('type_id', 1)) // 1 = รายรับ
                ->whereBetween('entry_date', [$rangeStart, $rangeEnd])
                // ✅ แก้ไข: กรองเอาเฉพาะรายรับที่มาจาก "การจ่ายบิล (Payment)" หรือมีคำว่า "ค่าเช่า" ในชื่อ
                ->where(function($q) {
                    $q->whereNotNull('payment_id') // เกิดจากการรับชำระบิลปกติ
                      ->orWhere('title', 'like', '%ค่าเช่า%') // หรือระบุชื่อว่าเป็นค่าเช่า
                      ->orWhereHas('category', fn($subQ) => $subQ->where('name', 'like', '%ค่าเช่า%'));
                });
                
            // รองรับทั้งระบบเก่า (tenant) และระบบใหม่ (building_id, room_id ตรงๆ)
            if ($buildingId) {
                $txQuery->where(function($query) use ($buildingId) {
                    $query->where('building_id', $buildingId)
                          ->orWhereHas('room.roomPrice', fn($q) => $q->where('building_id', $buildingId))
                          ->orWhereHas('tenant.room.roomPrice', fn($q) => $q->where('building_id', $buildingId));
                });
            }
            if ($floorNum) {
                $txQuery->where(function($query) use ($floorNum) {
                    $query->whereHas('room.roomPrice', fn($q) => $q->where('floor_num', $floorNum))
                          ->orWhereHas('tenant.room.roomPrice', fn($q) => $q->where('floor_num', $floorNum));
                });
            }

            // amount เป็นคอลัมน์ใน DB อยู่แล้ว สามารถ ->sum() ได้เลย
            $paidAmount = (clone $txQuery)->sum('amount');
            $totalExpected = $paidAmount + $unpaidAmount; 

            // ---------------------------------------------------------
            // 3. กราฟแท่ง รายรับรายเดือน (Monthly Trend)
            // ---------------------------------------------------------
            $trendLabels = [];
            $trendPaid = [];
            $trendUnpaid = [];

            // ดึงข้อมูลออกมาก่อน 1 ก้อนเพื่อประหยัด Query ในลูป
            $transactions = $txQuery->get();
            $invoicesData = $invoiceQuery->get();

            $currDate = \Carbon\Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
            $limitDate = \Carbon\Carbon::createFromFormat('Y-m', $endMonth)->startOfMonth();

            while ($currDate <= $limitDate) {
                $targetMonth = $currDate->format('Y-m'); // "2025-10"
                $trendLabels[] = $currDate->locale('th')->isoFormat('MMM YY');

                // ✅ แก้ไข: ใช้ filter กรองวันที่แม่นยำขึ้นจาก Collection
                $monthTxs = $transactions->filter(function($t) use ($targetMonth) {
                    return \Carbon\Carbon::parse($t->entry_date)->format('Y-m') === $targetMonth;
                });
                $trendPaid[] = $monthTxs->sum('amount');
                
                // ยอดค้างของบิลเดือนนั้นๆ
                $monthInvoices = $invoicesData->filter(function($inv) use ($targetMonth) {
                    return $inv->billing_month === $targetMonth && in_array($inv->status, ['ค้างชำระ', 'ชำระบางส่วน']);
                });
                $trendUnpaid[] = $monthInvoices->sum('remaining_balance');

                $currDate->addMonth();
            }

            // ---------------------------------------------------------
            // 4. กราฟที่ 3: สัดส่วนรายรับแยกตามตึก/ประเภทห้อง
            // ---------------------------------------------------------
            $breakdownLabels = [];
            $breakdownData = [];
            
            // ใช้ Collection $transactions ที่ดึงมาก่อนหน้านี้ และโหลด relation เพิ่ม
            $transactions->load(['tenant.room.roomPrice.building', 'tenant.room.roomPrice.roomType', 'room.roomPrice.roomType', 'building']);

            if ($buildingId) {
                // ถ้าเลือกตึก ให้จัดกลุ่มตามประเภทห้อง (Room Type)
                $groupData = $transactions->groupBy(function($t) {
                    // หาจากระบบห้องตรงๆ ก่อน (ระบบใหม่) ถ้าไม่มีก็หาจากผู้เช่า (ระบบเก่า)
                    if ($t->room && $t->room->roomPrice && $t->room->roomPrice->roomType) return $t->room->roomPrice->roomType->name;
                    return $t->tenant?->room?->roomPrice?->roomType?->name ?? 'ไม่ระบุประเภท';
                })->map->sum('amount');
                $breakdownTitle = 'รายรับแยกตามประเภทห้องพัก';
            } else {
                // ถ้าไม่ระบุตึก ให้จัดกลุ่มตามอาคาร (Building)
                $groupData = $transactions->groupBy(function($t) {
                    if ($t->building) return $t->building->name; // ระบบใหม่
                    if ($t->room && $t->room->roomPrice && $t->room->roomPrice->building) return $t->room->roomPrice->building->name; // ระบบกลาง
                    return $t->tenant?->room?->roomPrice?->building?->name ?? 'ส่วนกลาง/ไม่ระบุตึก';
                })->map->sum('amount');
                $breakdownTitle = 'รายรับแยกตามอาคาร';
            }

            // เรียงลำดับจากรายรับมากไปน้อย
            $groupData = collect($groupData)->sortByDesc(fn($val) => $val);

            foreach($groupData as $label => $amount) {
                $breakdownLabels[] = $label;
                $breakdownData[] = $amount;
            }

            return response()->json([
                'summary' => [
                    'paid' => (float)$paidAmount,
                    'unpaid' => (float)$unpaidAmount,
                    'total' => (float)$totalExpected
                ],
                'trend' => [
                    'labels' => $trendLabels,
                    'paid' => $trendPaid,
                    'unpaid' => $trendUnpaid
                ],
                'breakdown' => [
                    'title' => $breakdownTitle,
                    'labels' => $breakdownLabels,
                    'data' => $breakdownData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'API Error: ' . $e->getMessage(), 
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    // =========================================================================
    // 3. วิเคราะห์รายรับ-รายจ่าย (กระแสเงินสดเดิม)
    // =========================================================================

    public function dashboardCashflow()
    {
        $buildings = Building::all();
        $floors = DB::table('room_prices')->select('floor_num')->whereNotNull('floor_num')->distinct()->orderBy('floor_num')->pluck('floor_num');
        return view('admin.dashboard.cashflow', compact('buildings', 'floors'));
    }

    // =========================================================================
    // 🌟 API: สำหรับหน้า "วิเคราะห์รายรับ-รายจ่าย" (Cashflow)
    // =========================================================================
    
    public function apiCashflowChart(Request $request)
    {
        try {
            $startMonth = $request->input('start_month') ?: now()->subMonths(5)->format('Y-m');
            $endMonth = $request->input('end_month') ?: now()->format('Y-m');
            $buildingId = $request->input('building_id');
            $floorNum = $request->input('floor_num');

            $rangeStart = \Carbon\Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth()->toDateString();
            $rangeEnd = \Carbon\Carbon::createFromFormat('Y-m', $endMonth)->endOfMonth()->toDateString();

            // 🌟 1. ดึงข้อมูล Transaction ทั้งหมดในช่วงเวลาที่กำหนด พร้อมโหลด Relation ของตึกที่เพิ่มมาใหม่
            $txQuery = AccountingTransaction::query()
                ->with(['category', 'building', 'room.roomPrice.building', 'tenant.room.roomPrice.building']) 
                ->where('status', 'active')
                ->whereBetween('entry_date', [$rangeStart, $rangeEnd]);

            // 🌟 2. กรองตามตึกและชั้น (รองรับทั้งวิธีใหม่ที่เก็บ building_id ตรงๆ และวิธีเก่าที่เก็บผ่าน tenant)
            if ($buildingId) {
                $txQuery->where(function($query) use ($buildingId) {
                    $query->where('building_id', $buildingId) // เช็คจากระบบใหม่
                          ->orWhereHas('room.roomPrice', fn($q) => $q->where('building_id', $buildingId)) // เช็คจากห้อง
                          ->orWhereHas('tenant.room.roomPrice', fn($q) => $q->where('building_id', $buildingId)); // เช็คจากผู้เช่า
                });
            }

            if ($floorNum) {
                $txQuery->where(function($query) use ($floorNum) {
                    $query->whereHas('room.roomPrice', fn($q) => $q->where('floor_num', $floorNum))
                          ->orWhereHas('tenant.room.roomPrice', fn($q) => $q->where('floor_num', $floorNum));
                });
            }

            $transactions = $txQuery->get();

            // ---------------------------------------------------------
            // 1. กราฟแท่ง รายรับ-รายจ่ายรายเดือน (Monthly Trend)
            // ---------------------------------------------------------
            $trendLabels = [];
            $trendIncome = [];
            $trendExpense = [];
            
            $currDate = \Carbon\Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
            $limitDate = \Carbon\Carbon::createFromFormat('Y-m', $endMonth)->startOfMonth();

            while ($currDate <= $limitDate) {
                $targetMonth = $currDate->format('Y-m'); // เช่น "2025-10"
                $trendLabels[] = $currDate->locale('th')->isoFormat('MMM YY');
                
                $monthTxs = $transactions->filter(function($t) use ($targetMonth) {
                    return \Carbon\Carbon::parse($t->entry_date)->format('Y-m') === $targetMonth;
                });

                $trendIncome[] = $monthTxs->where('category.type_id', 1)->sum('amount'); // 1 = รายรับ
                $trendExpense[] = $monthTxs->where('category.type_id', 2)->sum('amount'); // 2 = รายจ่าย

                $currDate->addMonth();
            }

            // ---------------------------------------------------------
            // 2. จัดกลุ่มรายรับและรายจ่ายตามหมวดหมู่ 
            // ---------------------------------------------------------
            $incomeByCategory = $transactions->where('category.type_id', 1)->groupBy(fn($t) => $t->category->name ?? 'ไม่ระบุหมวดหมู่')->map->sum('amount')->sortByDesc(fn($val) => $val);
            $expenseByCategory = $transactions->where('category.type_id', 2)->groupBy(fn($t) => $t->category->name ?? 'ไม่ระบุหมวดหมู่')->map->sum('amount')->sortByDesc(fn($val) => $val);

            $totalIncome = $incomeByCategory->sum();
            $totalExpense = $expenseByCategory->sum();
            $netProfit = $totalIncome - $totalExpense;

            $topIncome = $incomeByCategory->map(fn($amount, $name) => ['name' => $name, 'total' => $amount])->values();
            $topExpense = $expenseByCategory->map(fn($amount, $name) => ['name' => $name, 'total' => $amount])->values();

            // ---------------------------------------------------------
            // 🌟 3. เปรียบเทียบรายรับ-รายจ่าย แยกตามอาคาร 
            // ---------------------------------------------------------
            $buildingLabels = [];
            $buildingIncome = [];
            $buildingExpense = [];

            // จัดกลุ่มโดยเช็คจากข้อมูลระบบใหม่ก่อน ถ้าไม่มีค่อยถอยไปหาระบบเก่า
            $groupedByBuilding = $transactions->groupBy(function($t) {
                if ($t->building) return $t->building->name; // มีการบันทึกตึกตรงๆ (ระบบใหม่)
                if ($t->room && $t->room->roomPrice && $t->room->roomPrice->building) return $t->room->roomPrice->building->name; // อิงจากห้อง
                if ($t->tenant && $t->tenant->room && $t->tenant->room->roomPrice && $t->tenant->room->roomPrice->building) return $t->tenant->room->roomPrice->building->name; // อิงจากผู้เช่า (ระบบเก่า)
                
                return 'ส่วนกลาง/ไม่ระบุอาคาร';
            });

            foreach ($groupedByBuilding as $bName => $bTxs) {
                $buildingLabels[] = $bName;
                $buildingIncome[] = $bTxs->where('category.type_id', 1)->sum('amount');
                $buildingExpense[] = $bTxs->where('category.type_id', 2)->sum('amount');
            }

            return response()->json([
                'summary' => [
                    'income' => (float)$totalIncome,
                    'expense' => (float)$totalExpense,
                    'net_profit' => (float)$netProfit
                ],
                'trend' => [
                    'labels' => $trendLabels,
                    'income' => $trendIncome,
                    'expense' => $trendExpense
                ],
                'building_comparison' => [ 
                    'labels' => $buildingLabels,
                    'income' => $buildingIncome,
                    'expense' => $buildingExpense
                ],
                'breakdown' => [
                    'income_labels' => $incomeByCategory->keys(),
                    'income_data' => $incomeByCategory->values(),
                    'expense_labels' => $expenseByCategory->keys(),
                    'expense_data' => $expenseByCategory->values(),
                ],
                'top_income' => $topIncome,
                'top_expense' => $topExpense
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'API Error: ' . $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

    // =========================================================================
    // 4. วิเคราะห์มิเตอร์น้ำ-ไฟ
    // =========================================================================
    
    public function dashboardMeter()
    {
        $buildings = Building::all();
        $floors = DB::table('room_prices')->select('floor_num')->whereNotNull('floor_num')->distinct()->orderBy('floor_num')->pluck('floor_num');
        return view('admin.dashboard.meter_analysis', compact('buildings', 'floors'));
    }

    // =========================================================================
    // 🌟 API: สำหรับหน้า "วิเคราะห์มิเตอร์น้ำ-ไฟ" (Meter Analysis)
    // =========================================================================

    public function apiMeterChart(Request $request)
    {
        try {
            $startMonth = $request->input('start_month') ?: now()->subMonths(5)->format('Y-m');
            $endMonth = $request->input('end_month') ?: now()->format('Y-m');
            $buildingId = $request->input('building_id');
            $floorNum = $request->input('floor_num');

            // 1. ดึงข้อมูล MeterReading ตามช่วงเวลาที่เลือก
            $meterQuery = MeterReading::query()
                ->with(['room.roomPrice.building'])
                ->whereBetween('billing_month', [$startMonth, $endMonth]);

            if ($buildingId) $meterQuery->whereHas('room.roomPrice', fn($q) => $q->where('building_id', $buildingId));
            if ($floorNum) $meterQuery->whereHas('room.roomPrice', fn($q) => $q->where('floor_num', $floorNum));

            $meters = $meterQuery->get();

            // 2. ตัวแปรสำหรับเก็บข้อมูลกราฟ
            $trendLabels = [];
            $elecTrend = [];
            $waterTrend = [];
            $tableData = [];

            $currDate = \Carbon\Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
            $limitDate = \Carbon\Carbon::createFromFormat('Y-m', $endMonth)->startOfMonth();

            while ($currDate <= $limitDate) {
                $mStr = $currDate->format('Y-m');
                $label = $currDate->locale('th')->isoFormat('MMM YY');
                
                $monthData = $meters->where('billing_month', $mStr);

                // แยกน้ำและไฟ 
                $elecUnits = $monthData->filter(fn($m) => str_contains(strtolower($m->meter_type), 'ไฟ') || str_contains(strtolower($m->meter_type), 'electric'))->sum('units_used');
                $waterUnits = $monthData->filter(fn($m) => str_contains(strtolower($m->meter_type), 'น้ำ') || str_contains(strtolower($m->meter_type), 'water'))->sum('units_used');

                $trendLabels[] = $label;
                $elecTrend[] = $elecUnits;
                $waterTrend[] = $waterUnits;

                $tableData[] = [
                    'month' => $label,
                    'elec' => $elecUnits,
                    'water' => $waterUnits
                ];

                $currDate->addMonth();
            }

            // 3. ข้อมูลสำหรับ KPI และ Top 5 (อิงจากเดือนล่าสุดที่เลือก)
            $latestData = $meters->where('billing_month', $endMonth);
            
            $elecLatest = $latestData->filter(fn($m) => str_contains(strtolower($m->meter_type), 'ไฟ') || str_contains(strtolower($m->meter_type), 'electric'));
            $waterLatest = $latestData->filter(fn($m) => str_contains(strtolower($m->meter_type), 'น้ำ') || str_contains(strtolower($m->meter_type), 'water'));

            $totalElec = $elecLatest->sum('units_used');
            $totalWater = $waterLatest->sum('units_used');

            $avgElec = $elecLatest->count() > 0 ? $totalElec / $elecLatest->count() : 0;
            $avgWater = $waterLatest->count() > 0 ? $totalWater / $waterLatest->count() : 0;

            $topElec = $elecLatest->sortByDesc('units_used')->take(5)->map(fn($m) => [
                'room' => $m->room->room_number ?? 'ไม่ระบุ',
                'units' => $m->units_used
            ])->values();

            $topWater = $waterLatest->sortByDesc('units_used')->take(5)->map(fn($m) => [
                'room' => $m->room->room_number ?? 'ไม่ระบุ',
                'units' => $m->units_used
            ])->values();

            // ==========================================
            // 🌟 4. ข้อมูลกราฟแท่งเปรียบเทียบแยกตามตึก
            // ==========================================
            $buildingLabels = [];
            $buildingElec = [];
            $buildingWater = [];

            // จัดกลุ่มมิเตอร์ทั้งหมดตามชื่อตึก
            $groupedByBuilding = $meters->groupBy(function($m) {
                return $m->room->roomPrice->building->name ?? 'ไม่ระบุอาคาร';
            });

            foreach ($groupedByBuilding as $bName => $bMeters) {
                $buildingLabels[] = $bName;
                $buildingElec[] = $bMeters->filter(fn($m) => str_contains(strtolower($m->meter_type), 'ไฟ') || str_contains(strtolower($m->meter_type), 'electric'))->sum('units_used');
                $buildingWater[] = $bMeters->filter(fn($m) => str_contains(strtolower($m->meter_type), 'น้ำ') || str_contains(strtolower($m->meter_type), 'water'))->sum('units_used');
            }

            return response()->json([
                'summary' => [
                    'total_elec' => (float)$totalElec,
                    'total_water' => (float)$totalWater,
                    'avg_elec' => round($avgElec, 1),
                    'avg_water' => round($avgWater, 1),
                ],
                'trend' => [
                    'labels' => $trendLabels,
                    'elec' => $elecTrend,
                    'water' => $waterTrend,
                ],
                'building_comparison' => [ // 🌟 ส่งข้อมูลตึกไปให้ View
                    'labels' => $buildingLabels,
                    'elec' => $buildingElec,
                    'water' => $buildingWater
                ],
                'top_elec' => $topElec,
                'top_water' => $topWater,
                'table_data' => array_reverse($tableData)
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'API Error: ' . $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

    // =========================================================================
    // 🌟 API 2: สำหรับหน้า "วิเคราะห์รายรับ-รายจ่าย" (Cashflow)
    // - ส่งข้อมูล: กราฟแท่งรายรับ/รายจ่าย, Top Income, Top Expense
    // =========================================================================

    // public function apiCashflowChart(Request $request)
    // {
    //     $startMonth = $request->input('start_month', now()->subMonths(5)->format('Y-m'));
    //     $endMonth = $request->input('end_month', now()->format('Y-m'));
    //     $buildingId = $request->input('building_id');
    //     $floorNum = $request->input('floor_num');

    //     $rangeStart = \Carbon\Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth()->toDateString();
    //     $rangeEnd = \Carbon\Carbon::createFromFormat('Y-m', $endMonth)->endOfMonth()->toDateString();

    //     $baseTxQuery = AccountingTransaction::where('status', 'active')->whereBetween('entry_date', [$rangeStart, $rangeEnd]);
    //     if ($buildingId) $baseTxQuery->whereHas('tenant.room.roomPrice', fn($q) => $q->where('building_id', $buildingId));
    //     if ($floorNum) $baseTxQuery->whereHas('tenant.room.roomPrice', fn($q) => $q->where('floor_num', $floorNum));

    //     $labelsRange = [];
    //     $incomeData = [];
    //     $expenseData = [];
    //     $currentDate = \Carbon\Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
    //     $endDateLimit = \Carbon\Carbon::createFromFormat('Y-m', $endMonth)->startOfMonth();

    //     while ($currentDate <= $endDateLimit) {
    //         $targetMonth = $currentDate->format('Y-m');
    //         $labelsRange[] = $currentDate->locale('th')->isoFormat('MMM YY');
    //         $incomeData[] = (clone $baseTxQuery)->whereHas('category', fn($q) => $q->where('type_id', 1))->where('entry_date', 'like', $targetMonth . '%')->sum('amount');
    //         $expenseData[] = (clone $baseTxQuery)->whereHas('category', fn($q) => $q->where('type_id', 2))->where('entry_date', 'like', $targetMonth . '%')->sum('amount');
    //         $currentDate->addMonth();
    //     }

    //     $topIncome = (clone $baseTxQuery)->with('category')->whereHas('category', fn($q) => $q->where('type_id', 1))
    //         ->select('category_id', DB::raw('SUM(amount) as total'))->groupBy('category_id')->orderByDesc('total')->get()
    //         ->map(fn($item) => ['name' => $item->category->name ?? 'รายรับอื่นๆ', 'total' => $item->total]);

    //     $topExpense = (clone $baseTxQuery)->with('category')->whereHas('category', fn($q) => $q->where('type_id', 2))
    //         ->select('category_id', DB::raw('SUM(amount) as total'))->groupBy('category_id')->orderByDesc('total')->get()
    //         ->map(fn($item) => ['name' => $item->category->name ?? 'รายจ่ายอื่นๆ', 'total' => $item->total]);

    //     return response()->json([
    //         'cashflow' => ['labels' => $labelsRange, 'income' => $incomeData, 'expense' => $expenseData],
    //         'top_income' => $topIncome,  
    //         'top_expense' => $topExpense
    //     ]);
    // }
    // ---------------------------------------------
// ไปหน้า ตั้งค่าอพาร์ทเม้นท์ settingApartment

    // ---------------------------------------------

// ไปหน้า จัดการประเภทตึก Building ตึก 2 4 5 ชั้น

    // ---------------------------------------------

// จัดการประเภทห้อง Room Type

    // ---------------------------------------------

// จัดการราคาห้อง Room_price

    // ---------------------------------------------

// จัดการห้อง Rooms

    // ---------------------------------------------
// [Maintenance - Admin] ส่วนบำรุงห้องพัก (จัดการโดยแอดมิน)

    // ---------------------------------------------

// จัดการผู้เช่า Tenant
 
    // ---------------------------------------------
// จัดการค่าใช้จ่ายกับผู้เช่า Tenant Expenses

    // ---------------------------------------------

// จดมิเตอร์น้ำไฟ Meter Readings

    // ---------------------------------------------

// จัดการระบบ Invoice

    // ---------------------------------------------

// ระบบจัดการ accounting_category


    // ---------------------------------------------

// จัดการ payment การชำระค่าเช่าของ admin ให้ admin จัดการจ่ายค่าเช่าลงระบบ

    // ---------------------------------------------

// จัดการ รายรับรายจ่าย accounting_transaction

    // ---------------------------------------------

// จัดการผู้ดูแลระบบ Admin


    // ---------------------------------------------
}
