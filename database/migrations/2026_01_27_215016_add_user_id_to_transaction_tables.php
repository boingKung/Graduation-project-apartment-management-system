<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. เพิ่มในตาราง invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('room_id')
                  ->constrained('users')->onDelete('set null');
        });

        // 2. เพิ่มในตาราง payments
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('invoice_id')
                  ->constrained('users')->onDelete('set null');
        });

        // 3. เพิ่มในตาราง accounting_transactions
        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('tenant_id')
                  ->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // ลบ Foreign Key และ Column ออกหากมีการ Rollback
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};