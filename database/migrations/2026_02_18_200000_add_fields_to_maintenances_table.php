<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (!Schema::hasColumn('maintenances', 'details')) {
                $table->text('details')->nullable()->after('description');
            }
            if (!Schema::hasColumn('maintenances', 'cost')) {
                $table->decimal('cost', 10, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('maintenances', 'repair_date')) {
                $table->date('repair_date')->nullable()->after('cost');
            }
            if (!Schema::hasColumn('maintenances', 'finish_date')) {
                $table->date('finish_date')->nullable()->after('repair_date');
            }
        });

        if (Schema::hasColumn('maintenances', 'repair_date')) {
            DB::statement("UPDATE maintenances SET repair_date = DATE(created_at) WHERE repair_date IS NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('maintenances', 'details')) {
                $columns[] = 'details';
            }
            if (Schema::hasColumn('maintenances', 'cost')) {
                $columns[] = 'cost';
            }
            if (Schema::hasColumn('maintenances', 'repair_date')) {
                $columns[] = 'repair_date';
            }
            if (Schema::hasColumn('maintenances', 'finish_date')) {
                $columns[] = 'finish_date';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
