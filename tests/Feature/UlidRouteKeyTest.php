<?php

namespace Tests\Feature;

use App\Models\Informasi;
use App\Models\InformasiLampiran;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\Tugas;
use App\Models\TugasLampiran;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Records that appear in URLs are addressed by a ULID, never by their auto-increment id.
 */
class UlidRouteKeyTest extends TestCase
{
    private const string MIGRASI_ULID = 'database/migrations/2026_09_22_000002_add_ulid_to_routed_tables.php';

    public function test_detail_pages_are_reached_by_ulid_and_not_by_id(): void
    {
        $kelas = $this->kelas();
        $admin = $this->admin($kelas);
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id]);
        $kelompok = Kelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);
        $mahasiswa = $this->mahasiswa($kelas);

        $halaman = [
            ['tugas.show', $tugas, '/tugas/'],
            ['mata-kuliah.show', $mataKuliah, '/mata-kuliah/'],
            ['informasi.show', $informasi, '/informasi/'],
            ['kelompok.show', $kelompok, '/kelompok/'],
            ['users.show', $mahasiswa, '/users/'],
        ];

        foreach ($halaman as [$route, $model, $prefix]) {
            $this->assertTrue(Str::isUlid($model->ulid), "{$route}: ulid belum terisi");
            $this->assertSame(url($prefix.$model->ulid), route($route, $model), "{$route}: URL tidak memakai ulid");

            $this->actingAs($admin)->get(route($route, $model))->assertOk();
            $this->actingAs($admin)->get($prefix.$model->id)->assertNotFound();
            $this->actingAs($admin)->get($prefix.strtolower((string) Str::ulid()))->assertNotFound();
        }
    }

    public function test_attachments_are_reached_by_the_ulid_of_both_parent_and_file(): void
    {
        Storage::fake(Informasi::LAMPIRAN_DISK);

        $kelas = $this->kelas();
        $mahasiswa = $this->mahasiswa($kelas);
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id]);
        $lampiranInformasi = InformasiLampiran::factory()->create(['informasi_id' => $informasi->id]);
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $kelas->id])->id]);
        $lampiranTugas = TugasLampiran::factory()->create(['tugas_id' => $tugas->id]);
        Storage::disk(Informasi::LAMPIRAN_DISK)->put($lampiranInformasi->path, 'x');
        Storage::disk(Tugas::LAMPIRAN_DISK)->put($lampiranTugas->path, 'x');

        $this->assertSame(url("/informasi/{$informasi->ulid}/lampiran/{$lampiranInformasi->ulid}"), route('informasi.lampiran', [$informasi, $lampiranInformasi]));
        $this->assertSame(url("/tugas/{$tugas->ulid}/lampiran/{$lampiranTugas->ulid}"), route('tugas.lampiran', [$tugas, $lampiranTugas]));

        $this->actingAs($mahasiswa)->get(route('informasi.lampiran', [$informasi, $lampiranInformasi]))->assertOk();
        $this->actingAs($mahasiswa)->get(route('tugas.lampiran', [$tugas, $lampiranTugas]))->assertOk();

        $this->actingAs($mahasiswa)->get("/informasi/{$informasi->id}/lampiran/{$lampiranInformasi->id}")->assertNotFound();
        $this->actingAs($mahasiswa)->get("/informasi/{$informasi->ulid}/lampiran/{$lampiranInformasi->id}")->assertNotFound();
        $this->actingAs($mahasiswa)->get("/tugas/{$tugas->id}/lampiran/{$lampiranTugas->id}")->assertNotFound();
        $this->actingAs($mahasiswa)->get("/tugas/{$tugas->ulid}/lampiran/{$lampiranTugas->id}")->assertNotFound();
    }

    public function test_the_migration_backfills_a_unique_ulid_on_existing_rows(): void
    {
        $kelas = $this->kelas();
        $semesterId = DB::table('semesters')->where('nomor', 3)->value('id');

        Artisan::call('migrate:rollback', ['--path' => self::MIGRASI_ULID]);

        DB::table('mata_kuliah')->insert([
            ['kelas_id' => $kelas->id, 'semester_id' => $semesterId, 'nama' => 'Basis Data', 'dosen' => 'Dr. Andi', 'sks' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['kelas_id' => $kelas->id, 'semester_id' => $semesterId, 'nama' => 'Jaringan', 'dosen' => 'Dr. Budi', 'sks' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Artisan::call('migrate');

        $ulid = DB::table('mata_kuliah')->orderBy('id')->pluck('ulid');

        $this->assertCount(2, $ulid);
        $this->assertCount(2, $ulid->unique());
        $ulid->each(fn (string $nilai) => $this->assertTrue(Str::isUlid($nilai)));

        // The kelas row itself is untouched: kelas never appears in a URL.
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('kelas', 'ulid'));
    }

    public function test_new_rows_get_a_ulid_from_the_model(): void
    {
        $kelas = $this->kelas();
        $user = User::factory()->create(['kelas_id' => $kelas->id]);

        $this->assertTrue(Str::isUlid($user->ulid));
        $this->assertSame($user->ulid, $user->fresh()->ulid);
        $this->assertSame('ulid', $user->getRouteKeyName());
        $this->assertTrue($user->getIncrementing(), 'id tetap auto-increment');
    }
}
