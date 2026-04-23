<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Events\PaymentRecorded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; // เรียกใช้งาน Auth Facade

use App\Models\TenantExpense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AccountingTransaction;

// นำเข้า Trait
use App\Traits\FormatHelper;

// line
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\Log;  
class PaymentController extends Controller
{
    use FormatHelper; // เรียกใช้ Trait ใน class
    // จัดการ payment การชำระค่าเช่าของ admin ให้ admin จัดการจ่ายค่าเช่าลงระบบ

    public function pendingInvoicesShow(Request $request)
    {
        // แปลงเดือนที่ส่งมาจาก Filter พ.ศ. -> ค.ศ.
        $request->merge(['filter_month' => $this->convertThaiYearToAD($request->filter_month)]);
        $searchRoom = $request->input('search_room');
        $filterMonth = $request->input('filter_month');
        $filterStatus = $request->input('filter_status'); // 🌟 เพิ่มตัวกรองสถานะ

        $query = Invoice::with(['tenant.room', 'tenant', 'payments', 'details']);

        // กรองสถานะบิล
        if ($filterStatus) {
            $query->where('invoices.status', $filterStatus);
        } else {
            $query->whereIn('invoices.status', ['ค้างชำระ', 'ชำระบางส่วน', 'รอตรวจสอบ']);
        }

        if ($searchRoom) {
            $query->whereHas('tenant.room', function ($q) use ($searchRoom) {
                $q->where('room_number', 'like', "%{$searchRoom}%");
            });
        }

        if ($filterMonth) {
            $query->where('billing_month', $filterMonth);
        }

        $pendingInvoices = $query->join('rooms', 'invoices.room_id', '=', 'rooms.id')
            ->select('invoices.*')
            ->orderBy('rooms.room_number', 'asc')
            ->get();

        foreach ($pendingInvoices as $inv) {
            $inv->thai_due_date = $this->toThaiDate($inv->due_date);
        }

        $availableMonths = Invoice::whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน', 'รอตรวจสอบ'])
            ->select('billing_month')->distinct()->orderBy('billing_month', 'desc')->get();

        foreach ($availableMonths as $m) {
            $m->thai_billing_month = $this->toThaiDate($m->billing_month, false);
        }

        return view('admin.payments.pendingInvoices', compact('pendingInvoices', 'searchRoom', 'filterMonth', 'filterStatus', 'availableMonths'));
    }

