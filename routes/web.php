<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// นำเข้า Auth Controllers
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\TenantLoginController;

use App\Models\Tenant;
// นำเข้า Controllers หลัก
use App\Http\Controllers\AdminController; // สำหรับ Dashboard
use App\Http\Controllers\TenantController; // สำหรับฝั่งผู้เช่า (Front-end)

// -----------------------------------------------------------------------
// นำเข้า Admin Controllers ที่แยกหมวดหมู่ใหม่ทั้งหมด (13 ตัว + ซ่อมบำรุง)
// -----------------------------------------------------------------------
use App\Http\Controllers\Admin\ApartmentController;
use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\RoomPriceController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\MaintenanceController; // แนะนำให้แยกส่วนแจ้งซ่อมมาที่นี่
use App\Http\Controllers\Admin\TenantController as AdminTenantController; // เปลี่ยนชื่อกันชนกับฝั่งผู้เช่า
use App\Http\Controllers\Admin\MeterReadingController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\TenantExpenseController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\AccountingTransactionController;
use App\Http\Controllers\Admin\AccountingCategoryController;
use App\Http\Controllers\Admin\UserController;

// API Line
use App\Http\Controllers\Api\LineWebhookController;
use App\Http\Controllers\Auth\LineLoginController;

// ==========================================
Route::post('/api/line/webhook', [LineWebhookController::class, 'webhook']);
// --- ระบบ LINE ผูกบัญชี ---
Route::get('/auth/line', [LineLoginController::class, 'redirectToLineLink'])->name('line.login');
Route::get('/auth/line/callback/link', [LineLoginController::class, 'handleLineCallbackLink']); // 🌟 แก้ตรงนี้
Route::get('/auth/line/link', [LineLoginController::class, 'linkAccountForm'])->name('line.link.form');
Route::post('/auth/line/link', [LineLoginController::class, 'linkAccountSave'])->name('line.link.save');

// --- ระบบ LINE ลงทะเบียนผู้เช่าใหม่ ---
Route::get('/auth/line/register', [LineLoginController::class, 'redirectToLineRegister'])->name('line.register');
Route::get('/auth/line/callback/register', [LineLoginController::class, 'handleLineCallbackRegister']); // 🌟 แก้ตรงนี้
Route::get('/auth/line/register/form', [LineLoginController::class, 'registerForm'])->name('line.register.form');
Route::post('/auth/line/register/save', [LineLoginController::class, 'registerSave'])->name('line.register.save');

// ระบบแจ้งชำระเงิน (สำหรับผู้เช่าผ่าน LINE)
Route::get('/auth/line/callback/payment', [LineLoginController::class, 'handleLineCallbackPayment']);
Route::get('/auth/line/payment', [LineLoginController::class, 'paymentForm'])->name('line.payment.form');
Route::post('/auth/line/payment', [LineLoginController::class, 'paymentSave'])->name('line.payment.save');

// ==========================================
// ทดสอบ ระบบ คิดค่าปรับ ถ้าเกิดวันที่ 5 -------
Route::get('/test-late-fees', function () {
    try {
        Artisan::call('app:calculate-late-fees');
        return redirect()->back()->with('success', "ระบบคำนวณค่าปรับทำงานเรียบร้อยแล้ว! ลองเช็คในฐานข้อมูลดูครับ");
    } catch (\Exception $e) {
        return redirect()->back()->withErrors('error', $e->getMessage());
    }
});
// -------------------

// ==========================================
//  Tenant ฝั่งผู้เช่า
// ==========================================
Route::get('/', function () {
    return view('auth.tenantLogin');
})->name('tenant.loginForm');
Route::post('/tenant/login', [TenantLoginController::class, 'login'])->name('tenant.login');

