<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('apartment', function (Blueprint $table) {
            // วันที่ครบกำหนดชำระของทุกเดือน (ค่าเริ่มต้นคือวันที่ 5)
            $table->integer('invoice_due_day')->default(5)->after('postal_code')->comment('วันที่ครบกำหนดชำระ');
            
            // จำนวนวันผ่อนผันก่อนเริ่มคิดค่าปรับ (ค่าเริ่มต้นคือ 15 วัน)
            $table->integer('late_fee_grace_days')->default(15)->after('invoice_due_day')->comment('ระยะเวลาผ่อนผัน (วัน)');
            
        });
    }

    public function down()
    {
        Schema::table('apartment', function (Blueprint $table) {
            $table->dropColumn(['invoice_due_day', 'late_fee_grace_days']);
        });
    }
};
