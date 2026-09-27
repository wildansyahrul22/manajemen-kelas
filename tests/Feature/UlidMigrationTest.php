<?php

namespace Tests\Feature;

use App\Models\Informasi;
use App\Models\InformasiLampiran;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\Tugas;
use App\Models\TugasLampiran;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The migrations that give every routed table its ULID. They change the schema inside the test's
 * transaction, which only SQLite (the test database) rolls back; MySQL would commit the test data.
 *
 * SQLite applies ->change() by rebuilding the table, and inside a transaction it cannot switch
 * foreign keys off for that, so dropping the old table would cascade-delete every child row.
 * MySQL alters in place; foreign keys are off during these tests to match.
 */
class UlidMigrationTest extends TestCase
{
    private const string MIGRASI_TAMBAH = 'database/migrations/2026_09_22_000002_add_ulid_to_routed_tables.php';

    private const string MIGRASI_ACAK = 'database/migrations/2026_09_27_000001_regenerate_ulid_on_routed_tables.php';

    /** @var list<string> */
    private const array TABEL = ['users', 'mata_kuliah', 'tugas', 'tugas_lampiran', 'informasi', 'informasi_lampiran', 'kelompok'];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Migrasi di dalam transaksi test hanya aman di SQLite.');
        }
    }

    public function beginDatabaseTransaction(): void
    {
        Schema::disableForeignKeyConstraints();

        parent::beginDatabaseTransaction();

        $this->beforeApplicationDestroyed(fn () => Schema::enableForeignKeyConstraints());
    }

    public function test_adding_the_column_gives_every_existing_row_a_random_required_ulid_and_rolls_back(): void
    {
        $this->barisDiSetiapTabel();

        Artisan::call('migrate:rollback', ['--path' => self::MIGRASI_TAMBAH]);

        foreach (self::TABEL as $tabel) {
            $this->assertFalse(Schema::hasColumn($tabel, 'ulid'), "{$tabel}: ulid tidak dihapus saat rollback");
        }

        Artisan::call('migrate', ['--path' => self::MIGRASI_TAMBAH]);

        foreach (self::TABEL as $tabel) {
            $this->assertNotEmpty(DB::table($tabel)->pluck('ulid'), "{$tabel}: baris hilang");
            DB::table($tabel)->pluck('ulid')->each(fn (string $ulid) => $this->assertTrue(Str::isUlid($ulid), "{$tabel}: {$ulid}"));
            $this->assertFalse(collect(Schema::getColumns($tabel))->firstWhere('name', 'ulid')['nullable'], "{$tabel}: ulid masih boleh null");
        }

        $semua = collect(self::TABEL)->flatMap(fn (string $tabel) => DB::table($tabel)->pluck('ulid'));
        $this->assertCount($semua->count(), $semua->map(fn (string $ulid) => substr($ulid, 0, 8))->unique(), 'ULID hasil backfill berurutan');
        $this->assertFalse(Schema::hasColumn('kelas', 'ulid'), 'kelas tidak pernah muncul di URL');
    }

    public function test_regenerating_moves_every_ulid_to_ulid_lama_and_rollback_puts_it_back(): void
    {
        $this->barisDiSetiapTabel();
        Artisan::call('migrate:rollback', ['--path' => self::MIGRASI_ACAK]);
        $sebelum = $this->ulidPerTabel();

        Artisan::call('migrate', ['--path' => self::MIGRASI_ACAK]);

        foreach (self::TABEL as $tabel) {
            $this->assertNotEmpty($sebelum[$tabel], "{$tabel}: tidak ada baris untuk diuji");

            foreach (DB::table($tabel)->get(['id', 'ulid', 'ulid_lama']) as $baris) {
                $this->assertSame($sebelum[$tabel][$baris->id], $baris->ulid_lama, "{$tabel}: ulid_lama");
                $this->assertNotSame($baris->ulid_lama, $baris->ulid, "{$tabel}: ulid tidak diganti");
                $this->assertTrue(Str::isUlid($baris->ulid), "{$tabel}: {$baris->ulid}");
            }
        }

        Artisan::call('migrate:rollback', ['--path' => self::MIGRASI_ACAK]);

        $this->assertSame($sebelum, $this->ulidPerTabel());
        $this->assertFalse(Schema::hasColumn('tugas', 'ulid_lama'));
    }

    public function test_both_migrations_run_again_after_a_partial_run_without_touching_finished_rows(): void
    {
        $this->barisDiSetiapTabel();
        $tugasSelesai = Tugas::query()->firstOrFail();
        $tugasBelum = Tugas::factory()->create(['mata_kuliah_id' => $tugasSelesai->mata_kuliah_id]);
        DB::table('tugas')->where('id', $tugasSelesai->id)->update(['ulid_lama' => '01k5nq3r7ats8tabcdefghjkmn']);
        $sebelum = $this->ulidPerTabel();

        DB::table('migrations')->whereIn('migration', ['2026_09_22_000002_add_ulid_to_routed_tables', '2026_09_27_000001_regenerate_ulid_on_routed_tables'])->delete();
        Artisan::call('migrate', ['--path' => self::MIGRASI_TAMBAH]);
        Artisan::call('migrate', ['--path' => self::MIGRASI_ACAK]);

        $this->assertSame($sebelum['tugas'][$tugasSelesai->id], DB::table('tugas')->where('id', $tugasSelesai->id)->value('ulid'), 'baris yang sudah selesai diacak ulang');
        $this->assertSame($sebelum['tugas'][$tugasBelum->id], DB::table('tugas')->where('id', $tugasBelum->id)->value('ulid_lama'));
    }

    /**
     * One row in each routed table, all in one kelas.
     */
    private function barisDiSetiapTabel(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id]);

        $this->mahasiswa($kelas);
        TugasLampiran::factory()->create(['tugas_id' => $tugas->id]);
        InformasiLampiran::factory()->create(['informasi_id' => $informasi->id]);
        Kelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function ulidPerTabel(): array
    {
        return collect(self::TABEL)->mapWithKeys(fn (string $tabel) => [$tabel => DB::table($tabel)->pluck('ulid', 'id')->all()])->all();
    }
}
