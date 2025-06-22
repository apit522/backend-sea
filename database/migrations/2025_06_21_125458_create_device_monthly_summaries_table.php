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
        Schema::create('device_monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('summary_year');
            $table->unsignedTinyInteger('summary_month');
            $table->decimal('avg_watt', 10, 2);
            $table->decimal('total_kwh', 12, 4);
            $table->decimal('peak_watt', 10, 2);
            $table->decimal('avg_temperature', 5, 2);
            $table->unique(['device_id', 'summary_year', 'summary_month'], 'monthly_summary_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_monthly_summaries');
    }
};