Route::prefix('tenant')->middleware('auth:tenant')->group(function () {
    Route::get('/dashboard', [TenantController::class, 'index'])->name('tenant.dashboard');
    Route::get('/dashboard/invoice-version', [TenantController::class, 'dashboardInvoiceVersion'])->name('tenant.dashboard.invoiceVersion');
    Route::post('/logout', [TenantController::class, 'logout'])->name('tenant.logout');
    Route::get('/index', [TenantController::class, 'tenantIndex'])->name('tenant.index');
    Route::post('/maintenance/send', [TenantController::class, 'sendMaintenance'])->name('tenant.maintenance.send');
    Route::get('/maintenance', [TenantController::class, 'maintenanceIndex'])->name('tenant.maintenance.index');
    Route::get('/invoices', [TenantController::class, 'myInvoices'])->name('tenant.invoices.index');
    Route::get('/invoices/{invoiceId}/payment-detail', [TenantController::class, 'invoicePaymentDetail'])->name('tenant.invoices.paymentDetail');
    Route::get('/invoices/{id}/print', [TenantController::class, 'printInvoice'])->name('tenant.invoices.print');
    Route::get('/profile', [TenantController::class, 'profile'])->name('tenant.profile');
});

// ==========================================
// Admin ฝั่งผู้ดูแลระบบ
// ==========================================
Route::get('/admin/login', [AdminLoginController::class, 'loginForm'])->name('admin.loginForm');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login');

Route::get('/admin/register', [AdminLoginController::class, 'registerForm'])->name('admin.registerForm');
Route::post('/admin/register', [AdminLoginController::class, 'register'])->name('admin.register');

