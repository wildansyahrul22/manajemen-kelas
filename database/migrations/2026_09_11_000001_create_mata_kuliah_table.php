<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->restrictOnDelete();
            $table->string('kode', 20)->nullable();
            $table->string('nama', 100);
            $table->string('dosen', 100);
            $table->unsignedTinyInteger('sks')->default(2);
            $table->timestamps();

            $table->index(['kelas_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_kuliah');
    }
};
