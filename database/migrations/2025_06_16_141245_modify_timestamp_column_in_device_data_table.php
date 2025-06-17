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
        Schema::table('device_data', function (Blueprint $table) {
            // Hapus kolom created_at dan updated_at
            $table->dropColumn(['created_at', 'updated_at']);

            // Tambahkan kolom 'timestamp' baru
            // `useCurrent()` akan mengisinya dengan waktu saat ini jika tidak ada nilai yang diberikan,
            // tapi skrip Python Anda sudah handle ini dengan NOW().
            $table->timestamp('timestamp')->useCurrent()->after('current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_data', function (Blueprint $table) {
            // Kembalikan ke kondisi semula jika migrasi di-rollback
            $table->dropColumn('timestamp');
            $table->timestamps(); // Ini akan membuat kembali created_at dan updated_at
        });
    }
};