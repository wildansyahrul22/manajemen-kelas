<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Files attached to a tugas (soal, template laporan, …), same shape as informasi_lampiran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas_lampiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $table->string('path');
            $table->string('nama');
            $table->unsignedInteger('ukuran')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas_lampiran');
    }
};
