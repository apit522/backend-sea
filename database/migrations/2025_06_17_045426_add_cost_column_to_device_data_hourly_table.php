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
        Schema::table('device_data_hourly', function (Blueprint $table) {
            // Tambahkan kolom untuk total biaya dalam Rupiah
            $table->decimal('cost_total', 15, 2)->default(0)->after('kwh_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_data_hourly', function (Blueprint $table) {
            $table->dropColumn('cost_total');
        });
    }
};
