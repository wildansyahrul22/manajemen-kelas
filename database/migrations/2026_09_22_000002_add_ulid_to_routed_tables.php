<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Every model that appears in a URL (detail pages, attachment downloads) gets a ULID as its route
 * key, so links can no longer be enumerated by counting up the auto-increment id. Existing rows
 * are backfilled here; new rows get theirs from the model (RoutesByUlid).
 */
return new class extends Migration
{
    /** @var list<string> */
    private const array TABEL = ['users', 'mata_kuliah', 'tugas', 'tugas_lampiran', 'informasi', 'informasi_lampiran', 'kelompok'];

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->char('ulid', 26)->nullable()->unique()->after('id');
            });

            DB::table($tabel)->select('id')->orderBy('id')->each(function (object $baris) use ($tabel) {
                DB::table($tabel)->where('id', $baris->id)->update(['ulid' => strtolower((string) Str::ulid())]);
            });

            Schema::table($tabel, function (Blueprint $table) {
                $table->char('ulid', 26)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) use ($tabel) {
                $table->dropUnique("{$tabel}_ulid_unique");
                $table->dropColumn('ulid');
            });
        }
    }
};
