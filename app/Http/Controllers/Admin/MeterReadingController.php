<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\Room;
use App\Models\MeterReading;

// นำเข้า Trait
use App\Traits\FormatHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant; // ต้องใช้เพื่อดึง line_id
class MeterReadingController extends Controller
{
    use FormatHelper; // เรียกใช้ Trait ใน Class
    // จดมิเตอร์น้ำไฟ Meter Readings

    public function meterReadingsInsertForm(Request $request)
    {
        // แปลงรอบเดือนที่ส่งมาจากตัวกรอง
        $request->merge([
            'billing_month' => $this->convertThaiYearToAD($request->billing_month),
        ]);
        $billing_month = $request->billing_month ?? date('Y-m');
        $searchRoom = $request->input('search_room');
        $filterTenant = $request->input('filter_tenant', 'occupied'); 
        $filterFloor = $request->input('filter_floor'); // 🌟 รับค่าค้นหาชั้น

        // 1. หา ID ของห้องที่จดมิเตอร์ในเดือนนี้ไปแล้ว ทั้งน้ำและไฟ
        $completedRoomIds = MeterReading::where('billing_month', $billing_month)
            ->groupBy('room_id')
            ->havingRaw('COUNT(DISTINCT meter_type) >= 2')
            ->pluck('room_id');

        // 2. ดึงข้อมูลห้องพัก (ตั้งต้นด้วยการกรองห้องที่จดเสร็จแล้วออก)
        $roomsQuery = Room::with([
            'tenants' => function ($q) {
                $q->where('status', 'กำลังใช้งาน');
            },
            'roomPrice.building'
        ])->whereNotIn('id', $completedRoomIds);

        // --- Logic กรองสถานะห้อง ---
        if ($filterTenant === 'occupied') {
            $roomsQuery->whereIn('status', ['มีผู้เช่า', 'ซ่อมบำรุง']);
        }

        // --- เงื่อนไขค้นหาตามเลขห้อง ---
        if ($searchRoom) {
            $roomsQuery->where('room_number', 'like', "%{$searchRoom}%");
        }

        // 🌟 --- เงื่อนไขค้นหาตามชั้น (ผ่าน RoomPrice) ---
        if ($filterFloor) {
            $roomsQuery->whereHas('roomPrice', function ($q) use ($filterFloor) {
                $q->where('floor_num', $filterFloor);
            });
        }

        $rooms = $roomsQuery->orderBy('room_number', 'asc')->get();

        // 🌟 ดึงหมายเลขชั้นทั้งหมดที่ไม่ซ้ำกัน เพื่อเอาไปสร้าง Dropdown Filter (ผ่านตาราง room_prices)
        $floorNums = DB::table('room_prices')
            ->select('floor_num')
            ->whereNotNull('floor_num')
            ->distinct()
            ->orderBy('floor_num', 'asc')
            ->pluck('floor_num');

        $existingReadings = MeterReading::where('billing_month', $billing_month)->get();

        foreach ($rooms as $room) {
            foreach (['water', 'electric'] as $type) {
                $lastReading = MeterReading::where('room_id', $room->id)
                    ->where('meter_type', $type)
                    ->where('billing_month', '<', $billing_month)
                    ->orderBy('billing_month', 'desc')
                    ->first();

                // ถ้าไม่มีข้อมูลเดือนก่อน ให้ส่งค่า null หรือ 0 ไป
                $room->{"prev_{$type}"} = $lastReading ? $lastReading->current_value : null;
            }
        }
        
        $thai_date = $this->toThaiDate($billing_month, false);
        
        return view('admin.meter_readings.insert', compact('rooms', 'billing_month', 'existingReadings', 'thai_date', 'searchRoom', 'filterTenant', 'filterFloor', 'floorNums'));
    }
    public function insertMeterReading(Request $request)
    {
        // แปลง พ.ศ. -> ค.ศ. ก่อนเริ่ม Validation
        $request->merge([
            'billing_month' => $this->convertThaiYearToAD($request->billing_month),
            'reading_date'  => $this->convertThaiYearToAD($request->reading_date),
        ]);
        $request->validate([
            'billing_month' => 'required',
            'reading_date' => 'required|date',
            'data' => 'required|array'
        ]);

        try {
            DB::beginTransaction();

            $notifications = []; // 🌟 อาร์เรย์สำหรับเก็บข้อมูลเตรียมส่ง LINE

            foreach ($request->data as $roomId => $types) {
                $tenantId = null;
                $waterUsed = 0;
                $electricUsed = 0;
                $hasData = false;

                foreach ($types as $type => $values) {
                    // ข้ามหากไม่ได้กรอกเลขมิเตอร์ปัจจุบัน
                    if (is_null($values['current_value'])) continue;

                    $prev = (float) $values['previous_value'];
                    $current = (float) $values['current_value'];
                    $used = $this->calculateUnitsUsed($prev, $current);
                    $tenantId = $values['tenant_id']; // เก็บ tenant_id ไว้ส่งไลน์
                    $hasData = true;

                    if ($type === 'water') $waterUsed = $used;
                    if ($type === 'electric') $electricUsed = $used;

                    // บันทึกลงตาราง meter_readings โดยตรง
                    MeterReading::create([
                        'room_id' => $roomId,
                        'tenant_id' => $tenantId,
                        'meter_type' => $type,
                        'previous_value' => $prev,
                        'current_value' => $current,
                        'units_used' => $used,
                        'billing_month' => $request->billing_month,
                        'reading_date' => $request->reading_date,
                    ]);
                }

                // 🌟 ถ้ามีการบันทึกข้อมูลของห้องนี้ ให้เก็บลงอาร์เรย์เพื่อเตรียมส่งไลน์
                if ($hasData && $tenantId) {
                    $notifications[$tenantId] = [
                        'room_id' => $roomId,
                        'water' => $waterUsed,
                        'electric' => $electricUsed
                    ];
                }
            }

            DB::commit();

            // 🌟 ส่งไลน์หลังจากบันทึกฐานข้อมูลสำเร็จ 100%
            $this->sendBatchMeterLineNotification($notifications, $request->billing_month, 'insert');

            return redirect()->back()->with('success', 'บันทึกข้อมูลและแจ้งเตือนผู้เช่าเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }
    // หน้าแก้ไขมิเตอร์ (เฉพาะรายการที่จดแล้ว)
    
    public function readMeterReading(Request $request)
    {
        $request->merge([
            'billing_month' => $this->convertThaiYearToAD($request->billing_month),
        ]);
        $billing_month = $request->billing_month ?? date('Y-m');
        $searchRoom = $request->input('search_room');
        $filterFloor = $request->input('filter_floor'); // 🌟 เพิ่มรับค่าตัวกรองชั้น

        $recordedDate = MeterReading::where('billing_month', $billing_month)->value('reading_date') ?? date('Y-m-d');

        $recordedRoomIds = MeterReading::where('billing_month', $billing_month)
            ->pluck('room_id')
            ->unique();

        $roomsQuery = Room::with([
            'tenants' => function ($q) {
                $q->where('status', 'กำลังใช้งาน');
            },
            'roomPrice.building'
        ])->whereIn('id', $recordedRoomIds);

        if ($searchRoom) {
            $roomsQuery->where('room_number', 'like', "%{$searchRoom}%");
        }

        // 🌟 เพิ่มเงื่อนไขกรองตามชั้น
        if ($filterFloor) {
            $roomsQuery->whereHas('roomPrice', function ($q) use ($filterFloor) {
                $q->where('floor_num', $filterFloor);
            });
        }

        $rooms = $roomsQuery->orderBy('room_number', 'asc')->get();

        // 🌟 ดึงข้อมูลชั้นทั้งหมดที่ไม่ซ้ำกัน สำหรับ Dropdown
        $floorNums = DB::table('room_prices')
            ->select('floor_num')
            ->whereNotNull('floor_num')
            ->distinct()
            ->orderBy('floor_num', 'asc')
            ->pluck('floor_num');

        $existingReadings = MeterReading::where('billing_month', $billing_month)->get();

        // ดึงค่า Previous
        foreach ($rooms as $room) {
            foreach (['water', 'electric'] as $type) {
                $lastReading = MeterReading::where('room_id', $room->id)
                    ->where('meter_type', $type)
                    ->where('billing_month', '<', $billing_month)
                    ->orderBy('billing_month', 'desc')
                    ->first();
                $room->{"prev_{$type}"} = $lastReading ? $lastReading->current_value : null;
            }
        }
        
        $thai_date = $this->toThaiDate($billing_month, false);
        
        return view('admin.meter_readings.show', compact('rooms', 'billing_month', 'existingReadings', 'thai_date', 'recordedDate', 'searchRoom', 'filterFloor', 'floorNums'));
    }

    // ฟังก์ชันสำหรับ Update ข้อมูล (ใช้ updateOrCreate เพื่อความปลอดภัย)
    // ฟังก์ชันสำหรับ Update ข้อมูล
    public function updateMeterReading(Request $request)
    {
        $request->merge([
            'billing_month' => $this->convertThaiYearToAD($request->billing_month),
            'reading_date'  => $this->convertThaiYearToAD($request->reading_date),
        ]);
        $request->validate([
            'billing_month' => 'required',
            'reading_date' => 'required|date',
            'data' => 'required|array'
        ]);

        try {
            DB::beginTransaction();
            
            $notifications = []; // 🌟 อาร์เรย์สำหรับเก็บข้อมูลเตรียมส่ง LINE

            foreach ($request->data as $roomId => $types) {
                $tenantId = null;
                $waterUsed = 0;
                $electricUsed = 0;
                $hasData = false;

                foreach ($types as $type => $values) {
                    if (is_null($values['current_value'])) continue;

                    $prev = (float) $values['previous_value'];
                    $current = (float) $values['current_value'];
                    $used = $this->calculateUnitsUsed($prev, $current);
                    $tenantId = $values['tenant_id'];
                    $hasData = true;

                    if ($type === 'water') $waterUsed = $used;
                    if ($type === 'electric') $electricUsed = $used;

                    MeterReading::updateOrCreate(
                        [
                            'room_id' => $roomId,
                            'meter_type' => $type,
                            'billing_month' => $request->billing_month,
                        ],
                        [
                            'tenant_id' => $tenantId,
                            'previous_value' => $prev,
                            'current_value' => $current,
                            'units_used' => $used,
                            'reading_date' => $request->reading_date,
                        ]
                    );
                }

                // 🌟 เก็บข้อมูลห้องที่มีการแก้ไขลงอาร์เรย์
                if ($hasData && $tenantId) {
                    $notifications[$tenantId] = [
                        'room_id' => $roomId,
                        'water' => $waterUsed,
                        'electric' => $electricUsed
                    ];
                }
            }
            
            DB::commit();

            // 🌟 ส่งไลน์แจ้งเตือนการแก้ไข
            $this->sendBatchMeterLineNotification($notifications, $request->billing_month, 'update');

            return redirect()->back()->with('success', 'แก้ไขข้อมูลมิเตอร์และแจ้งเตือนเรียบร้อยแล้ว');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    // 🌟 ฟังก์ชัน Private สำหรับคำนวณหน่วยมิเตอร์ (รองรับมิเตอร์ตีกลับ กฎ >= 9000)
    // =========================================================================
    
    private function calculateUnitsUsed($prev, $current)
    {
        if ($current >= $prev) {
            return $current - $prev;
        }
        
        // 🌟 กรณีตีกลับ อนุญาตเฉพาะเลขเดิมที่ >= 9000 เท่านั้น
        if ($prev >= 9000) {
            $digits = strlen((string) floor($prev));
            $maxMeterValue = pow(10, $digits); // เช่น 9999 คือ 4 หลัก = 10000
            return ($maxMeterValue - $prev) + $current;
        }
        
        // ถ้าน้อยกว่า 9000 แล้วเลขใหม่น้อยกว่าเก่า ถือว่าผิดปกติ ให้คืนค่าติดลบเพื่อให้ Validation จับได้
        return -1; 
    }

    private function convertThaiYearToAD($dateString)
    {
        if (!$dateString) return null;

        try {
            $parts = explode('-', $dateString);
            if (count($parts) >= 2) {
                $year = (int) $parts[0];
                // ถ้าปี > 2400 แสดงว่าเป็น พ.ศ. แน่นอน
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
    // 🌟 ฟังก์ชัน Private สำหรับส่ง LINE แจ้งเตือนจดมิเตอร์หลายห้องพร้อมกัน
    // =========================================================================
    private function sendBatchMeterLineNotification($notifications, $billingMonth, $actionType = 'insert')
    {
        $channelAccessToken = env('LINE_BOT_CHANNEL_ACCESS_TOKEN');
        if (empty($notifications) || !$channelAccessToken) return;

        // ดึงข้อมูลผู้เช่าทั้งหมดที่มีการจดมิเตอร์ในรอบนี้
        $tenantIds = array_keys($notifications);
        $tenants = Tenant::with('room')->whereIn('id', $tenantIds)->whereNotNull('line_id')->get();

        foreach ($tenants as $tenant) {
            try {
                $data = $notifications[$tenant->id];
                $roomNumber = $tenant->room->room_number ?? '-';
                $billingMonth = $this->toThaiDate($billingMonth, false);
                if ($actionType === 'update') {
                    $messageText = "⚠️ มีการแก้ไขยอดจดมิเตอร์ ห้อง {$roomNumber}\n";
                    $messageText .= "📅 รอบเดือน: {$billingMonth}\n\n";
                    $messageText .= "ยอดที่มีการปรับปรุงใหม่:\n";
                } else {
                    $messageText = "📝 แจ้งผลการจดมิเตอร์ ห้อง {$roomNumber}\n";
                    $messageText .= "📅 รอบเดือน: {$billingMonth}\n\n";
                }

                $messageText .= "💧 น้ำประปา ใช้ไป: {$data['water']} หน่วย\n";
                $messageText .= "⚡ ไฟฟ้า ใช้ไป: {$data['electric']} หน่วย\n\n";
                $messageText .= "แอดมินกำลังจัดทำบิลค่าเช่า โปรดรอรับแจ้งเตือนบิลเร็วๆ นี้ครับ 🏢";

                // ยิง API ไปหา LINE ของผู้เช่าคนนั้น
                Http::withToken($channelAccessToken)->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $tenant->line_id,
                    'messages' => [
                        [
                            'type' => 'text',
                            'text' => $messageText
                        ]
                    ]
                ]);

            } catch (\Exception $e) {
                // หากส่ง LINE ไม่สำเร็จ ให้ข้ามไปคนถัดไป และเก็บ Log ไว้
                Log::error("Line Notify Error (Batch Meter - Tenant ID {$tenant->id}): " . $e->getMessage());
                continue;
            }
        }
    }
}
