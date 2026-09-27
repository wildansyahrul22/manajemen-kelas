<?php

namespace Tests\Feature;

use App\Models\Informasi;
use App\Models\InformasiLampiran;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\Tugas;
use App\Models\TugasLampiran;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Records that appear in URLs are addressed by a random ULID. Old keys (the auto-increment id, a
 * replaced ULID in ulid_lama) redirect only for someone allowed to see the record.
 */
class UlidRouteKeyTest extends TestCase
{
    /** A ULID from the first, sequential backfill, as it may still sit in shared links. */
    private const string ULID_LAMA = '01k5nq3r7ats8tabcdefghjkmn';

    private const string ULID_LAMA_LAMPIRAN = '01k5nq3r7ats8vabcdefghjkmn';

    public function test_detail_pages_are_reached_by_ulid(): void
    {
        $kelas = $this->kelas();
        $admin = $this->admin($kelas);

        foreach ($this->halamanDetail($kelas) as [$route, $model, $prefix]) {
            $this->assertTrue(Str::isUlid($model->ulid), "{$route}: ulid belum terisi");
            $this->assertSame(url($prefix.$model->ulid), route($route, $model), "{$route}: URL tidak memakai ulid");

            $this->actingAs($admin)->get(route($route, $model))->assertOk();
            $this->actingAs($admin)->get($prefix.strtolower((string) Str::ulid()))->assertNotFound();
            $this->actingAs($admin)->get($prefix.'bukan-ulid')->assertNotFound();
        }
    }

    public function test_an_old_id_link_redirects_to_the_ulid_url_for_someone_who_may_view_the_record(): void
    {
        $kelas = $this->kelas();
        $admin = $this->admin($kelas);

        foreach ($this->halamanDetail($kelas) as [$route, $model, $prefix]) {
            $this->actingAs($admin)->get($prefix.$model->id)
                ->assertStatus(301)
                ->assertRedirect(route($route, $model));
        }
    }

    public function test_an_old_id_link_is_not_found_for_a_member_of_another_kelas(): void
    {
        $kelas = $this->kelas();
        $adminKelasLain = $this->admin($this->kelas());

        foreach ($this->halamanDetail($kelas) as [$route, $model, $prefix]) {
            $this->actingAs($adminKelasLain)->get($prefix.$model->id)->assertNotFound();
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
    }

    public function test_an_old_attachment_link_redirects_one_key_at_a_time_and_keeps_the_query_string(): void
    {
        $kelas = $this->kelas();
        $mahasiswa = $this->mahasiswa($kelas);
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $kelas->id])->id]);
        $lampiran = TugasLampiran::factory()->create(['tugas_id' => $tugas->id]);

        $this->actingAs($mahasiswa)->get("/tugas/{$tugas->id}/lampiran/{$lampiran->id}?unduh=1")
            ->assertStatus(301)
            ->assertRedirect("/tugas/{$tugas->ulid}/lampiran/{$lampiran->id}?unduh=1");

        $this->actingAs($mahasiswa)->get("/tugas/{$tugas->ulid}/lampiran/{$lampiran->id}?unduh=1")
            ->assertStatus(301)
            ->assertRedirect("/tugas/{$tugas->ulid}/lampiran/{$lampiran->ulid}?unduh=1");
    }

    public function test_an_old_attachment_link_is_not_found_outside_its_kelas_or_under_another_parent(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id]);
        $lampiran = InformasiLampiran::factory()->create(['informasi_id' => $informasi->id]);
        $tugasLain = Tugas::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);
        $lampiranTugasLain = TugasLampiran::factory()->create(['tugas_id' => $tugasLain->id]);
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);

        $this->actingAs($this->mahasiswa($this->kelas()))
            ->get("/informasi/{$informasi->ulid}/lampiran/{$lampiran->id}")
            ->assertNotFound();

        $this->actingAs($this->mahasiswa($kelas))
            ->get("/tugas/{$tugas->ulid}/lampiran/{$lampiranTugasLain->id}")
            ->assertNotFound();
    }

    public function test_a_replaced_ulid_redirects_to_the_current_one_only_within_the_kelas(): void
    {
        $kelas = $this->kelas();
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $kelas->id])->id]);
        $lampiran = TugasLampiran::factory()->create(['tugas_id' => $tugas->id]);
        DB::table('tugas')->where('id', $tugas->id)->update(['ulid_lama' => self::ULID_LAMA]);
        DB::table('tugas_lampiran')->where('id', $lampiran->id)->update(['ulid_lama' => self::ULID_LAMA_LAMPIRAN]);

        $this->actingAs($this->mahasiswa($kelas))->get('/tugas/'.self::ULID_LAMA)
            ->assertStatus(301)
            ->assertRedirect(route('tugas.show', $tugas));

        $this->actingAs($this->mahasiswa($kelas))->get("/tugas/{$tugas->ulid}/lampiran/".self::ULID_LAMA_LAMPIRAN)
            ->assertStatus(301)
            ->assertRedirect(route('tugas.lampiran', [$tugas, $lampiran]));

        $this->actingAs($this->mahasiswa($this->kelas()))->get('/tugas/'.self::ULID_LAMA)->assertNotFound();
    }

    public function test_new_rows_get_unrelated_random_ulids_from_the_model(): void
    {
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id]);
        $tugas = Tugas::factory()->count(5)->create(['mata_kuliah_id' => $mataKuliah->id]);

        $this->assertSame($tugas[0]->ulid, $tugas[0]->fresh()->ulid);
        $this->assertSame('ulid', $tugas[0]->getRouteKeyName());
        $this->assertTrue($tugas[0]->getIncrementing(), 'id tetap auto-increment');
        $tugas->each(fn (Tugas $satu) => $this->assertTrue(Str::isUlid($satu->ulid)));
        $this->assertCount(5, $tugas->map(fn (Tugas $satu) => substr($satu->ulid, 0, 8))->unique(), 'ULID berbasis waktu yang dibuat dalam satu detik berawalan sama');
    }

    public function test_a_record_loaded_without_its_ulid_still_links_by_ulid_in_production_and_reports_it(): void
    {
        Exceptions::fake();
        Model::preventAccessingMissingAttributes(false);

        $tugas = Tugas::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id])->id]);
        $tanpaUlid = Tugas::query()->select(['id', 'nama'])->findOrFail($tugas->id);

        $this->assertSame(url('/tugas/'.$tugas->ulid), route('tugas.show', $tanpaUlid));
        Exceptions::assertReported(MissingAttributeException::class);
    }

    public function test_a_record_loaded_without_its_ulid_fails_loudly_outside_production(): void
    {
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id])->id]);
        $tanpaUlid = Tugas::query()->select(['id', 'nama'])->findOrFail($tugas->id);

        $this->expectException(MissingAttributeException::class);

        route('tugas.show', $tanpaUlid);
    }

    /**
     * One record for every detail page, all in the given kelas.
     *
     * @return list<array{0: string, 1: Model, 2: string}>
     */
    private function halamanDetail(Kelas $kelas): array
    {
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);

        return [
            ['tugas.show', Tugas::factory()->create(['mata_kuliah_id' => $mataKuliah->id]), '/tugas/'],
            ['mata-kuliah.show', $mataKuliah, '/mata-kuliah/'],
            ['informasi.show', Informasi::factory()->create(['kelas_id' => $kelas->id]), '/informasi/'],
            ['kelompok.show', Kelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id]), '/kelompok/'],
            ['users.show', $this->mahasiswa($kelas), '/users/'],
        ];
    }
}
