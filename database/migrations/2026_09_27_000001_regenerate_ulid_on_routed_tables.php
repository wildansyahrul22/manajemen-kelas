<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Uid\Ulid;

/**
 * The first deployed run of add_ulid_to_routed_tables filled existing rows with Str::ulid(), which
 * within one millisecond only adds 1 to the previous value: neighbouring rows got neighbouring
 * ULIDs that could be guessed. Every existing row now gets 128 random bits instead. The replaced
 * ULID is kept in ulid_lama, so links shared before still redirect (RoutesByUlid).
 *
 * Can be run again after a failure: rows that already have ulid_lama are skipped.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const array TABEL = ['users', 'mata_kuliah', 'tugas', 'tugas_lampiran', 'informasi', 'informasi_lampiran', 'kelompok'];

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            if (! Schema::hasColumn($tabel, 'ulid_lama')) {
                Schema::table($tabel, function (Blueprint $table) {
                    $table->ulid('ulid_lama')->nullable()->unique()->after('ulid');
                });
            }

            DB::table($tabel)->select(['id', 'ulid'])->whereNull('ulid_lama')->chunkById(500, function (Collection $baris) use ($tabel) {
                DB::transaction(function () use ($baris, $tabel) {
                    foreach ($baris as $satu) {
                        DB::table($tabel)->where('id', $satu->id)->update([
                            'ulid_lama' => $satu->ulid,
                            'ulid' => strtolower((string) Ulid::fromBinary(random_bytes(16))),
                        ]);
                    }
                });
            });
        }
    }

    /**
     * Puts the replaced ULIDs back, so links made with the new ones stop working.
     */
    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            DB::table($tabel)->whereNotNull('ulid_lama')->update(['ulid' => DB::raw('ulid_lama')]);

            Schema::table($tabel, function (Blueprint $table) {
                $table->dropUnique(['ulid_lama']);
                $table->dropColumn('ulid_lama');
            });
        }
    }
};
