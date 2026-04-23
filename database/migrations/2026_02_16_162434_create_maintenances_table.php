<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('room_id')->constrained()->onDelete('cascade');
            $blueprint->string('title');
            $blueprint->text('details')->nullable();
            $blueprint->string('status')->default('pending');
            $blueprint->string('technician_name')->nullable();
            $blueprint->dateTime('technician_time')->nullable();
            $blueprint->date('repair_date');
            $blueprint->date('finish_date')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
