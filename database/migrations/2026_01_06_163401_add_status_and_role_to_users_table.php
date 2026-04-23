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
        Schema::table('users', function (Blueprint $table) {
            // 1. แยกตำแหน่ง (Role) เพิ่มต่อท้าย lastname
            $table->string('role')
                  ->default('พนักงาน')
                  ->after('lastname')
                  ->comment('ตำแหน่ง: ผู้บริหาร, พนักงาน');
            
            // 2. แยกสถานะการใช้งาน (Status) เพิ่มต่อท้าย role
            $table->string('status')
                  ->default('ใช้งาน')
                  ->after('role')
                  ->comment('สถานะ: ใช้งาน, ระงับใช้งาน');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
