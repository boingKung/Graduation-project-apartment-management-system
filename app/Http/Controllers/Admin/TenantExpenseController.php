<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\TenantExpense;
use App\Models\AccountingCategory;

class TenantExpenseController extends Controller
{
    //// จัดการค่าใช้จ่ายกับผู้เช่า Tenant Expenses

    public function tenantExpensesShow()
    {
        $expenses = TenantExpense::with('category')->paginate(10);
        // ดึงเฉพาะหมวดหมู่ที่เป็น "รายรับ" (type_id = 1) เพื่อให้ Admin เลือกจับคู่
        $categories = AccountingCategory::where('type_id', 1)->get();
        return view('admin.tenant_expenses.show', compact('expenses', 'categories'));
    }

    public function insertTenantExpense(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:50|unique:tenant_expenses,name',
                'price' => 'required|numeric|min:0',
                'accounting_category_id' => 'required|exists:accounting_categories,id', // ต้องเลือกหมวดหมู่
            ]);
            DB::table('tenant_expenses')->insert([
                'name' => $request->name,
                'price' => $request->price,
                'accounting_category_id' => $request->accounting_category_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->route('admin.tenant_expenses.show')->with('success', 'เพิ่มรายการค่าใช้จ่ายสำเร็จ');
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    public function updateTenantExpense(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:50|unique:tenant_expenses,name,' . $id,
                'price' => 'required|numeric|min:0',
                'accounting_category_id' => 'required|exists:accounting_categories,id', // ต้องเลือกหมวดหมู่
            ]);
            $data = [
                'name' => $request->name,
                'price' => $request->price,
                'accounting_category_id' => $request->accounting_category_id,
                'updated_at' => now(),
            ];
            DB::table('tenant_expenses')->where('id', $id)->update($data);
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->route('admin.tenant_expenses.show')->with('success', 'ข้อมูลรายการค่าใช้จ่ายถูกอัปเดตแล้ว');
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);

        }
    }

    public function deleteTenantExpense($id)
    {
        try {
            DB::beginTransaction();
            DB::table('tenant_expenses')->where('id', $id)->delete();
            // บันทึกการเปลี่ยนแปลง
            DB::commit();
            return redirect()->route('admin.tenant_expenses.show')->with('success', 'ลบรายการค่าใช้จ่ายสำเร็จ');
        } catch (\Exception $e) {
            // ยกเลิกการบันทึกข้อมูลถ้ามีข้อผิดพลาด
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);

        }
    }
}
