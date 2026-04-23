<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Payment;

// นำเข้า Trait
use App\Traits\FormatHelper;

// ระบบส่งแจ้งเตือนให้ admnin ทั้ง 1 ผู้เช่าลงทะเบียนออนไล 2 ผู้เช่าแนบหลักฐานการชำระ
use App\Events\NewPaymentRequest; // ผู้เช่าแนบหลักฐานการชำระ
use App\Events\NewTenantRequest; // ผู้เช่าลงทะเบียนออนไลน์
class LineLoginController extends Controller
{
    use FormatHelper; // เรียกใช้ Trait ใน Class
    // ==========================================
    // ส่วนที่ 1: การเข้าสู่ระบบ LINE (แยกเส้นทางกันเด็ดขาด)
    // ==========================================

    public function redirectToLineLink()
    {
        // บังคับให้วิ่งไปที่เส้นทางของ "การผูกบัญชี"
        return Socialite::driver('line')->redirectUrl(url('/auth/line/callback/link'))->redirect();
    }

    public function redirectToLineRegister()
    {
        // บังคับให้วิ่งไปที่เส้นทางของ "การลงทะเบียน"
        return Socialite::driver('line')->redirectUrl(url('/auth/line/callback/register'))->redirect();
    }

    // handel รับการ Callback กลับมาจาก LINE หลังจากที่ผู้ใช้ล็อกอินผ่าน LINE แล้ว
    // LINE ตรวจสอบระบบรักษาความปลอดภัยตัวเอง แล้วพบว่า URL นี้ "ไม่ได้ถูกลงทะเบียนไว้" ในหน้าตั้งค่า Callback URL ของ LINE Developers
    public function handleLineCallbackLink()
    {
        return $this->processLineCallback('link');
    }

    public function handleLineCallbackRegister()
    {
        return $this->processLineCallback('register');
    }
    public function handleLineCallbackPayment()
    {
        return $this->processLineCallback('payment');
    }

    // ฟังก์ชันประมวลผลหลัก (ทำหน้าที่รับข้อมูลจาก LINE)

    private function processLineCallback($action)
    {
        try {
            // 🌟 ต้องบอกระบบให้ใช้ URL ให้ตรงกับที่ส่งไปตอนแรกเป๊ะๆ
            $redirectUrl = url('/auth/line/callback/' . $action);
            $lineUser = Socialite::driver('line')->redirectUrl($redirectUrl)->user();

            $lineId = $lineUser->getId();
            $avatar = $lineUser->getAvatar();
            $name = $lineUser->getName();

            // ค้นหาผู้เช่า
            $tenant = Tenant::where('line_id', $lineId)->whereIn('status', ['กำลังใช้งาน', 'รออนุมัติ'])->first();

            // 🌟 อัปเดตรูปโปรไฟล์ไลน์เสมอถ้าเจอผู้เช่า
            if ($tenant) {
                $tenant->update(['line_avatar' => $avatar]);
            }

            // 🌟 สร้าง Session เก็บข้อมูลผู้ใช้ไว้ใช้ในหน้าถัดไป
            session([
                'temp_line_id' => $lineId, 
                'temp_line_avatar' => $avatar,
                'temp_line_name' => $name
            ]);

            // 🌟 แยก Action ให้ชัดเจน!
            if ($action === 'payment') {
                // ถ้าตั้งใจมากดจ่ายเงิน ให้ส่งไปหน้าจ่ายเงินทันที (เดี๋ยวหน้าจ่ายเงินจะเช็คสถานะต่อเอง)
                return redirect()->route('line.payment.form');
            } 
            elseif ($action === 'register') {
                // ถ้าตั้งใจมาลงทะเบียนใหม่
                if ($tenant) {
                    return view('auth.line_error', ['message' => 'คุณมีบัญชีในระบบอยู่แล้ว ไม่สามารถลงทะเบียนใหม่ได้ครับ']);
                }
                return redirect()->route('line.register.form');
            } 
            else {
                // ถ้ามาจากการกด "ผูกบัญชี (link)"
                if ($tenant) {
                    return view('auth.line_success', [
                        'message' => 'บัญชี LINE ของคุณผูกกับห้อง ' . ($tenant->room->room_number ?? '') . ' เรียบร้อยแล้วครับ'
                    ]);
                }
                return redirect()->route('line.link.form');
            }

        } catch (\Exception $e) {
            return "เกิดข้อผิดพลาดในการเชื่อมต่อ LINE กรุณาลองใหม่อีกครั้ง (" . $e->getMessage() . ")";
        }
    }

    // ==========================================
    // ส่วนที่ 2: ระบบผูกบัญชี (ใช้ของเดิม)
    // ==========================================
    public function linkAccountForm()
    {
        if (!session('temp_line_id')) return redirect()->route('line.login');
        return view('auth.line_link', [
            'lineId' => session('temp_line_id'),
            'lineAvatar' => session('temp_line_avatar'),
            'lineName' => session('temp_line_name')
        ]);
    }

