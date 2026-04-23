<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
    {
        // แก้ไขตาราง accounting_transactions
        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->string('status')->default('active')->after('description')->comment('สถานะรายการ: active, void');
        });

        // แก้ไขตาราง payments
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->default('active')->after('note')->comment('สถานะการจ่ายเงิน: active, void');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
