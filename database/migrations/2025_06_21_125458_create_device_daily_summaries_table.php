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
        Schema::create('device_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->date('summary_date');
            $table->unsignedInteger('samples_count');
            $table->decimal('avg_watt', 10, 2);
            $table->decimal('min_watt', 10, 2);
            $table->decimal('max_watt', 10, 2);
            $table->decimal('avg_temperature', 5, 2);
            $table->decimal('avg_voltage', 6, 2);
            $table->decimal('avg_current', 6, 2);
            $table->decimal('total_kwh', 12, 4);
            $table->unique(['device_id', 'summary_date'], 'daily_summary_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_daily_summaries');
    }
};
