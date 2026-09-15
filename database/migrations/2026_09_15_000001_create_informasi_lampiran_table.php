<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One informasi can now carry several attachments. Existing single attachments are moved into the
 * new table before the old columns are dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informasi_lampiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informasi_id')->constrained('informasi')->cascadeOnDelete();
            $table->string('path');
            $table->string('nama');
            $table->unsignedInteger('ukuran')->nullable();
            $table->timestamps();
        });

        DB::table('informasi')
            ->whereNotNull('lampiran_path')
            ->orderBy('id')
            ->each(function (object $informasi) {
                DB::table('informasi_lampiran')->insert([
                    'informasi_id' => $informasi->id,
                    'path' => $informasi->lampiran_path,
                    'nama' => $informasi->lampiran_nama ?? basename($informasi->lampiran_path),
                    'ukuran' => null,
                    'created_at' => $informasi->updated_at ?? now(),
                    'updated_at' => $informasi->updated_at ?? now(),
                ]);
            });

        Schema::table('informasi', function (Blueprint $table) {
            $table->dropColumn(['lampiran_path', 'lampiran_nama']);
        });
    }

    public function down(): void
    {
        Schema::table('informasi', function (Blueprint $table) {
            $table->string('lampiran_path')->nullable()->after('link');
            $table->string('lampiran_nama')->nullable()->after('lampiran_path');
        });

        // Only the first attachment of each informasi fits the old single-column layout.
        DB::table('informasi_lampiran')
            ->orderBy('informasi_id')
            ->orderBy('id')
            ->get()
            ->unique('informasi_id')
            ->each(fn (object $lampiran) => DB::table('informasi')
                ->where('id', $lampiran->informasi_id)
                ->update(['lampiran_path' => $lampiran->path, 'lampiran_nama' => $lampiran->nama]));

        Schema::dropIfExists('informasi_lampiran');
    }
};
