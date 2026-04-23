<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class AdminLoginController extends Controller
{
    //
    public function loginForm()
    {
        return view('auth.adminLogin');
    }

    public function login(Request $request)
    {
        try {
            // Logic for admin login
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);
            // กำหนดเงื่อนไขการ Login: username + password และต้องมีสถานะ 'ใช้งาน' เท่านั้น
            $credentials = [
                'username' => $request->username,
                'password' => $request->password,
                'status'   => 'ใช้งาน' // เงื่อนไขเพิ่มเติมที่เพิ่มเข้าไป
            ];
            if (Auth::guard('admin')->attempt($credentials)) {
                $request->session()->regenerate();
                return redirect()->route('admin.dashboard')->with('success', 'เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับคุณผู้ดูแล');
            }else {
                return redirect()->back()->withErrors([
                    'error' => 'เข้าสู่ระบบไม่สำเร็จ! ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง หรือบัญชีของคุณถูกระงับการใช้งาน',
                ]);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }
    }

    public function registerForm()
    {
        return view('auth.adminRegister');
    }

    public function register(Request $request)
    {
        try {
            // Logic for admin registration
            $request->validate([
                'username' => 'required|string|unique:users,username',
                'password' => 'required|string|min:6|confirmed',
                'firstname' => 'required|string|max:50',
                'lastname' => 'required|string|max:50',
            ]);

            $user = User::create([
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'role' => 'พนักงาน',
                'status' => 'ใช้งาน',
            ]);
            // ยืนยันการบันทึกข้อมูลทั้งหมด
            DB::commit();
            return redirect()->route('admin.loginForm')->with('success', 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ');
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors([$e->getMessage()]);
        }
    }
    public function adminLogout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.loginForm')->with('success', 'ออกจากระบบสำเร็จ');
    }
}
