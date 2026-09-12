<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['kategori_informasi', 'kategori_kelompok'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('nama')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['kategori_informasi', 'kategori_kelompok'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
