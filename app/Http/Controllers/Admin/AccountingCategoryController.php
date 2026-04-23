<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\AccountingCategory;

class AccountingCategoryController extends Controller
{
    // ระบบจัดการ accounting_category

    public function accountingCategoryShow()
    {
        // ดึงหมวดหมู่รายรับ (type_id = 1)
        $income_categories = AccountingCategory::where('type_id', 1)->get();

        // ดึงหมวดหมู่รายจ่าย (type_id = 2)
        $expense_categories = AccountingCategory::where('type_id', 2)->get();

        return view('admin.accounting_categories.show', compact('income_categories', 'expense_categories'));
    }

    public function insertAccountingCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type_id' => 'required|in:1,2'
        ]);
        try {
            DB::beginTransaction();
            AccountingCategory::create([
                'type_id' => $request->type_id,
                'name' => $request->name,
            ]);
            DB::commit();
            return redirect()->back()->with('success', 'เพิ่มหมวดหมู่สำเร็จ');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'เกิดข้อผิดพลาด' . $e->getMessage()]);
        }
    }

    public function updateAccountingCategory(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type_id' => 'required|in:1,2'
        ]);
        try {
            DB::beginTransaction();
            $category = AccountingCategory::findOrFail($id);
            $category->update($request->only(['name', 'type_id']));
            DB::commit();
            return redirect()->back()->with('success', 'แก้ไขข้อมูลสำเร็จ');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'เกิดข้อผิดพลาด' . $e->getMessage()]);
        }
    }
}