    public function linkAccountSave(Request $request)
    {
        $request->validate([
            'id_card' => 'required|digits:13',
            'phone' => 'required|digits:10',
            'line_id' => 'required' 
        ]);

        $tenant = Tenant::where('id_card', $request->id_card)->where('phone', $request->phone)->where('status', 'กำลังใช้งาน')->first();

        if ($tenant) {
            $tenant->update([
                'line_id' => $request->line_id,
                'line_avatar' => $request->line_avatar,
            ]);
            session()->forget(['temp_line_id', 'temp_line_avatar', 'temp_line_name']);
            return view('auth.line_success', ['message' => 'ผูกบัญชีสำเร็จ! สามารถใช้งานบอทได้เลย 🎉']);
        }
        return back()->with('error', 'ไม่พบข้อมูลผู้เช่า หรือข้อมูลไม่ตรงกัน กรุณาลองใหม่');
    }

    // ==========================================
    // ส่วนที่ 3: ระบบลงทะเบียนผู้เช่าใหม่
    // ==========================================
    public function registerForm()
    {
        if (!session('temp_line_id')) return redirect()->route('line.register');
        return view('auth.line_register', [
            'lineId' => session('temp_line_id'),
            'lineAvatar' => session('temp_line_avatar'),
            'lineName' => session('temp_line_name')
        ]);
    }

