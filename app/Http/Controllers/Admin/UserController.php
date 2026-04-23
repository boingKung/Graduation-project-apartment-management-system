<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

class UserController extends Controller
{
    // จัดการผู้ดูแลระบบ Admin

    public function usersManageShow(Request $request)
    {
        $admins = User::query()
            // ค้นหาตาม Username
            ->when($request->filter_username, function ($q) use ($request) {
                $q->where('username', 'like', "%{$request->filter_username}%");
            })
            // ค้นหาตามชื่อหรือนามสกุล
            ->when($request->filter_name, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('firstname', 'like', "%{$request->filter_name}%")
                          ->orWhere('lastname', 'like', "%{$request->filter_name}%");
                });
            })
            // กรองตามตำแหน่ง (Role)
            ->when($request->filter_role, function ($q) use ($request) {
                $q->where('role', $request->filter_role);
            })
            // กรองตามสถานะ
            ->when($request->filter_status, function ($q) use ($request) {
                $q->where('status', $request->filter_status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users_manage.show', compact('admins'));
    }

    public function insertUserManage(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'username' => 'required|string|max:50',
                'password' => 'required|string|min:6|confirmed',
                'firstname' => 'required|string|max:50',
                'lastname' => 'required|string|max:50',
                'role' => 'required|string|max:20',
            ]);
            User::create([
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'role' => $request->role,
                'status' => 'ใช้งาน',
            ]);
            DB::commit();
            return redirect()->back()->with('success', 'เพิ่มผู้ดูแลระบบสำเร็จ')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function updateUserManage(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'username' => 'required|string|max:50',
                'password' => 'nullable|string|min:6|confirmed',
                'firstname' => 'required|string|max:50',
                'lastname' => 'required|string|max:50',
                'role' => 'required|string|max:20',
                'status' => 'required',
            ]);
            $data = [
                'username' => $request->username,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'role' => $request->role,
                'status' => $request->status,
            ];
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }
            User::where('id', $id)->update($data);
            DB::commit();
            return redirect()->back()->with('success', 'อัปเดตข้อมูลผู้ดูแลระบบสำเร็จ')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function deleteUserManage($id)
    {
        try {
            DB::beginTransaction();
            User::where('id', $id)->delete();
            DB::commit();
            return redirect()->back()->with('success', 'ลบผู้ดูแลระบบสำเร็จ')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}
