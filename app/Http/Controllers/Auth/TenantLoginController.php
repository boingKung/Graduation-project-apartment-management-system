<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
class TenantLoginController extends Controller
{
    //
        public function login(Request $request)
    {
        try {
            // Logic for admin login
            $request->validate([
                'room_number' => 'required|string',
                'password' => 'required|string',
            ]);
            $rooms = DB::table('rooms')->where('room_number', $request->room_number)->first();
            if (!$rooms) {
                return redirect()->back()->withErrors([
                    'error' => 'เข้าสู่ระบบไม่สำเร็จ! ห้องพักไม่ถูกต้อง',
                ]);
            }
            $id = $rooms->id;
            $credentials = [
                'room_id' => $id,
                'password' => $request->password,
                'status' => 'กำลังใช้งาน' // สำคัญมาก: เพื่อให้ Login ได้เฉพาะคนปัจจุบัน    
            ];
            if (Auth::guard('tenant')->attempt($credentials)) {
                $request->session()->regenerate();
                $tenant = Auth::guard('tenant')->user();
                return redirect()->route('tenant.index')->with('success', 'เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับคุณ ' . $tenant->first_name);
            }else {
                
                return redirect()->back()->withErrors([
                    'error' => 'เข้าสู่ระบบไม่สำเร็จ! ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง',
                ]);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }
    }
    public function tenantLogout(Request $request)
    {
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('tenant.loginForm')->with('success', 'ออกจากระบบสำเร็จ');
    }
}
