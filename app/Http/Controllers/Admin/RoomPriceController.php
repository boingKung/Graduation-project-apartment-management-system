<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\RoomPrices;
use App\Models\RoomType;
use App\Models\Building;

class RoomPriceController extends Controller
{
    // จัดการรายการ
    public function roomPriceShow(Request $request)
    {
        $room_prices = RoomPrices::with(['building', 'roomType'])
            ->when($request->filter_building, function ($q) use ($request) {
                $q->where('building_id', $request->filter_building);
            })
            ->when($request->filter_room_type, function ($q) use ($request) {
                $q->where('room_type_id', $request->filter_room_type);
            })
            ->when($request->filter_floor, function ($q) use ($request) {
                $q->where('floor_num', $request->filter_floor);
            })
            ->orderBy('building_id', 'asc')
            ->orderBy('floor_num', 'asc')
            ->paginate(20)
            ->withQueryString();
            
        $room_types = RoomType::all();
        $buildings = Building::all();
        
        return view('admin.room_prices.show', compact('room_prices', 'room_types', 'buildings'));
    }

    // สร้างข้อมูลใหม่
    public function insertRoomPrice(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'room_type_id' => 'required|exists:room_types,id',
            'floor_num' => 'required|integer|min:1',
            'color_code' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();
            
            $file = $request->file('color_code');
            
            // 🌟 1. ดักจับ: เช็คว่าไฟล์อัปโหลดมาสมบูรณ์หรือไม่ (เช่น เน็ตหลุดกลางคัน หรือไฟล์เสีย)
            if (!$file->isValid()) {
                throw new \Exception('ไฟล์อัปโหลดไม่สมบูรณ์: ' . $file->getErrorMessage());
            }

            // สร้างชื่อไฟล์ใหม่
            // ดึงแค่นามสกุลไฟล์ (เช่น .jpg, .png) แล้วสร้างชื่อใหม่ด้วยตัวเลขสุ่ม
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // บันทึกรูป
            $path = $file->storeAs('room_prices', $filename, 'public');

            // 🌟 2. ดักจับ: เช็คว่า Server ยอมให้เขียนไฟล์ลงโฟลเดอร์หรือไม่
            if (!$path) {
                throw new \Exception('Server ไม่สามารถบันทึกไฟล์ได้ (อาจติดเรื่อง Permission หรือพื้นที่เต็ม)');
            }

            DB::table('room_prices')->insert([
                'building_id' => $request->building_id,
                'room_type_id' => $request->room_type_id,
                'floor_num' => $request->floor_num,
                'color_code' => $path, // ตอนนี้รับรองว่าไม่ใช่ 0 แน่นอน
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::commit();
            return redirect()->back()->with('success', 'เพิ่มราคาห้องพักและรูปภาพสำเร็จ')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    // อัปเดตข้อมูล
    public function updateRoomPrice(Request $request, $id)
    {
        // 🌟 2. ย้าย Validate ออกมาไว้ข้างนอก
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'room_type_id' => 'required|exists:room_types,id',
            'floor_num' => 'required|integer|min:1',
            'color_code' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();
            
            $data = [
                'building_id' => $request->building_id,
                'room_type_id' => $request->room_type_id,
                'floor_num' => $request->floor_num,
                'updated_at' => now(),
            ];

            $roomPrice = DB::table('room_prices')->where('id', $id)->first();
            
            if ($request->hasFile('color_code')) {
                // เช็คว่ามีชื่อไฟล์เดิมใน DB และไฟล์นั้นมีอยู่จริงใน Disk หรือไม่
                if (!empty($roomPrice->color_code) && Storage::disk('public')->exists($roomPrice->color_code)) {
                    Storage::disk('public')->delete($roomPrice->color_code);
                }
                // สร้างชื่อไฟล์ใหม่
                $filename = time() . '_' . uniqid() . '.' . $request->color_code->getClientOriginalName();
                // บันทึกรูป
                $path = $request->file('color_code')->storeAs(
                    'room_prices',
                    $filename,
                    'public'
                );
                $data['color_code'] = $path;
            }

            DB::table('room_prices')->where('id', $id)->update($data);
            
            DB::commit();
            return redirect()->back()->with('success', 'ข้อมูลราคาห้องถูกอัปเดตแล้ว')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    // ลบข้อมูล
    public function deleteRoomPrice($id)
    {
        try {
            DB::beginTransaction();
            $roomPrice = DB::table('room_prices')->where('id', $id)->first();
            
            // ลบรูปภาพถ้ามี
            if (!empty($roomPrice->color_code) && Storage::disk('public')->exists($roomPrice->color_code)) {
                Storage::disk('public')->delete($roomPrice->color_code);
            }
            
            DB::table('room_prices')->where('id', $id)->delete();
            DB::commit();
            
            return redirect()->back()->with('success', 'ลบข้อมูลสำเร็จ')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}