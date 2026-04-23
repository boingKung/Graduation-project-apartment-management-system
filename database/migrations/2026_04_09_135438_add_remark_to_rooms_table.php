<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // เพิ่มคอลัมน์ remark ประเภท text โดยให้เป็น null ได้ วางไว้หลัง status
            $table->text('remark')->nullable()->after('status')->comment('หมายเหตุ');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};