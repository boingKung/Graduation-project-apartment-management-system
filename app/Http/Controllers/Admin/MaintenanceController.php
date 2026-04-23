<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\Tenant;
use App\Models\Maintenance;
use App\Events\MaintenanceStatusUpdated;
class MaintenanceController extends Controller
{
    // [Maintenance - Admin] ส่วนบำรุงห้องพัก (จัดการโดยแอดมิน)
    // เพิ่มรายการซ่อมจากหน้า history ของห้อง

    public function insertMaintenance(Request $request)
    {
        $request->merge([
            'technician_time' => $this->convertThaiYearToAD($request->technician_time),
        ]);
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'title' => 'required|string|max:255',
            'details' => 'nullable|string',
            'technician_name' => 'nullable|string|max:255',
            'technician_time' => 'nullable|date',
            'status' => 'required|in:pending,processing,finished,cancelled',
        ]);

        // หาผู้เช่าปัจจุบันของห้องนั้น
        $tenantId = Tenant::where('room_id', $request->room_id)
            ->where('status', 'กำลังใช้งาน')
            ->value('id');

        // ถ้าไม่มีผู้เช่า ให้เป็น null (อาจจะเป็นกรณีแจ้งซ่อมห้องว่าง)
        
        try {
            DB::beginTransaction();
            Maintenance::create([
                'tenant_id' => $tenantId, // จะเป็น null ได้ถ้าห้องว่าง
                'room_id' => $request->room_id,
                'title' => $request->title,
                'details' => $request->details,
                'status' => $request->status,
                'repair_date' => now(), // วันที่แจ้ง
                'technician_name' => $request->technician_name,
                'technician_time' => $request->technician_time,
                'finish_date' => ($request->status === 'finished') ? now() : null,
            ]);
            DB::commit();
            return back()->with('success', 'บันทึกการแจ้งซ่อมใหม่เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()])->withInput();
        }
    }
    // แสดงรายการแจ้งซ่อมทั้งหมด (พร้อมตัวกรองสถานะ)
    public function maintenanceIndex(Request $request)
    {
        $status = $request->status; // รับค่า filter สถานะ

        $query = Maintenance::with(['room.roomPrice.building']) // ดึงข้อมูลห้อง+ตึกมาด้วย
            ->orderByRaw("FIELD(status, 'pending', 'processing', 'finished', 'cancelled')") // เรียงลำดับความสำคัญ
            ->orderBy('created_at', 'desc'); // 🌟 2. เพิ่มการเรียงวันที่จากล่าสุด (ใหม่สุด) ไปหาเก่าสุด

        if ($status) {
            $query->where('status', $status);
        }

        $maintenances = $query->paginate(15);

        // นับจำนวนงานตามสถานะ (ใช้ทำสรุปภาพรวมด้านบน)
        $statusCounts = Maintenance::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // นับจำนวนงานที่ "รอดำเนินการ" เพื่อเอาไปโชว์เป็น Badge
        $pendingCount = $statusCounts['pending'] ?? 0;

        return view('admin.maintenance.index', compact('maintenances', 'pendingCount', 'statusCounts'));
    }

    // อัปเดตสถานะการซ่อม (รับเรื่อง / ปิดงาน / ยกเลิก)
    // ออกแบบให้รับ partial update — ส่งเฉพาะ field ที่ต้องการเปลี่ยน
    public function updateMaintenanceStatus(Request $request, $id)
    {
        // 🚩 แปลงปี พ.ศ. เป็น ค.ศ. ก่อนเริ่มทำงาน
        $request->merge([
            'technician_time' => $this->convertThaiYearToAD($request->technician_time),
        ]);
        try {
            DB::beginTransaction();
            $maintenance = Maintenance::findOrFail($id);

            if ($maintenance->status === 'finished') {
                return back()->withErrors(['error' => 'งานซ่อมที่เสร็จสิ้นแล้วไม่สามารถแก้ไขได้']);
            }

            // สร้าง data array เฉพาะ field ที่ส่งมาจริง (disabled input จะไม่ถูกส่ง)
            $data = ['status' => $request->status];

            if ($request->has('details')) {
                $data['details'] = $request->details;
            }
            if ($request->has('technician_name')) {
                $data['technician_name'] = $request->technician_name;
            }
            if ($request->has('technician_time')) {
                $data['technician_time'] = $request->technician_time ?: null;
            }

            $data['finish_date'] = ($request->status === 'finished') ? now() : null;

            $maintenance->update($data);

            // ข้อความแจ้งตามสถานะ
            $messages = [
                'processing' => 'รับงานซ่อมเรียบร้อยแล้ว',
                'finished'   => 'ปิดงานซ่อมเรียบร้อยแล้ว',
                'cancelled'  => 'ยกเลิกงานซ่อมเรียบร้อยแล้ว',
            ];
            DB::commit();

            // แจ้งเตือน tenant แบบ real-time ผ่าน Reverb (หลัง commit)
            try {
                MaintenanceStatusUpdated::dispatch($maintenance->fresh());
            } catch (\Throwable $e) {
                \Log::warning('Broadcast MaintenanceStatusUpdated failed: ' . $e->getMessage());
            }

            return back()->with('success', $messages[$request->status] ?? 'อัปเดตสถานะงานซ่อมเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    // ลบรายการแจ้งซ่อม
    public function deleteMaintenance($id)
    {
        try {
            DB::beginTransaction();
            $maintenance = Maintenance::findOrFail($id);
            $maintenance->delete();
            DB::commit();
            return back()->with('success', 'ลบรายการแจ้งซ่อมเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'ลบรายการไม่สำเร็จ: ' . $e->getMessage()]);
        }
    }

    private function convertThaiYearToAD($dateString)
    {
        if (!$dateString) return null;

        try {
            // รองรับทั้งแบบ Y-m-d และ Y-m-d H:i:s
            $parts = explode('-', $dateString);
            if (count($parts) >= 3) {
                $year = (int) $parts[0];
                // ถ้าปีเกิน 2400 แสดงว่าเป็น พ.ศ.
                if ($year > 2400) {
                    $year = $year - 543;
                    return $year . '-' . $parts[1] . '-' . $parts[2];
                }
            }
        } catch (\Exception $e) {
            return $dateString;
        }
        return $dateString;
    }
}