    public function registerSave(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'age' => 'required|numeric|min:1', 
            'id_card' => 'nullable|string|max:13',
            'phone' => 'required|digits:10',
            'deposit_amount' => 'required|numeric',
            
            // 🌟 แก้ไขตรงนี้: เปลี่ยนจาก required เป็น nullable
            // เพื่อรองรับกรณีที่ผู้เช่าเลือกชำระด้วยเงินสด จะได้ไม่ต้องบังคับแนบรูป
            'deposit_slip' => 'nullable|image|mimes:jpeg,png,jpg|max:4096', 
            'line_id' => 'required',
            'pdpa_accepted' => 'required|accepted'
        ],[
            'pdpa_accepted.accepted' => 'คุณต้องยอมรับนโยบายคุ้มครองข้อมูลส่วนบุคคล (PDPA) ก่อนลงทะเบียน'
        ]);

        // จัดการอัปโหลดรูปภาพสลิปมัดจำ
        $slipPath = null;
        if ($request->hasFile('deposit_slip')) {
            $file = $request->file('deposit_slip');
            $filename = time() . '_' . $file->getClientOriginalName();
            $slipPath = $file->storeAs('slips', $filename, 'public'); 
        }

        // สร้างผู้เช่าคนใหม่
        $tenant = Tenant::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'age' => $request->age,
            'id_card' => $request->id_card,
            'id_card_issue_date' => $request->id_card_issue_date, 
            'id_card_expiry_date' => $request->id_card_expiry_date, 
            'id_card_issue_place' => $request->id_card_issue_place, 
            'id_card_issue_province' => $request->id_card_issue_province, 
            'workplace' => $request->workplace, 
            'phone' => $request->phone,
            
            'address_no' => $request->address_no,
            'moo' => $request->moo,
            'alley' => $request->alley, 
            'street' => $request->street, 
            'province' => $request->province,
            'district' => $request->district,
            'sub_district' => $request->sub_district,
            'postal_code' => $request->postal_code,
            'resident_count' => $request->resident_count,
            
            'has_parking' => $request->has('has_parking') ? 1 : 0,
            'deposit_amount' => $request->deposit_amount,
            'deposit_payment_method' => $request->deposit_payment_method,
            'deposit_slip' => $slipPath, 

            'line_id' => $request->line_id,
            'line_avatar' => $request->line_avatar,
            'status' => 'รออนุมัติ',
        ]);
        // สั่งยิงแจ้งเตือนไปหาแอดมินทันที!
        NewTenantRequest::dispatch($tenant);
        session()->forget(['temp_line_id', 'temp_line_avatar', 'temp_line_name']);

        return view('auth.line_success', [
            'message' => 'จองห้องพักสำเร็จ! แอดมินได้รับข้อมูลและสลิปของคุณแล้ว กรุณารอติดต่อกลับครับ 🏢'
        ]);
    }

    // ==========================================
    // ส่วนที่ 4: ระบบแจ้งชำระเงิน (สำหรับผู้เช่าผ่าน LINE)
    // ==========================================

    public function paymentForm(Request $request)
    {
        // 1. รับค่า Line ID จากการเชื่อมต่อ (สมมติว่าคุณส่งพารามิเตอร์มา หรือดึงผ่าน Socialite อีกรอบ)
        // สำหรับ LIFF มักจะส่ง parameter line_id มากับ URL เลย เช่น ?line_id=U12345...
        $lineId = $request->query('line_id'); 

        // ถ้าระบบยังไม่ได้รับ Line ID ให้บังคับล็อกอินผ่านไลน์เพื่อดึงค่าก่อน
        if (!$lineId && !session('temp_line_id')) {
            return Socialite::driver('line')->redirectUrl(url('/auth/line/callback/payment'))->redirect();
        }
        
        // ถ้าล็อกอินเสร็จแล้ว จะมี session เก็บไว้ (สมมติว่าคุณรับ Callback กลับมาที่ route นี้)
        if (!$lineId && session('temp_line_id')) {
            $lineId = session('temp_line_id');
        }

        // 2. ตรวจสอบสถานะผู้เช่า
        $tenant = Tenant::with('room')->where('line_id', $lineId)->latest()->first();

        if (!$tenant) {
            return view('auth.line_error', ['message' => 'คุณยังไม่ได้ผูกบัญชีเข้ากับระบบ กรุณาผูกบัญชีก่อนทำรายการครับ']);
        }
        if ($tenant->status === 'รออนุมัติ') {
            return view('auth.line_error', ['message' => 'บัญชีของคุณกำลังรอการอนุมัติ ยังไม่สามารถชำระเงินได้ครับ']);
        }
        if ($tenant->status === 'สิ้นสุดสัญญา') {
            return view('auth.line_error', ['message' => 'สัญญาเช่าของคุณสิ้นสุดแล้ว ไม่สามารถทำรายการได้ครับ']);
        }

        // 3. หาบิลที่ค้างชำระล่าสุด (สถานะ 'ค้างชำระ' หรือ 'ชำระบางส่วน')
        $invoice = Invoice::where('tenant_id', $tenant->id)
                                      ->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน'])
                                      ->orderBy('billing_month', 'asc') // เอาบิลเก่าสุดที่ค้างขึ้นมาก่อน
                                      ->first();

        // เพิ่มส่วนนี้: แปลงวันที่ผ่าน FormatHelper
        if ($invoice) {
            // สมมติว่าใน FormatHelper ของคุณมีฟังก์ชัน toThaiDate()
            $invoice->thai_billing_month = $this->toThaiDate($invoice->billing_month,false,false);
            $invoice->thai_due_date = $this->toThaiDate($invoice->due_date);
        }
        // 4. ส่งข้อมูลไปแสดงที่หน้าเว็บ
        return view('auth.line_payment', compact('tenant', 'invoice'));
    }

    public function paymentSave(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'invoice_id' => 'required|exists:invoices,id',
            'amount_paid' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'slip_image' => 'required|image|mimes:jpeg,png,jpg|max:4096', // บังคับแนบสลิป
        ]);

        $tenant = Tenant::findOrFail($request->tenant_id);
        $invoice = Invoice::findOrFail($request->invoice_id);

        // 1. จัดการอัปโหลดไฟล์สลิป
        $slipPath = null;
        if ($request->hasFile('slip_image')) {
            $file = $request->file('slip_image');
            $filename = time() . '_inv' . $invoice->id . '_slip.' . $file->getClientOriginalExtension();
            $slipPath = $file->storeAs('slips/payments', $filename, 'public');
        }

        // 2. สร้างรายการ Payment ใหม่ (สถานะ 'รอตรวจสอบ')
        // * แอดมินต้องเข้ามาตรวจรูปสลิปก่อน ยอดถึงจะตัดจริงๆ ในระบบ
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => null, // null แปลว่าผู้เช่าเป็นคนส่งมาเอง
            'amount_paid' => $request->amount_paid,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'slip_image' => $slipPath,
            'note' => 'ผู้เช่าแจ้งชำระเงินผ่าน LINE',
            'status' => 'รอตรวจสอบ' // 🌟 สำคัญ: ให้สถานะเป็นรอแอดมินตรวจสอบ
        ]);

        // อัปเดตสถานะบิลเบื้องต้น (เปลี่ยนเป็นรอตรวจสอบสลิป เพื่อไม่ให้ผู้เช่าแจ้งซ้ำซ้อน)
        // **ข้อควรระวัง: ถ้าคุณไม่มี status 'รอตรวจสอบ' ใน Invoice อาจจะข้ามบรรทัดนี้ไปก่อนได้ครับ
        $invoice->update(['status' => 'รอตรวจสอบ']);
        // 🌟 ยิงแจ้งเตือนไปหาแอดมินแบบ Real-time ทันที!
        NewPaymentRequest::dispatch($payment);
        return view('auth.line_success', [
            'message' => 'ส่งหลักฐานการชำระเงินเรียบร้อยแล้ว! ยอดเงินจะอัปเดตหลังจากแอดมินตรวจสอบสลิปครับ 💸'
        ]);
    }
}