<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Uid\Ulid;

/**
 * Every model that appears in a URL (detail pages, attachment downloads) gets a ULID as its route
 * key, so links can no longer be enumerated by counting up the auto-increment id. Existing rows
 * are backfilled here with 128 random bits, like RoutesByUlid does for new rows.
 *
 * MySQL commits every schema change on its own, so each step checks what is already done and a
 * failed run can simply be run again. bin/deploy.sh keeps the site in maintenance mode meanwhile,
 * so no row is inserted between the backfill and the NOT NULL change.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const array TABEL = ['users', 'mata_kuliah', 'tugas', 'tugas_lampiran', 'informasi', 'informasi_lampiran', 'kelompok'];

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            if (! Schema::hasColumn($tabel, 'ulid')) {
                Schema::table($tabel, function (Blueprint $table) {
                    $table->ulid('ulid')->nullable()->unique()->after('id');
                });
            }

            DB::table($tabel)->select('id')->whereNull('ulid')->chunkById(500, function (Collection $baris) use ($tabel) {
                DB::transaction(function () use ($baris, $tabel) {
                    foreach ($baris as $satu) {
                        DB::table($tabel)->where('id', $satu->id)->update(['ulid' => strtolower((string) Ulid::fromBinary(random_bytes(16)))]);
                    }
                });
            });

            Schema::table($tabel, function (Blueprint $table) {
                $table->ulid('ulid')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropUnique(['ulid']);
                $table->dropColumn('ulid');
            });
        }
    }
};
