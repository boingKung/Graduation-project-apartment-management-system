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
        Schema::table('tenants', function (Blueprint $table) {
            // ลบเงื่อนไขห้ามซ้ำ (Unique) ออก
            $table->dropUnique('tenants_line_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // กรณีอยากย้อนกลับ ค่อยใส่ Unique คืน
            $table->unique('line_id', 'tenants_line_id_unique');
        });
    }
};
