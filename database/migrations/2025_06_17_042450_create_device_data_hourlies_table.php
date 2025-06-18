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
        Schema::create('device_data_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->timestamp('hour_timestamp')->comment('Awal jam, cth: 2025-06-17 13:00:00');
            $table->float('watt_avg');
            $table->float('voltage_avg');
            $table->float('current_avg');
            $table->float('temperature_avg');
            $table->float('kwh_total', 10, 4)->comment('Total kWh dalam jam tersebut');

            // Kunci unik untuk mencegah duplikasi data per jam per perangkat
            $table->unique(['device_id', 'hour_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_data_hourlies');
    }
};