Route::prefix('admin')->middleware('auth:admin')->group(function () {
    
    // 0. Auth & Dashboard
    Route::post('/logout', [AdminLoginController::class, 'adminLogout'])->name('admin.logout');
    Route::get('/dashboard', [AdminController::class, 'adminDashboard'])->name('admin.dashboard'); // ยังใช้ AdminController เดิม
    Route::get('/rental-income', [AdminController::class, 'dashboardRentalIncome'])->name('admin.dashboard.rental_income');
    Route::get('/cashflow', [AdminController::class, 'dashboardCashflow'])->name('admin.dashboard.cashflow');
    Route::get('/meter-analysis', [AdminController::class, 'dashboardMeter'])->name('admin.dashboard.meter');
    // --- API สำหรับดึงข้อมูลกราฟ (แยกตามหน้า) ---
    Route::get('/api/overview', [AdminController::class, 'apiOverviewChart'])->name('admin.dashboard.api.overview');
    Route::get('/api/rental-income', [AdminController::class, 'apiRentalIncomeChart'])->name('admin.dashboard.api.rental_income');
    Route::get('/api/cashflow', [AdminController::class, 'apiCashflowChart'])->name('admin.dashboard.api.cashflow');
    Route::get('/api/meter', [AdminController::class, 'apiMeterChart'])->name('admin.dashboard.api.meter');
    
    // 1. ตั้งค่าอพาร์ทเม้นท์ Apartment
    Route::get('/apartment', [ApartmentController::class, 'apartmentShow'])->name('admin.apartment.show');
    Route::get('/apartment/edit/{id}', [ApartmentController::class, 'editApartment'])->name('admin.apartment.edit');
    Route::put('/apartment/update/{id}', [ApartmentController::class, 'updateApartment'])->name('admin.apartment.update');
    
    // 2. จัดการประเภทตึก Building ตึก 2 4 5 ชั้น
    Route::get('/building', [BuildingController::class, 'buildingShow'])->name('admin.building.show');
    Route::post('/building/insert', [BuildingController::class, 'insertBuilding'])->name('admin.building.insert');
    Route::put('/building/update/{id}', [BuildingController::class, 'updateBuilding'])->name('admin.building.update');
    
    // 3. จัดการประเภทห้อง Room Type
    Route::get('/room_types', [RoomTypeController::class, 'roomTypeShow'])->name('admin.room_types.show');
    Route::post('room_types/insert', [RoomTypeController::class, 'insertRoomType'])->name('admin.room_types.insert');
    Route::put('/room_types/update/{id}', [RoomTypeController::class, 'updateRoomType'])->name('admin.room_types.update');
    Route::post('/room_types/delete/{id}', [RoomTypeController::class, 'deleteRoomType'])->name('admin.room_types.delete');
    
    // 4. จัดการราคาห้อง Room Price
    Route::get('/room_prices', [RoomPriceController::class, 'roomPriceShow'])->name('admin.room_prices.show');
    Route::post('/room_prices/insert', [RoomPriceController::class, 'insertRoomPrice'])->name('admin.room_prices.insert');
    Route::put('/room_prices/update/{id}', [RoomPriceController::class, 'updateRoomPrice'])->name('admin.room_prices.update');
    Route::post('/room_prices/delete/{id}', [RoomPriceController::class, 'deleteRoomPrice'])->name('admin.room_prices.delete');
    
    // 5. จัดการห้อง Rooms
    Route::get('/rooms', [RoomController::class, 'roomShow'])->name('admin.rooms.show');
    Route::post('/rooms/insert', [RoomController::class, 'insertRoom'])->name('admin.rooms.insert');
    Route::put('/rooms/update/{id}', [RoomController::class, 'updateRoom'])->name('admin.rooms.update');
    Route::post('/rooms/delete/{id}', [RoomController::class, 'deleteRoom'])->name('admin.rooms.delete');
    Route::get('/rooms/system', [RoomController::class, 'roomSystem'])->name('admin.rooms.system');
    Route::get('/rooms/{id}/history', [RoomController::class, 'roomHistory'])->name('admin.rooms.history');
    
    // -- ระบบแจ้งซ่อม (Maintenance) (แนะนำให้แยกจาก RoomController มาใช้ MaintenanceController)
    Route::post('/maintenance/insert', [MaintenanceController::class, 'insertMaintenance'])->name('admin.maintenance.insert');
    Route::get('/maintenance', [MaintenanceController::class, 'maintenanceIndex'])->name('admin.maintenance.index');
    Route::put('/maintenance/update/{id}', [MaintenanceController::class, 'updateMaintenanceStatus'])->name('admin.maintenance.update_status');
    Route::post('/admin/maintenance/{id}/delete', [MaintenanceController::class, 'deleteMaintenance'])->name('admin.maintenance.delete');
    
    // --- ระบบจัดการการจองห้องพัก (รออนุมัติ) ---
    Route::get('/registrations', [AdminTenantController::class, 'registrationShow'])->name('admin.registrations.show');
    Route::put('/registrations/{id}/cancel', [AdminTenantController::class, 'cancelRegistration'])->name('admin.registrations.cancel');
    // 🌟 API ดึงรายชื่อคนจองออนไลน์ที่ยังรออนุมัติ
    Route::get('/api/pending-registrations', function () {
        return Tenant::where('status', 'รออนุมัติ')->orderBy('created_at', 'desc')->get(['id', 'first_name', 'last_name', 'phone']);
    });
    // 🌟 หน้าจอยืนยันข้อมูลคนจองออนไลน์ (Review)
    Route::get('/tenants/review-registration/{id}', [AdminTenantController::class, 'reviewRegistration'])->name('admin.tenants.review');
    // 🌟 ฟังก์ชันกดยืนยัน (Approve) พร้อมแจ้ง LINE
    Route::post('/tenants/approve-registration/{id}', [AdminTenantController::class, 'approveRegistration'])->name('admin.tenants.approve');
    // 6. จัดการผู้เช่า Tenant (ใช้ AdminTenantController)
    Route::get('/tenants', [AdminTenantController::class, 'tenantShow'])->name('admin.tenants.show');
    Route::get('/tenants/detail/{id}', [AdminTenantController::class, 'tenantDetail'])->name('admin.tenants.detail');
    Route::get('/tenants/create', [AdminTenantController::class, 'createTenantForm'])->name('admin.tenants.create');
    Route::post('/tenants/insert', [AdminTenantController::class, 'insertTenant'])->name('admin.tenants.insert');
    Route::put('/tenants/update/{id}', [AdminTenantController::class, 'updateTenant'])->name('admin.tenants.update');
    Route::get('/tenants/check-deposit-status/{id}', [AdminTenantController::class, 'checkDepositStatus']);
    Route::put('/tenants/updateStatus/{id}', [AdminTenantController::class, 'updateStatusTenant'])->name('admin.tenants.updateStatusTenant');
    Route::post('/tenants/delete/{id}', [AdminTenantController::class, 'deleteTenant'])->name('admin.tenants.delete');
    
    // 7. จดมิเตอร์น้ำไฟ Meter Readings
    Route::get('/meter_readings', [MeterReadingController::class, 'readMeterReading'])->name('admin.meter_readings.show');
    Route::get('/meter_readings/insert', [MeterReadingController::class, 'meterReadingsInsertForm'])->name('admin.meter_readings.insertForm');
    Route::post('/meter_readings/insert', [MeterReadingController::class, 'insertMeterReading'])->name('admin.meter_readings.insert');
    Route::put('/meter_readings/update', [MeterReadingController::class, 'updateMeterReading'])->name('admin.meter_readings.update');

    // 8. จัดการ บิลค่าเช่า Invoices
    Route::get('/invoices', [InvoiceController::class, 'invoiceShow'])->name('admin.invoices.show');
    Route::get('/invoices/collection_report', [InvoiceController::class, 'invoiceCollectionReport'])->name('admin.invoices.collectionReport');
    Route::post('/invoices/insertOne', [InvoiceController::class, 'insertInvoiceOne'])->name('admin.invoice.insertInvoiceOne');
    Route::post('/invoices/insertAll', [InvoiceController::class, 'insertInvoicesAll'])->name('admin.invoices.insertInvoicesAll');
    Route::post('/invoices/insertMeterReadingOne', [InvoiceController::class, 'insertInvoiceMeterReadingOne'])->name('admin.invoice.insertInvoiceMeterReadingOne');
    Route::post('/invoices/sendInvoiceOne', [InvoiceController::class, 'sendInvoiceOne'])->name('admin.invoice.sendInvoiceOne');
    Route::post('/invoices/sendInvoiceAll', [InvoiceController::class, 'sendInvoiceAll'])->name('admin.invoice.sendInvoiceAll');
    Route::get('/invoices/details/{id}', [InvoiceController::class, 'readInvoiceDetails'])->name('admin.invoices.details');
    Route::get('/invoices/edit/details/{id}', [InvoiceController::class, 'editInvoiceDetails'])->name('admin.invoices.editDetails');
    Route::put('/invoices/update/details/{id}', [InvoiceController::class, 'updateInvoiceDetails'])->name('admin.invoices.updateDetails');
    Route::post('/invoices/delete/{id}', [InvoiceController::class, 'deleteInvoiceOne'])->name('admin.invoices.deleteInvoiceOne');
    Route::get('/invoices/print/invoice_details/{id}', [InvoiceController::class, 'printInvoiceDetails'])->name('admin.invoices.print_invoice_details');
    Route::get('/invoices/print/collection_report', [InvoiceController::class, 'printCollectionReportPdf'])->name('admin.invoices.print_collection_report');
    Route::get('/invoices/print/invoice_details_all', [InvoiceController::class, 'printInvoiceDetailsAll'])->name('admin.invoices.print_invoice_details_all');
    // excel
    Route::get('/invoices/export/collection_report_excel', [InvoiceController::class, 'exportCollectionReportExcel'])->name('admin.invoices.export_collection_report_excel');
    
    // 9. ตั้งค่าเก็บค่าใช้จ่ายกับผู้เช่า Tenant Expenses
    Route::get('/tenant_expenses', [TenantExpenseController::class, 'tenantExpensesShow'])->name('admin.tenant_expenses.show');
    Route::post('/tenant_expenses/insert', [TenantExpenseController::class, 'insertTenantExpense'])->name('admin.tenant_expenses.insert');
    Route::put('/tenant_expenses/update/{id}', [TenantExpenseController::class, 'updateTenantExpense'])->name('admin.tenant_expenses.update');
    Route::post('/tenant_expenses/delete/{id}', [TenantExpenseController::class, 'deleteTenantExpense'])->name('admin.tenant_expenses.delete');
    
    // 10. จัดการ payment การชำระค่าเช่า
    Route::get('payments/pendingInvoicesShow', [PaymentController::class, 'pendingInvoicesShow'])->name('admin.payments.pendingInvoicesShow');
    Route::post('/payments/insert', [PaymentController::class, 'insertPayment_and_AccountingTransaction_of_Tenant'])->name('admin.payments.insert');
    Route::get('/payments/history', [PaymentController::class, 'paymentHistory'])->name('admin.payments.history');
    Route::put('/payments/history/update/{id}', [PaymentController::class, 'updatePayment'])->name('admin.payments.updatePayment');
    Route::put('/payments/history/void/{id}', [PaymentController::class, 'voidPayment'])->name('admin.payments.voidPayment');
    Route::post('/admin/payments/{id}/reject', [PaymentController::class, 'rejectPendingPayment'])->name('admin.payments.reject');
    Route::get('/payments/history/getPaymentDetail/{id}', [PaymentController::class, 'getPaymentDetail'])->name('admin.payments.getPaymentDetail'); // AJAX
    
    // 11. จัดการ accounting_transactions (รายรับ-รายจ่าย)
    Route::get('accounting_transactions', [AccountingTransactionController::class, 'accountingTransactionShow'])->name('admin.accounting_transactions.show');
    Route::get('accounting_transactions/readDetail/{id}', [AccountingTransactionController::class, 'getTransactionDetail'])->name('admin.accounting_transactions.detail'); // AJAX
    Route::get('accounting_transactions/create', [AccountingTransactionController::class, 'accountingTransactionCreate'])->name('admin.accounting_transactions.create');
    Route::post('accounting_transactions/insert', [AccountingTransactionController::class, 'accountingTransactionStore'])->name('admin.accounting_transactions.store');
    Route::put('accounting_transactions/void/{id}', [AccountingTransactionController::class, 'voidTransaction'])->name('admin.accounting_transactions.voidTransaction');
    
    // 11.1 แสดง report รายงาน รายรับ รายจ่าย
    Route::get('accounting_transactions/summary', [AccountingTransactionController::class, 'reportSummary'])->name('admin.accounting_transactions.summary');
    Route::get('/accounting_transactions/get_summary_details', [AccountingTransactionController::class, 'getSummaryDetails'])->name('admin.accounting_transactions.getSummaryDetails'); // AJAX
    Route::get('/accounting_transactions/printSummaryPdf', [AccountingTransactionController::class, 'printSummaryPdf'])->name('admin.accounting_transactions.printSummaryPdf');
    Route::get('accounting_transactions/income', [AccountingTransactionController::class, 'reportIncome'])->name('admin.accounting_transactions.income');
    Route::get('/accounting_transactions/printIncomePdf', [AccountingTransactionController::class, 'printIncomePdf'])->name('admin.accounting_transactions.printIncomePdf');
    Route::get('accounting_transactions/expense', [AccountingTransactionController::class, 'reportExpense'])->name('admin.accounting_transactions.expense');
    Route::get('/accounting_transactions/printExpensePdf', [AccountingTransactionController::class, 'printExpensePdf'])->name('admin.accounting_transactions.printExpensePdf');
    // 11.2 excel
    Route::get('/accounting_transactions/exportSummaryExcel', [AccountingTransactionController::class, 'exportSummaryExcel'])->name('admin.accounting_transactions.exportSummaryExcel');
    Route::get('/accounting_transactions/exportIncomeExcel', [AccountingTransactionController::class, 'exportIncomeExcel'])->name('admin.accounting_transactions.exportIncomeExcel');
    Route::get('/accounting_transactions/exportExpenseExcel', [AccountingTransactionController::class, 'exportExpenseExcel'])->name('admin.accounting_transactions.exportExpenseExcel');
    
    // 12. จัดการ ระบบ accounting_category
    Route::get('/accounting_category', [AccountingCategoryController::class, 'accountingCategoryShow'])->name('admin.accounting_category.show');
    Route::post('/accounting_category/insert', [AccountingCategoryController::class, 'insertAccountingCategory'])->name('admin.accounting_category.insert');
    Route::put('/accounting_category/update/{id}', [AccountingCategoryController::class, 'updateAccountingCategory'])->name('admin.accounting_category.update');
    
    // 13. จัดการผู้ดูแลระบบ Admin
    Route::get('/users_manage', [UserController::class, 'usersManageShow'])->name('admin.users_manage.show');
    Route::post('/users_manage/insert', [UserController::class, 'insertUserManage'])->name('admin.users_manage.insert');
    Route::put('/users_manage/update/{id}', [UserController::class, 'updateUserManage'])->name('admin.users_manage.update');
    Route::post('/users_manage/delete/{id}', [UserController::class, 'deleteUserManage'])->name('admin.users_manage.delete');

});