<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informasi', function (Blueprint $table) {
            $table->string('link', 2048)->nullable()->after('isi');
            $table->string('lampiran_path')->nullable()->after('link');
            $table->string('lampiran_nama')->nullable()->after('lampiran_path');
        });
    }

    public function down(): void
    {
        Schema::table('informasi', function (Blueprint $table) {
            $table->dropColumn(['link', 'lampiran_path', 'lampiran_nama']);
        });
    }
};
