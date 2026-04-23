<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
class RoomTypeController extends Controller
{
    // จัดการประเภทห้อง Room Type

    public function roomTypeShow()
    {
        $room_types = DB::table('room_types')->get();
        return view('admin.room_types.show', compact('room_types'));
    }

    public function insertRoomType(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:255|unique:room_types,name',
            ]);
            DB::table('room_types')->insert([
                'name' => $request->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->route('admin.room_types.show')->with('success', 'เพิ่มประเภทห้องพักสำเร็จ')->withInput();
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function updateRoomType(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:255|unique:room_types,name,',
            ]);
            $data = [
                'name' => $request->name,
                'updated_at' => now(),
            ];
            DB::table('room_types')->where('id', $id)->update($data);
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->route('admin.room_types.show')->with('success', 'ข้อมูลประเภทห้องถูกอัปเดตแล้ว');
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);

        }
    }
    public function deleteRoomType($id)
    {
        try {
            DB::beginTransaction();
            DB::table('room_types')->where('id', $id)->delete();
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->route('admin.room_types.show')->with('success', 'ลบประเภทห้องสำเร็จ');
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);

        }
    }
}
