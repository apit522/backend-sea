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
        Schema::table('devices', function (Blueprint $table) {
            // Daya listrik dalam VA (Volt-Ampere)
            $table->integer('daya_va')->default(900)->after('btu');
            // Tarif per kWh dalam Rupiah
            $table->decimal('tarif_per_kwh', 8, 2)->default(1444.70)->after('daya_va');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['daya_va', 'tarif_per_kwh']);
        });
    }
};
