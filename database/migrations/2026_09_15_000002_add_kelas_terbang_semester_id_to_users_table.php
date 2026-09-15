<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Kelas terbang": a student who joins this kelas for one semester only. Null = regular member.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelas_terbang_semester_id')->nullable()->after('kelas_id')->constrained('semesters')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_terbang_semester_id');
        });
    }
};
