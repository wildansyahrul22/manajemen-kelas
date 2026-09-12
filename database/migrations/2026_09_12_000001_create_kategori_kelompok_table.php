<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_kelompok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->string('nama', 100);
            $table->timestamps();

            $table->unique(['mata_kuliah_id', 'nama']);
        });

        Schema::table('kelompok', function (Blueprint $table) {
            $table->foreignId('kategori_kelompok_id')
                ->nullable()
                ->after('mata_kuliah_id')
                ->constrained('kategori_kelompok')
                ->cascadeOnDelete();
        });

        // Existing kelompok get a default "Umum" kategori on their mata kuliah so the column can be required.
        DB::table('kelompok')
            ->select('mata_kuliah_id')
            ->distinct()
            ->orderBy('mata_kuliah_id')
            ->pluck('mata_kuliah_id')
            ->each(function (int $mataKuliahId) {
                $kategoriId = DB::table('kategori_kelompok')->insertGetId([
                    'mata_kuliah_id' => $mataKuliahId,
                    'nama' => 'Umum',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('kelompok')
                    ->where('mata_kuliah_id', $mataKuliahId)
                    ->update(['kategori_kelompok_id' => $kategoriId]);
            });

        Schema::table('kelompok', function (Blueprint $table) {
            $table->foreignId('kategori_kelompok_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('kelompok', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kategori_kelompok_id');
        });

        Schema::dropIfExists('kategori_kelompok');
    }
};
