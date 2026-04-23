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
        Schema::table('tenant_expenses', function (Blueprint $table) {
            // เพิ่มการเชื่อมโยงกับหมวดบัญชีรายรับ
            $table->foreignId('accounting_category_id')->nullable()->after('id')
                ->constrained('accounting_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_expenses', function (Blueprint $table) {
            $table->dropForeign(['accounting_category_id']);
            $table->dropColumn('accounting_category_id');
        });
    }
};
