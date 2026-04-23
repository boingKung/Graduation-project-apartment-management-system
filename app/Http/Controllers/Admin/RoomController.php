<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\RoomPrices;
use App\Models\RoomType;
use App\Models\Building;
use App\Models\Room;
use App\Models\Maintenance;

// นำเข้า Trait
use App\Traits\FormatHelper;
class RoomController extends Controller
{
    use FormatHelper; // เรียกใช้ Trait ใน Class
    // จัดการห้อง Rooms

    public function roomShow(Request $request)
    {
        // 1. ดึงข้อมูล Rooms พร้อมความสัมพันธ์ (Room -> RoomPrice -> Building & RoomType)
        $rooms = Room::with(['roomPrice.building', 'roomPrice.roomType'])
            // ค้นหาตามเลขห้อง (ใหม่)
            ->when($request->filter_room, function ($q) use ($request) {
                $q->where('room_number', 'like', "%{$request->filter_room}%");
            })
            // กรองตามอาคาร (ปรับเปลี่ยนจาก building_id เดิม)
            ->when($request->filter_building, function ($q) use ($request) {
                $q->whereHas('roomPrice', function ($subQ) use ($request) {
                    $subQ->where('building_id', $request->filter_building);
                });
            })
            // กรองตามประเภทห้อง
            ->when($request->filter_room_type, function ($q) use ($request) {
                $q->whereHas('roomPrice', function ($subQ) use ($request) {
                    $subQ->where('room_type_id', $request->filter_room_type);
                });
            })
            // กรองตามชั้น
            ->when($request->filter_floor, function ($q) use ($request) {
                $q->whereHas('roomPrice', function ($subQ) use ($request) {
                    $subQ->where('floor_num', $request->filter_floor);
                });
            })
            // กรองตามสถานะห้องพัก
            ->when($request->filter_status, function ($q) use ($request) {
                $q->where('status', $request->filter_status);
            })
            // เรียงลำดับตามเลขห้อง
            ->orderBy('room_number', 'asc')
            ->paginate(20)
            ->withQueryString();

        // 2. ดึงข้อมูลสำหรับ Dropdown ใน Filter และ Modal
        $buildings = Building::all();
        $room_types = RoomType::all();

        // ดึง room_prices เพื่อใช้เลือกตอน insert/edit ห้องใหม่
        $room_prices = RoomPrices::with(['building', 'roomType'])
            ->orderBy('building_id', 'asc')
            ->orderBy('floor_num', 'asc')
            ->get();

        return view('admin.rooms.show', compact('rooms', 'buildings','room_types', 'room_prices'));
    }

