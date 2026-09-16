<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategori_kelompok', function (Blueprint $table) {
            // Locked kategori: nobody may change its kelompok until an admin kelas unlocks it again.
            $table->boolean('final')->default(false)->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('kategori_kelompok', function (Blueprint $table) {
            $table->dropColumn('final');
        });
    }
};
