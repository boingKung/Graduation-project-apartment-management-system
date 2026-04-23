<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * finish_date เดิมเป็น date → เปลี่ยนเป็น dateTime เพื่อเก็บเวลาที่ซ่อมเสร็จจริง
     */
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dateTime('finish_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->date('finish_date')->nullable()->change();
        });
    }
};
