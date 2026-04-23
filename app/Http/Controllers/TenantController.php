<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Events\NewMaintenanceRequest;
use App\Models\Maintenance;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Payment;
use Carbon\Carbon;

// PDF
use Barryvdh\DomPDF\Facade\Pdf;
// นำเข้า Trait
use App\Traits\FormatHelper;
class TenantController extends Controller
{
     use FormatHelper; // เรียกใช้ Trait ใน Class
    /** สถานะที่ถือว่ายังค้างชำระ — ใช้ร่วมกันทุกหน้า */
    private const UNPAID_STATUSES = ['ค้างชำระ', 'ส่งบิลแล้ว', 'ชำระบางส่วน'];

    /** แปลง status → label ภาษาไทย */
    private const STATUS_LABELS = [
        'ค้างชำระ' => 'รอชำระ',
        'ส่งบิลแล้ว' => 'ส่งบิลแล้ว',
        'ชำระบางส่วน' => 'ชำระบางส่วน',
        'ชำระแล้ว' => 'ชำระแล้ว',
    ];

    // ใช้สำหรับ Route /dashboard
    public function index()
    {
        return $this->loadDashboardView();
    }

    // 🔥 เพิ่มฟังก์ชันนี้สำหรับ Route /index (แก้ปัญหา Route not defined)
    public function tenantIndex()
    {
        return $this->loadDashboardView();
    }

    public function maintenanceIndex()
    {
        return view('tenant.maintenance.index');
    }

    // ฟังก์ชันกลางสำหรับโหลดหน้า Dashboard (จะได้ไม่ต้องเขียนซ้ำ)
    private function loadDashboardView()
    {
        $tenant = Auth::guard('tenant')->user();
        $now = Carbon::now();
        $dashboardInvoiceVersion = '0|0|0|0.00';
        
        // ควรดึงความสัมพันธ์ room มาด้วย เพื่อป้องกัน Error หากไม่มีข้อมูลห้อง
        if($tenant) {
            $tenant->load('room.roomPrice.roomType'); 
        }

        $apartment = DB::table('apartment')->first();

        // ดึงรายการแจ้งซ่อมล่าสุดของผู้เช่าคนนี้เท่านั้น (กรองด้วย tenant_id ไม่ใช่ room_id
        // เพื่อป้องกันผู้เช่าใหม่เห็นประวัติของผู้เช่าคนก่อนในห้องเดิม)
        $maintenanceRequests = collect();
        if ($tenant) {
            $maintenanceRequests = Maintenance::where('tenant_id', $tenant->id)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get();
        }

        // ===== ข้อมูลเพิ่มเติมสำหรับ Dashboard =====

        // ใบแจ้งหนี้ล่าสุดที่ยังไม่ชำระ
        $latestUnpaidInvoice = null;
        $totalUnpaidAmount = 0;
        $unpaidInvoiceCount = 0;
        if ($tenant) {
            $latestUnpaidInvoice = Invoice::where('tenant_id', $tenant->id)
                ->whereIn('status', self::UNPAID_STATUSES)
                ->orderBy('billing_month', 'desc')
                ->first();

            $unpaidInvoices = Invoice::where('tenant_id', $tenant->id)
                ->whereIn('status', self::UNPAID_STATUSES)
                ->get();
            $unpaidInvoiceCount = $unpaidInvoices->count();
            $totalUnpaidAmount = $unpaidInvoices->sum('remaining_balance');

            $latestUnpaidUpdatedAt = $unpaidInvoices->max('updated_at');
            $dashboardInvoiceVersion = implode('|', [
                $latestUnpaidInvoice?->id ?? 0,
                $latestUnpaidUpdatedAt ? Carbon::parse($latestUnpaidUpdatedAt)->timestamp : 0,
                $unpaidInvoiceCount,
                number_format((float) $totalUnpaidAmount, 2, '.', ''),
            ]);
        }

        // มิเตอร์เดือนนี้ (ค่าน้ำ / ค่าไฟ)
        $currentMonth = $now->format('Y-m');
        $meterWater = null;
        $meterElectric = null;
        if ($tenant && $tenant->room_id) {
            $meterWater = MeterReading::where('room_id', $tenant->room_id)
                ->where('meter_type', 'water')
                ->where('billing_month', $currentMonth)
                ->first();

            $meterElectric = MeterReading::where('room_id', $tenant->room_id)
                ->where('meter_type', 'electric')
                ->where('billing_month', $currentMonth)
                ->first();
        }

        // ประวัติชำระเงินล่าสุด 3 รายการ
        $recentPayments = collect();
        if ($tenant) {
            $recentPayments = Payment::whereHas('invoice', function ($q) use ($tenant) {
                    $q->where('tenant_id', $tenant->id);
                })
                ->where('status', 'active')
                ->orderBy('payment_date', 'desc')
                ->take(3)
                ->get();
        }

        return view('tenant.index', compact(
            'tenant', 'apartment', 'maintenanceRequests',
            'latestUnpaidInvoice', 'totalUnpaidAmount', 'unpaidInvoiceCount',
            'meterWater', 'meterElectric',
            'recentPayments', 'now', 'dashboardInvoiceVersion'
        ));
    }