    public function insertPayment_and_AccountingTransaction_of_Tenant(Request $request)
    {
        // 🚩 แปลงปี พ.ศ. เป็น ค.ศ. สำหรับวันที่จ่ายเงินก่อนเริ่ม Validation
        $request->merge([
            'payment_date' => $this->convertThaiYearToAD($request->payment_date),
        ]);
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount_paid' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'slip_image' => 'nullable|image|max:2048',
            'note' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $invoice = Invoice::with(['details', 'tenant.room', 'payments'])->findOrFail($request->invoice_id);

            $paidAmount = (float) $request->amount_paid;
            $currentRemaining = (float) $invoice->remaining_balance;

            // 🌟 เช็คว่าเป็นการ "อนุมัติ" ข้อมูลเดิมที่ค้างอยู่หรือไม่
            $pending_payment_id = $request->input('pending_payment_id');
            
            $path = null;
            if ($request->hasFile('slip_image')) {
                $path = $request->file('slip_image')->store('slips', 'public');
            }

            if ($pending_payment_id) {
                // อัปเดตรายการเดิม
                $payment = Payment::findOrFail($pending_payment_id);
                
                // ลบสลิปเก่าถ้าอัปโหลดใหม่
                if ($path && $payment->slip_image && Storage::disk('public')->exists($payment->slip_image)) {
                    Storage::disk('public')->delete($payment->slip_image);
                }

                $payment->update([
                    'user_id' => Auth::id(), // บันทึกว่าแอดมินคนไหนเป็นคนกดรับเงิน
                    'amount_paid' => $paidAmount,
                    'payment_date' => $request->payment_date,
                    'payment_method' => $request->payment_method,
                    'slip_image' => $path ?: $payment->slip_image, // ใช้สลิปใหม่ หรือสลิปเดิมที่ผู้เช่าส่งมา
                    'note' => $request->note ?: $payment->note,
                    'status' => 'active' // เปลี่ยนสถานะเป็นรับเงินแล้ว
                ]);
            } else {
                // สร้างรายการใหม่ (กรณีแอดมินรับเงินเอง)
                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => Auth::id(),
                    'amount_paid' => $paidAmount,
                    'payment_date' => $request->payment_date,
                    'payment_method' => $request->payment_method,
                    'slip_image' => $path,
                    'note' => $request->note,
                    'status' => 'active'
                ]);
            }

            // 2. กระจายยอดเงินลงหมวดบัญชี
            $paidRemaining = $paidAmount;
            foreach ($invoice->details as $detail) {
                if ($paidRemaining <= 0) break;

                $expenseSetting = TenantExpense::find($detail->tenant_expense_id);
                $categoryId = $expenseSetting->accounting_category_id ?? 1;

                $alreadyPaidForItem = DB::table('accounting_transactions')
                    ->join('payments', 'accounting_transactions.payment_id', '=', 'payments.id')
                    ->where('payments.invoice_id', $invoice->id)
                    ->where('accounting_transactions.category_id', $categoryId)
                    ->sum('accounting_transactions.amount');

                $itemBalance = $detail->subtotal - $alreadyPaidForItem;

                if ($itemBalance > 0) {
                    $allocation = min($paidRemaining, $itemBalance);

                    AccountingTransaction::create([
                        'category_id' => $categoryId,
                        'payment_id' => $payment->id,
                        'tenant_id' => $invoice->tenant_id,
                        'user_id' => Auth::id(),
                        'title' => $detail->name . " (ห้อง " . $invoice->tenant->room->room_number . ")",
                        'amount' => $allocation,
                        'entry_date' => $request->payment_date,
                        'description' => "ชำระเงินช่องทาง: " . $payment->payment_method,
                        'status' => 'active',
                    ]);
                    $paidRemaining -= $allocation;
                }
            }

            // 3. อัปเดตสถานะบิล
            if ($paidAmount >= $currentRemaining) {
                $invoice->status = 'ชำระแล้ว';
                $newStatus = 'ชำระแล้ว';
            } else {
                $invoice->status = 'ชำระบางส่วน';
                $newStatus = 'ชำระบางส่วน';
            }
            $invoice->save();

            DB::commit();
            
            // 4. ส่งแจ้งเตือน LINE 
            $newRemainingBalance = max(0, $currentRemaining - $paidAmount);
            $this->sendPaymentLineNotification($invoice, $paidAmount, $newStatus, $newRemainingBalance);
            try { PaymentRecorded::dispatch($invoice->fresh(), $paidAmount); } catch (\Throwable $e) {}

            return back()->with('success', 'ตรวจสอบและบันทึกการรับชำระเงินสำเร็จ')->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()])->withInput();
        }
    }

    // 🌟 ฟังก์ชันปฏิเสธสลิป พร้อมส่ง LINE แจ้งเตือน
    
    public function rejectPendingPayment(Request $request, $id) // 🌟 เพิ่ม Request $request เข้ามาในวงเล็บ
    {
        DB::beginTransaction();
        try {
            $payment = Payment::findOrFail($id);
            
            $payment->user_id = Auth::id();
            // 🌟 1. เปลี่ยนสถานะเป็นโมฆะ และ บันทึกหมายเหตุที่แอดมินพิมพ์ลงไปด้วย
            $payment->status = 'ปฏิเสธสลิป';
            if ($request->filled('note')) {
                $payment->note = 'ถูกปฏิเสธ: ' . $request->note;
            }
            $payment->save();

            $invoice = Invoice::with('tenant')->findOrFail($payment->invoice_id);
            
            // 2. คืนสถานะบิลตามยอดที่เหลือจริง
            $totalPaidActive = $invoice->payments()->where('status', 'active')->sum('amount_paid');
            if ($totalPaidActive <= 0) {
                $invoice->update(['status' => 'ค้างชำระ']);
            } else {
                $invoice->update(['status' => 'ชำระบางส่วน']);
            }

            // 3. ส่ง LINE แจ้งเตือนผู้เช่า พร้อมบอก "หมายเหตุ/สาเหตุ" ที่แอดมินพิมพ์
            if ($invoice->tenant && $invoice->tenant->line_id) {
                $channelAccessToken = env('LINE_BOT_CHANNEL_ACCESS_TOKEN');
                $messageText = "❌ แจ้งเตือน: สลิปการชำระเงินรอบเดือน " . $this->toThaiDate($invoice->billing_month, false) . " ไม่ถูกต้อง\n\n";
                
                // 🌟 แทรกหมายเหตุไปในข้อความ LINE
                if ($request->filled('note')) {
                    $messageText .= "สาเหตุ: " . $request->note . "\n\n";
                } else {
                    $messageText .= "สาเหตุ: ข้อมูลไม่ชัดเจนหรือยอดเงินไม่ตรง\n\n";
                }
                
                $messageText .= "กรุณาตรวจสอบและแนบสลิปใหม่อีกครั้งผ่านเมนู 'แนบหลักฐานการชำระเงิน' ใน LINE ครับ 🙏";

                Http::withToken($channelAccessToken)->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $invoice->tenant->line_id,
                    'messages' => [['type' => 'text', 'text' => $messageText]]
                ]);
            }

            DB::commit();
            return back()->with('success', 'ปฏิเสธสลิป บันทึกหมายเหตุ และส่งแจ้งเตือนให้ผู้เช่าเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    public function paymentHistory(Request $request)
    {
        // แปลงค่าจากตัวกรองทั้งหมด
        $request->merge([
            'filter_month' => $this->convertThaiYearToAD($request->filter_month),
            'filter_date'  => $this->convertThaiYearToAD($request->filter_date),
        ]);
        // รับค่าตัวกรอง
        $filterRoom = $request->input('filter_room');
        $filterMethod = $request->input('filter_method');
        $filterMonth = $request->input('filter_month');
        $filterPayer = $request->input('filter_payer');
        $filterReceiver = $request->input('filter_receiver');
        $filterStatus = $request->input('filter_status');
        $filterDate = $request->input('filter_date');
        $filterMinAmount = $request->input('filter_min_amount');

        $query = Payment::with(['invoice.tenant.room', 'invoice.admin', 'admin', 'accounting_transactions.tenant.room']);

        // 🌟 ซ่อนสถานะ "รอตรวจสอบ" ออกจากหน้านี้เสมอ (เว้นแต่จะเจาะจงค้นหา)
        if ($filterStatus) {
            $query->where('status', $filterStatus);
        } else {
            $query->where('status', '!=', 'รอตรวจสอบ'); // ไม่เอา รอตรวจสอบ
        }

        // กรองตามเลขห้อง
        if ($filterRoom) {
            $query->where(function ($q) use ($filterRoom) {
                $q->whereHas('invoice.tenant.room', fn($subQ) => $subQ->where('room_number', 'like', "%{$filterRoom}%"))
                    ->orWhereHas('accounting_transactions.tenant.room', fn($subQ) => $subQ->where('room_number', 'like', "%{$filterRoom}%"));
            });
        }

        // ค้นหาชื่อ-นามสกุล ผู้ชำระ
        if ($filterPayer) {
            $query->where(function ($q) use ($filterPayer) {
                $q->whereHas('invoice.tenant', function ($subQ) use ($filterPayer) {
                    $subQ->where('first_name', 'like', "%{$filterPayer}%")
                        ->orWhere('last_name', 'like', "%{$filterPayer}%");
                })->orWhereHas('accounting_transactions.tenant', function ($subQ) use ($filterPayer) {
                    $subQ->where('first_name', 'like', "%{$filterPayer}%")
                        ->orWhere('last_name', 'like', "%{$filterPayer}%");
                });
            });
        }

        if ($filterReceiver) {
            $query->whereHas('admin', function ($q) use ($filterReceiver) {
                $q->where('firstname', 'like', "%{$filterReceiver}%")
                    ->orWhere('lastname', 'like', "%{$filterReceiver}%");
            });
        }

        if ($filterMethod) {
            $query->where('payment_method', $filterMethod);
        }

        if ($filterMonth) {
            $query->whereHas('invoice', fn($q) => $q->where('billing_month', $filterMonth));
        }

        if ($filterDate) {
            $query->whereDate('payment_date', $filterDate);
        }

        if ($filterMinAmount) {
            $query->where('amount_paid', '>=', $filterMinAmount);
        }

        $summaryQuery = clone $query;
        $summaryAll = $summaryQuery->get();
        $summary = [
            'total_count' => $summaryAll->count(),
            'total_amount' => $summaryAll->where('status', 'active')->sum('amount_paid'),
            'cash_count' => $summaryAll->where('status', 'active')->where('payment_method', 'เงินสด')->count(),
            'cash_amount' => $summaryAll->where('status', 'active')->where('payment_method', 'เงินสด')->sum('amount_paid'),
            'transfer_count' => $summaryAll->where('status', 'active')->where('payment_method', 'โอนผ่านธนาคาร')->count(),
            'transfer_amount' => $summaryAll->where('status', 'active')->where('payment_method', 'โอนผ่านธนาคาร')->sum('amount_paid'),
            'void_count' => $summaryAll->where('status', 'void')->count(),
            'void_amount' => $summaryAll->where('status', 'void')->sum('amount_paid'),
            
            // 🌟 เพิ่มสรุปยอด "ปฏิเสธสลิป"
            'reject_count' => $summaryAll->where('status', 'ปฏิเสธสลิป')->count(),
            'reject_amount' => $summaryAll->where('status', 'ปฏิเสธสลิป')->sum('amount_paid'),
        ];

        $history = $query->orderBy('payment_date', 'desc')->orderBy('created_at', 'desc')->paginate(20);
        $displayTitle = $filterMonth ? "ประวัติการชำระเงินรอบเดือน " . $this->toThaiDate($filterMonth, false) : "ประวัติการชำระเงินทั้งหมด";

        foreach ($history as $pay) {
            $pay->thai_payment_date = $this->toThaiDate($pay->payment_date);

            if ($pay->invoice) {
                $pay->display_room = $pay->invoice->tenant->room->room_number ?? '-';
                $pay->display_tenant = trim(($pay->invoice->tenant->first_name ?? '') . ' ' . ($pay->invoice->tenant->last_name ?? ''));
            } else {
                $transaction = $pay->accounting_transactions->first();
                $pay->display_room = $transaction->tenant->room->room_number ?? '-';
                $pay->display_tenant = trim(($transaction->tenant->first_name ?? '') . ' ' . ($transaction->tenant->last_name ?? ''));
            }

            if (empty($pay->display_tenant))
                $pay->display_tenant = '-';
        }

        $availableMonths = Invoice::whereHas('payments')->select('billing_month')->distinct()->orderBy('billing_month', 'desc')->get();
        foreach ($availableMonths as $m) {
            $m->thai_billing_month = $this->toThaiDate($m->billing_month, false);
        }
        return view('admin.payments.history', compact('history', 'availableMonths', 'filterRoom', 'filterMethod', 'filterPayer', 'filterReceiver', 'filterMonth', 'filterStatus', 'displayTitle', 'summary', 'filterDate', 'filterMinAmount'));
    }

    // เพิ่มฟังก์ชันดึงรายละเอียดการชำระเงินผ่าน AJAX
    public function getPaymentDetail($id)
    {
        $pay = Payment::with(['invoice.tenant.room', 'invoice.admin', 'admin', 'invoice.details', 'accounting_transactions.tenant.room'])->findOrFail($id);

        $breakdown = [];
        $billing_month = '-';
        $invoice_no = '-';
        $invoice_total = 0;
        $invoice_paid = 0;
        $invoice_remain = 0;

        //  ถ้ามี Invoice (จ่ายรายเดือน)
        if ($pay->invoice) {
            $invoice = $pay->invoice;
            $billing_month = $this->toThaiDate($invoice->billing_month, false);
            $invoice_no = $invoice->invoice_number;
            $invoice_total = $invoice->total_amount;
            $invoice_paid = $invoice->total_paid;
            $invoice_remain = $invoice->remaining_balance;

            $display_room = $invoice->tenant->room->room_number ?? '-';
            $display_tenant = trim(($invoice->tenant->first_name ?? '') . ' ' . ($invoice->tenant->last_name ?? ''));

            $accountingTransactions = DB::table('accounting_transactions')
                ->join('payments', 'accounting_transactions.payment_id', '=', 'payments.id')
                ->where('payments.invoice_id', $invoice->id)
                ->where('accounting_transactions.status', 'active')
                ->select('accounting_transactions.*')
                ->get();

            foreach ($invoice->details as $detail) {
                $categoryName = $detail->name;
                $subtotal = $detail->subtotal;

                $paidAmount = $accountingTransactions
                    ->filter(fn($trans) => str_contains($trans->title, $categoryName))
                    ->sum('amount');

                $breakdown[] = [
                    'name' => $categoryName,
                    'subtotal' => $subtotal,
                    'paid' => $paidAmount,
                    'remaining' => max(0, $subtotal - $paidAmount),
                ];
            }
        } else {
            //  ถ้าไม่มี Invoice (จ่ายมัดจำแรกเข้า)
            $transaction = $pay->accounting_transactions->first();
            $display_room = $transaction->tenant->room->room_number ?? '-';
            $display_tenant = trim(($transaction->tenant->first_name ?? '') . ' ' . ($transaction->tenant->last_name ?? ''));

            $breakdown[] = [
                'name' => 'เงินมัดจำแรกเข้า',
                'subtotal' => $pay->amount_paid,
                'paid' => $pay->amount_paid,
                'remaining' => 0,
            ];
            $invoice_total = $pay->amount_paid;
            $invoice_paid = $pay->amount_paid;
        }

        return response()->json([
            'room' => $display_room,
            'date' => $this->toThaiDate($pay->payment_date),
            'time' => $pay->created_at->format('H:i') . ' น.',
            'amount' => number_format($pay->amount_paid, 2),
            'method' => $pay->payment_method,
            'tenant' => $display_tenant ?: '-',
            'receiver' => trim(($pay->admin->firstname ?? 'System') . ' ' . ($pay->admin->lastname ?? '')),
            'note' => $pay->note ?? '-',
            'slip' => $pay->slip_image ? asset('storage/' . $pay->slip_image) : null,
            'invoice_no' => $invoice_no,
            'billing_month' => $billing_month,
            'invoice_total' => number_format($invoice_total, 2),
            'invoice_paid' => number_format($invoice_paid, 2),
            'invoice_remain' => number_format($invoice_remain, 2),
            'breakdown' => $breakdown
        ]);
    }

    public function updatePayment(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $pay = Payment::findOrFail($id);

            $pay->payment_method = $request->payment_method;
            $pay->note = $request->note;

            if ($request->hasFile('slip_image')) {
                // ลบรูปเก่าถ้ามี และบันทึกรูปใหม่
                if ($pay->slip_image)
                    \Storage::disk('public')->delete($pay->slip_image);
                $pay->slip_image = $request->file('slip_image')->store('slips', 'public');
            }
            $pay->save();
            DB::commit();
            return back()->with('success', 'อัปเดตข้อมูลการชำระเงินเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function voidPayment($id)
    {
        DB::beginTransaction();
        try {
            $payment = Payment::with('invoice.payments')->findOrFail($id);

            if ($payment->status === 'void') {
                return back()->with('error', 'รายการนี้ถูกยกเลิกไปก่อนหน้านี้แล้ว');
            }

            // 1. เปลี่ยนสถานะ Payment เป็น void
            $payment->status = 'void';
            $payment->save();

            // 2. ยกเลิกรายการบัญชีที่เกี่ยวข้อง
            AccountingTransaction::where('payment_id', $payment->id)->update(['status' => 'void']);

            // 3. ปรับปรุงสถานะ Invoice (ทำเฉพาะ Payment ที่มีการผูกกับ Invoice เท่านั้น)
            if ($payment->invoice_id) {
                $invoice = $payment->invoice;
                $totalPaidActive = $invoice->payments()->where('status', 'active')->sum('amount_paid');

                if ($totalPaidActive <= 0) {
                    $invoice->status = 'ค้างชำระ';
                } elseif ($totalPaidActive < $invoice->total_amount) {
                    $invoice->status = 'ชำระบางส่วน';
                } else {
                    $invoice->status = 'ชำระแล้ว';
                }
                $invoice->save();
            }

            DB::commit();
            return back()->with('success', 'ยกเลิกรายการชำระเงินเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // =========================================================
    // 🌟 ฟังก์ชันตัวช่วย (ห้ามลืมก๊อปไปวางล่างสุดของ Class)
    // =========================================================
    private function convertThaiYearToAD($dateString)
    {
        if (!$dateString) return null;

        try {
            // แยกส่วนประกอบ (รองรับทั้ง YYYY-MM-DD และ YYYY-MM)
            $parts = explode('-', $dateString);
            if (count($parts) >= 2) {
                $year = (int) $parts[0];
                // ถ้าปี > 2400 แสดงว่าเป็น พ.ศ.
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

    // =========================================================================
    //  ฟังก์ชัน Private สำหรับส่ง LINE แจ้งเตือนการรับชำระเงิน
    // =========================================================================
    private function sendPaymentLineNotification($invoice, $paidAmount, $paymentStatus, $remainingBalance)
    {
        try {
            $tenant = $invoice->tenant;

            // ถ้าไม่มีผู้เช่า หรือผู้เช่ายังไม่ได้ผูก LINE ให้ข้ามไปเลย
            if (!$tenant || empty($tenant->line_id)) {
                return false; 
            }

            $channelAccessToken = env('LINE_BOT_CHANNEL_ACCESS_TOKEN');
            $roomNumber = $tenant->room->room_number ?? '-';
            $billingMonth = $this->toThaiDate($invoice->billing_month, false);

            // จัดเตรียมข้อความ
            $messageText = "✅ ได้รับยอดชำระเงินเรียบร้อยแล้ว\n\n";
            $messageText .= "🏢 ห้อง: {$roomNumber}\n";
            $messageText .= "📅 บิลรอบเดือน: {$billingMonth}\n";
            $messageText .= "💰 ยอดชำระครั้งนี้: " . number_format($paidAmount, 2) . " บาท\n";

            // แจ้งสถานะบิล
            if ($paymentStatus === 'ชำระแล้ว') {
                $messageText .= "🎉 สถานะบิล: ชำระครบถ้วน\n";
            } else {
                $messageText .= "⚠️ สถานะบิล: ชำระบางส่วน\n";
                $messageText .= "❗ ยอดคงเหลือที่ต้องชำระ: " . number_format($remainingBalance, 2) . " บาท\n";
            }

            $messageText .= "\nขอบคุณที่ชำระค่าเช่าตรงเวลาครับ 🙏";

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
            // บันทึก Error ลง Log ไว้ ไม่ให้ระบบพัง
            Log::error('Line Notify Error (Payment): ' . $e->getMessage());
            return false;
        }
    }
}
