<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
class BuildingController extends Controller
{
    // ไปหน้า จัดการประเภทตึก Building ตึก 2 4 5 ชั้น

    public function buildingShow()
    {
        $buildings = DB::table('buildings')->get();
        return view('admin.building.show', compact('buildings'));
    }
    public function insertBuilding(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:255',
            ]);
            
            $data = [
                'name' => $request->name,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            DB::table('buildings')->insert($data);
            
            DB::commit();
            return redirect()->route('admin.building.show')->with('success', 'เพิ่มข้อมูลอาคารเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function updateBuilding(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $data = [
                'name' => $request->name,
                'updated_at' => now(),
            ];
            DB::table('buildings')->where('id', $id)->update($data);
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->route('admin.building.show')->with('success', 'ข้อมูลอาคารถูกอัปเดตแล้ว');
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);

        }
    }
}
