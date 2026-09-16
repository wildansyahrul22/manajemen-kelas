<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Tugas kelompok": the tugas is done per kelompok of one kategori kelompok (same mata kuliah).
 * Null = individual tugas. Deleting the kategori turns the tugas back into an individual one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas', function (Blueprint $table) {
            $table->foreignId('kategori_kelompok_id')->nullable()->after('mata_kuliah_id')->constrained('kategori_kelompok')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tugas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kategori_kelompok_id');
        });
    }
};
