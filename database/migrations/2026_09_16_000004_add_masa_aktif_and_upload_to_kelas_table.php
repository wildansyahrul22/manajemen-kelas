<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            // Subscription window; both null means the kelas never expires.
            $table->date('masa_aktif_mulai')->nullable()->after('semester_aktif_id');
            $table->date('masa_aktif_selesai')->nullable()->after('masa_aktif_mulai');
            // Whether the upload features of the app are part of this kelas' plan.
            $table->boolean('upload')->default(true)->after('masa_aktif_selesai');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropColumn(['masa_aktif_mulai', 'masa_aktif_selesai', 'upload']);
        });
    }
};
