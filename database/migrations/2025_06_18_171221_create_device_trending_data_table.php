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
        Schema::create('device_trending_data', function (Blueprint $table) {
            $table->foreignId('device_id')->primary()->constrained()->cascadeOnDelete();
            $table->decimal('last_24h_kwh', 10, 4);
            $table->decimal('last_7d_kwh', 10, 4);
            $table->decimal('last_30d_kwh', 10, 4);
            $table->decimal('current_efficiency', 5, 2)->nullable();
            $table->timestamp('last_updated')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_trending_data');
    }
};