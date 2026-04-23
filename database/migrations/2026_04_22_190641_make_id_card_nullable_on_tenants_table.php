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
            // สั่งให้คอลัมน์เดิมสามารถเป็นค่าว่าง (null) ได้
            $table->string('id_card')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // ย้อนกลับเป็นบังคับกรอก (not null)
            $table->string('id_card')->nullable(false)->change();
        });
    }
};