    public function dashboardInvoiceVersion()
    {
        $tenant = Auth::guard('tenant')->user();

        if (!$tenant) {
            return response()->json(['version' => '0|0|0|0.00']);
        }

        $unpaidBaseQuery = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', self::UNPAID_STATUSES);

        $latestUnpaidInvoice = (clone $unpaidBaseQuery)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first(['id', 'updated_at']);

        $unpaidInvoices = (clone $unpaidBaseQuery)->get();
        $unpaidInvoiceCount = $unpaidInvoices->count();
        $totalUnpaidAmount = $unpaidInvoices->sum('remaining_balance');

        $version = implode('|', [
            $latestUnpaidInvoice?->id ?? 0,
            $latestUnpaidInvoice?->updated_at?->timestamp ?? 0,
            $unpaidInvoiceCount,
            number_format((float) $totalUnpaidAmount, 2, '.', ''),
        ]);

        return response()->json(['version' => $version]);
    }

    public function logout(Request $request)
    {
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('tenant.loginForm');
    }
 // ---------------------------------------------
    // [Maintenance - Tenant] ส่งคำขอแจ้งซ่อมจากผู้เช่า
    public function sendMaintenance(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'details' => 'nullable|string',
        ]);

        $tenant = Auth::guard('tenant')->user();

        if (!$tenant || !$tenant->room_id) {
            return back()->withErrors(['error' => 'ไม่พบข้อมูลห้องพักของคุณ กรุณาติดต่อเจ้าหน้าที่']);
        }

        try {
            $maintenance = Maintenance::create([
                'tenant_id' => $tenant->id,
                'room_id' => $tenant->room_id,
                'title' => $request->title,
                'details' => $request->details,
                'status' => 'pending',
                'repair_date' => now(),
            ]);

            try {
                NewMaintenanceRequest::dispatch($maintenance);
            } catch (\Throwable $broadcastErr) {
                // ไม่ให้ broadcast error กวน UX
                \Log::warning('Broadcast NewMaintenanceRequest failed: ' . $broadcastErr->getMessage());
            }

            return back()->with('success', 'ส่งเรื่องแจ้งซ่อมเรียบร้อยแล้ว เจ้าหน้าที่จะดำเนินการตรวจสอบโดยเร็วที่สุด');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

 // ---------------------------------------------

    // ใบแจ้งหนี้ & ยอดชำระ
    
    public function myInvoices()
    {
        $tenant = Auth::guard('tenant')->user();
        
        // โหลดข้อมูลห้องมารอไว้เลย เพื่อแก้ปัญหา "ห้อง -"
        if($tenant) {
             $tenant->load('room'); 
        }

        // 🌟 ดึงเฉพาะบิลที่สถานะ 'ค้างชำระ' หรือ 'ชำระบางส่วน' เรียงจากล่าสุดไปเก่า
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน','ชำระแล้ว']) // <--- เพิ่มบรรทัดนี้
            ->orderBy('billing_month', 'desc')
            ->paginate(10);

        $statusLabels = self::STATUS_LABELS;
        $unpaidStatuses = self::UNPAID_STATUSES;

        return view('tenant.invoices.index', compact('invoices', 'tenant', 'statusLabels', 'unpaidStatuses'));
    }

    /**
     * ดู detail การชำระบางส่วนของบิล
     * แสดง breakdown: ชำระไปแล้วต่อหมวดหมู่ + คงเหลือ
     */
    public function invoicePaymentDetail($invoiceId)
    {
        $tenant = Auth::guard('tenant')->user();
        
        // 🌟 1. ดึงข้อมูลจากฐานข้อมูล (ห้ามใส่ map ตรงนี้)
        $invoice = Invoice::with([
            'details',
            'payments' => function ($q) {
                $q->where('status', 'active')->orderBy('payment_date', 'desc');
            }
        ])->findOrFail($invoiceId);

        // ตรวจสอบสิทธิ์ — ผู้เช่าสามารถดูได้เฉพาะบิลของตัวเอง
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized');
        }
        
        // ดึง accounting transactions ที่เกี่ยวข้องกับบิลนี้
        $accountingTransactions = DB::table('accounting_transactions')
            ->join('payments', 'accounting_transactions.payment_id', '=', 'payments.id')
            ->where('payments.invoice_id', $invoice->id)
            ->where('accounting_transactions.status', 'active')
            ->select('accounting_transactions.*', 'payments.payment_date', 'payments.payment_method')
            ->orderBy('accounting_transactions.category_id')
            ->get();

        // จัดกลุ่ม accounting transactions ตามชื่อรายการ (detail name) เพื่อรวม breakdown
        $breakdown = [];
        foreach ($invoice->details as $detail) {
            $categoryName = $detail->name;
            $subtotal = $detail->subtotal;
            
            // ดึงยอดที่ชำระไปแล้วสำหรับรายการนี้
            $paidAmount = $accountingTransactions
                ->filter(function ($trans) use ($categoryName) {
                    return str_contains($trans->title, $categoryName);
                })
                ->sum('amount');

            $remaining = max(0, $subtotal - $paidAmount);

            $breakdown[] = (object) [
                'name' => $categoryName,
                'subtotal' => $subtotal,
                'paid' => $paidAmount,
                'remaining' => $remaining,
            ];
        }

        // ปรับแต่งวันที่เป็นภาษาไทย
        $thai_billing_month = \Carbon\Carbon::parse($invoice->billing_month)
            ->locale('th')->isoFormat('MMMM YYYY');
        $thai_due_date = \Carbon\Carbon::parse($invoice->due_date)
            ->locale('th')->isoFormat('D MMMM YYYY');

        // 🌟 2. ส่งข้อมูลกลับไปที่หน้าเว็บ (เราจะทำการใส่ URL รูปสลิป ตรงนี้ครับ!)
        return response()->json([
            'invoice' => [
                'invoice_number' => $invoice->invoice_number,
                'billing_month' => $thai_billing_month,
                'due_date' => $thai_due_date,
                'total_amount' => (float) $invoice->total_amount,
                'total_paid' => (float) $invoice->total_paid,
                'remaining_balance' => (float) $invoice->remaining_balance,
                'status' => $invoice->status,
                'status_label' => self::STATUS_LABELS[$invoice->status] ?? $invoice->status,
            ],
            'breakdown' => $breakdown,
            'payments' => $invoice->payments->map(function ($p) {
                // เช็คพาธรูปภาพ เผื่อมีคำว่า public ติดมา
                $cleanPath = str_replace('public/', '', $p->slip_image); 

                return [
                    'date' => \Carbon\Carbon::parse($p->payment_date)->locale('th')->isoFormat('D MMMM YYYY'),
                    'amount' => (float) $p->amount_paid,
                    'method' => $p->payment_method,
                    // 🌟 เพิ่ม URL สลิปตรงนี้ถูกต้องแล้วครับ
                    'slip_url' => $p->slip_image ? asset('storage/' . $cleanPath) : null, 
                ];
            }),
        ]);
    }

    // พิม ใบเสร็จรับเงิน PDF
    public function printInvoice($id)
    {
        $tenantUser = Auth::guard('tenant')->user();

        // 1. ดึงข้อมูลบิล และเช็คด้วยว่าบิลนี้เป็นของคนที่ Login อยู่จริงไหม (Security Check)
        $invoice = Invoice::with([
            'details', 
            'tenant', 
            'tenant.room', 
            'details.meterReading'
        ])
        ->where('tenant_id', $tenantUser->id)
        ->findOrFail($id);

        // 2. จัดการวันที่ภาษาไทย (ใช้ Logic เดียวกับ Admin)
        $invoice->thai_billing_month = $this->toThaiDate($invoice->billing_month, false);
        $invoice->thai_issue_date = $this->toThaiDate($invoice->issue_date);
        $invoice->thai_due_date = $this->toThaiDate($invoice->due_date);

        $firstReading = $invoice->details->whereNotNull('meter_reading_id')->first();
        $invoice->thai_reading_date = ($firstReading && $firstReading->meterReading)
            ? $this->toThaiDate($firstReading->meterReading->reading_date) : '-';

        // 3. แปลงยอดรวมเป็นตัวอักษรไทย
        $invoice->total_amount_thai = $this->bahtText($invoice->total_amount);
        
        // 4. ดึงข้อมูลอพาร์ทเม้นท์
        $apartment = DB::table('apartment')->first();

        // // 5. โหลด View ตัวเดียวกับที่ Admin ใช้ (เพื่อความเหมือนกัน 100%)

        $pdf = Pdf::loadView('admin.invoices.pdf.print_pdf_invoiceDetails', compact('invoice', 'apartment'))
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('invoice_' . $invoice->invoice_number . '.pdf');
        // return view('admin.invoices.pdf.print_pdf_invoiceDetails', compact('invoice', 'apartment'));
        
    }

    // ข้อมูลส่วนตัว & ตั้งค่า
    public function profile()
    {
        $tenant = Auth::guard('tenant')->user();
        $tenant->load('room.roomPrice.roomType');
        $tenant->thai_start_date = $this->toThaiDate($tenant->start_date);
        $tenant->thai_end_date = $tenant->end_date ? $this->toThaiDate($tenant->end_date) : '-';
        $tenant->thai_id_card_issue_date = $tenant->id_card_issue_date ? $this->toThaiDate($tenant->id_card_issue_date) : '-';
        $tenant->thai_id_card_expiry_date = $tenant->id_card_expiry_date ? $this->toThaiDate($tenant->id_card_expiry_date) : '-';
        return view('tenant.profile', compact('tenant'));
    }
}