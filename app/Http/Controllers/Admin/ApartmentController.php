<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

// นำเข้า Trait
use App\Traits\FormatHelper;
class ApartmentController extends Controller
{
    use FormatHelper; // เรียกใช้ Trait ใน Class
    // ไปหน้า ตั้งค่าอพาร์ทเม้นท์ settingApartment
    public function apartmentShow()
    {
        $apartment = DB::table('apartment')->first();
        return view('admin.apartment.show', compact('apartment'));
    }
    public function editApartment($id)
    {
        $apartment = DB::table('apartment')->where('id', $id)->first();
        return view('admin.apartment.edit', compact('apartment'));
    }
    public function updateApartment(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:255',
                'address_no' => 'nullable|string|max:100',
                'moo' => 'nullable|string|max:3',
                'sub_district' => 'nullable|string|max:100',
                'district' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:5',
                'phone' => 'nullable|string|max:10',
                'invoice_due_day' => 'required|integer|min:1|max:30',
                'late_fee_grace_days' => 'required|integer|min:0',
                'free_resident_limit' => 'required|integer|min:1',
            ]);
            $data = [
                'name' => $request->name,
                'address_no' => $request->address_no,
                'moo' => $request->moo,
                'sub_district' => $request->sub_district,
                'district' => $request->district,
                'province' => $request->province,
                'postal_code' => $request->postal_code,
                'phone' => $request->phone,
                'invoice_due_day' => $request->invoice_due_day,
                'late_fee_grace_days' => $request->late_fee_grace_days,
                'free_resident_limit' => $request->free_resident_limit,
                'updated_at' => now(),
            ];
            DB::table('apartment')->where('id', $id)->update($data);
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->route('admin.apartment.show', $id)->with('success', 'ข้อมูลอพาร์ทเม้นท์ถูกอัปเดตแล้ว');
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

}
