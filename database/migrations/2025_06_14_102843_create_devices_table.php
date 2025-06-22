<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relasi ke tabel users
            $table->string('name'); // Nama AC yang diberikan pengguna
            $table->string('location')->nullable(); // Lokasi perangkat, opsional
            $table->string('unique_id')->unique(); // ID unik dari perangkat keras (ESP)
            $table->integer('btu')->nullable(); // BTU/jam, opsional
            $table->timestamp('last_seen_at')->nullable(); // Untuk cek status koneksi
            $table->integer('daya_va');
            $table->decimal('tarif_per_kwh', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('devices');
    }
};
