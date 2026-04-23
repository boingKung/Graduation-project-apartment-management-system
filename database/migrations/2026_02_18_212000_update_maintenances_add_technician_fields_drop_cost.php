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
        Schema::table('maintenances', function (Blueprint $table) {
            if (!Schema::hasColumn('maintenances', 'technician_name')) {
                $table->string('technician_name')->nullable()->after('status');
            }
            if (!Schema::hasColumn('maintenances', 'technician_time')) {
                $table->dateTime('technician_time')->nullable()->after('technician_name');
            }
            if (Schema::hasColumn('maintenances', 'cost')) {
                $table->dropColumn('cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('maintenances', 'technician_name')) {
                $table->dropColumn('technician_name');
            }
            if (Schema::hasColumn('maintenances', 'technician_time')) {
                $table->dropColumn('technician_time');
            }
            if (!Schema::hasColumn('maintenances', 'cost')) {
                $table->decimal('cost', 10, 2)->default(0)->after('status');
            }
        });
    }
};