    public function insertRoom(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'room_number' => 'required|string|max:4|unique:rooms,room_number',
                'room_price_id' => 'required|exists:room_prices,id',
                'price' => 'required|numeric|min:0',
                'status' => 'required',
                'remark' => 'nullable|string',
            ]);
            DB::table('rooms')->insert([
                'room_number' => $request->room_number,
                'room_price_id' => $request->room_price_id,
                'price' => $request->price,
                'status' => $request->status,
                'remark' => $request->remark,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->back()->with('success', 'เพิ่มห้องพักสำเร็จ')->withInput();
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function updateRoom(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'room_number' => 'required|string|max:4|unique:rooms,room_number,' . $id,
                'room_price_id' => 'required|exists:room_prices,id',
                'price' => 'required|numeric|min:0',
                'status' => 'required',
                'remark' => 'nullable|string',
            ]);
            $data = [
                'room_number' => $request->room_number,
                'room_price_id' => $request->room_price_id,
                'price' => $request->price,
                'status' => $request->status,
                'remark' => $request->remark,
                'updated_at' => now(),
            ];
            DB::table('rooms')->where('id', $id)->update($data);
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return back()->with('success', 'ข้อมูลห้องพักถูกอัปเดตแล้ว')->withInput();
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();

        }
    }

    public function deleteRoom($id)
    {
        try {
            DB::beginTransaction();
            DB::table('rooms')->where('id', $id)->delete();
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->back()->with('success', 'ลบห้องพักสำเร็จ')->withInput();
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();

        }
    }
    //15 ก.พ. 69 จัดการผังห้อง
    //ส่วนสองเพิ่มมา
    public function roomSystem(Request $request)
    {
        $buildingId = $request->input('building_id');

        // --- กรณีที่ 1: ยังไม่ได้เลือกตึก (แสดงหน้าเมนูเลือกตึก) ---
        if (!$buildingId) {
            // ดึงรายชื่อตึกพร้อมนับจำนวนห้องว่าง/ไม่ว่าง เพื่อโชว์สถิติเบื้องต้น
            $buildings = Building::withCount([
                'rooms as total_rooms',
                'rooms as available_rooms' => function ($q) {
                    $q->where('status', 'ว่าง');
                },
                'rooms as occupied_rooms' => function ($q) {
                    $q->where('status', 'มีผู้เช่า');
                }
            ])->get();

            return view('admin.rooms.system_select', compact('buildings'));
        }

        // --- กรณีที่ 2: เลือกตึกแล้ว (แสดงผังห้อง แยกชั้น) ---

        // 1. ดึงข้อมูลตึกที่เลือก
        $currentBuilding = Building::findOrFail($buildingId);

        // 2. สร้าง Query ดึงห้องเฉพาะตึกนี้

        $query = Room::with([
            // 🌟 ดึงข้อมูลผู้เช่า พร้อมกับดึงบิลที่ค้างชำระของ 'ผู้เช่าคนนี้' ติดมาด้วย
            'tenants' => function ($q) {
                $q->where('status', 'กำลังใช้งาน')
                  ->with(['invoices' => function ($invQ) {
                      $invQ->whereIn('status', ['ค้างชำระ', 'ชำระบางส่วน']);
                  }]);
            },
            'roomPrice', // เพื่อเอาราคา และ ชั้น (floor_num)
            'roomPrice.roomType'
        ])
            ->whereHas('roomPrice', function ($q) use ($buildingId) {
                $q->where('building_id', $buildingId);
            });

        // 3. Filter เพิ่มเติม (ค้นหา/สถานะ)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('room_number', 'like', "%{$request->search}%");
        }

        // 4. ดึงข้อมูลและเรียงลำดับ
        $rooms = $query->orderBy('room_number', 'asc')->get();

        // 🌟 5. คำนวณและเตรียมข้อมูลสำหรับแสดงผล (ย้ายมาจาก Blade)
        $today = \Carbon\Carbon::now()->startOfDay(); // ดึงเวลาแค่วันละครั้ง ประหยัดทรัพยากร

        $rooms->each(function ($room) use ($today) {
            // ดึงผู้เช่าปัจจุบัน
            $tenant = $room->tenants->first();
            $room->current_tenant = $tenant; 
            
            // ข้อมูลประเภทห้อง
            $room->type_name = $room->roomPrice->roomType->name ?? '-';
            $room->type_desc = $room->roomPrice->roomType->description ?? '-';
            
            // 1. คำนวณยอดค้างชำระ
            $unpaidAmount = ($tenant && $tenant->invoices) ? $tenant->invoices->sum('remaining_balance') : 0;
            $room->unpaid_amount = $unpaidAmount;

            // 2. กำหนดสถานะสีที่จะแสดงผล (UI Status)
            $uiStatus = $room->status;
            if ($unpaidAmount > 0) {
                $uiStatus = 'ค้างชำระ';
            }
            $room->ui_status = $uiStatus;

            // 3. คำนวณวันหมดอายุสัญญา
            $room->days_left = null;
            $room->display_end_date = null;
            
            if ($tenant && $tenant->end_date) {
                $endDate = \Carbon\Carbon::parse($tenant->end_date)->startOfDay();
                $room->days_left = (int) $today->diffInDays($endDate, false); 
                $room->display_end_date = \Carbon\Carbon::parse($tenant->end_date)->locale('th')->isoFormat('D MMM YY');
            }
        });
        
        // 5. ไฮไลท์สำคัญ: จัดกลุ่มห้องตาม "ชั้น" (floor_num)
        // ใช้ floor_num จากตาราง room_prices มาเป็นตัวแบ่ง
        $roomsByFloor = $rooms->groupBy(function ($item) {
            return $item->roomPrice->floor_num ?? 1; // ถ้าไม่มีข้อมูลชั้น ให้เป็นชั้น 1
        })->sortKeys(); // เรียงชั้น 1, 2, 3...

        return view('admin.rooms.system_view', compact('currentBuilding', 'roomsByFloor'));
    }
    //16 ก.พ. ประวัติห้องพัก
    public function roomHistory($id)
    {
        // 1. ดึงข้อมูลห้อง (พร้อมตึกและราคา)
        $room = Room::with(['building', 'roomPrice.roomType'])->findOrFail($id);

        // 2. หาผู้เช่า "ปัจจุบัน" (สำหรับการ์ดด้านซ้ายมือ)
        $currentTenant = $room->tenants()->where('status', 'กำลังใช้งาน')->first();
        if ($currentTenant) {
            $currentTenant->formatted_start_date = $this->toThaiDate($currentTenant->start_date, true, true);
        }

        // 🌟 3. ดึงประวัติผู้เช่าทั้งหมด (แบ่งหน้า Paginator)
        // ใช้ชื่อตัวแปร tenant_page เพื่อไม่ให้ชนกับแท็บแจ้งซ่อม
        $allTenants = $room->tenants()
            ->orderByRaw("CASE WHEN status = 'กำลังใช้งาน' THEN 1 ELSE 2 END") // ให้ผู้เช่าปัจจุบันอยู่บนสุด
            ->orderBy('end_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'tenant_page');

        // จัดรูปแบบวันที่ใน Paginator
        $allTenants->getCollection()->transform(function ($t) {
            $t->formatted_start_date = $this->toThaiDate($t->start_date, true, true);
            $t->formatted_end_date = $t->end_date ? $this->toThaiDate($t->end_date, true, true) : '-';
            return $t;
        });

        // 🌟 4. ดึงประวัติการแจ้งซ่อม (แบ่งหน้า Paginator)
        // ใช้ชื่อตัวแปร repair_page
        $maintenanceLogs = Maintenance::where('room_id', $id)
            ->orderBy('repair_date', 'desc')
            ->paginate(10, ['*'], 'repair_page');

        // จัดรูปแบบวันที่ใน Paginator
        $maintenanceLogs->getCollection()->transform(function ($log) {
            $log->formatted_repair_date = $this->toThaiDate($log->repair_date, true, true);
            $log->formatted_finish_date = $log->finish_date ? $this->toThaiDate($log->finish_date, true, true) : '-';
            
            if ($log->technician_time) {
                $log->formatted_tech_time = $this->toThaiDate($log->technician_time, true, true) . ' ' . \Carbon\Carbon::parse($log->technician_time)->format('H:i') . ' น.';
            } else {
                $log->formatted_tech_time = '-';
            }
            return $log;
        });

        return view('admin.rooms.history', compact('room', 'currentTenant', 'allTenants', 'maintenanceLogs'));
    }
}
