<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // เพิ่มคอลัมน์ issue_date ต่อท้าย billing_month
            $table->date('issue_date')->after('billing_month')->nullable()->comment('วันที่ออกใบแจ้งหนี้');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // สำหรับลบคอลัมน์ออกหากมีการ rollback
            $table->dropColumn('issue_date');
        });
    }
};
