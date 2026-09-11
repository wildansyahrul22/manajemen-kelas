<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_informasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->string('nama', 50);
            $table->string('warna', 20)->default('slate');
            $table->timestamps();

            $table->unique(['kelas_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_informasi');
    }
};
