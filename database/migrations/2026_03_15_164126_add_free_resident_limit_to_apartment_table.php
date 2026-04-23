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
            $table->integer('free_resident_limit')->default(2)->after('late_fee_grace_days')->comment('จำนวนคนพักสูงสุดที่ฟรีค่าใช้จ่าย');
        });
    }

    public function down()
    {
        Schema::table('apartment', function (Blueprint $table) {
            $table->dropColumn('free_resident_limit');
        });
    }
};
