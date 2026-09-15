<?php

namespace Tests\Feature;

use App\Livewire\Informasi\Index as InformasiIndex;
use App\Models\Informasi;
use App\Support\BatasUnggah;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BatasUnggahTest extends TestCase
{
    #[TestWith(['6M', 6291456])]
    #[TestWith(['512K', 524288])]
    #[TestWith(['1G', 1073741824])]
    #[TestWith(['2048', 2048])]
    #[TestWith(['1.5M', 1572864])]
    #[TestWith(['0', null])]
    #[TestWith(['-1', null])]
    #[TestWith(['', null])]
    #[TestWith(['abc', null])]
    public function test_php_ini_shorthand_values_are_converted_to_bytes(string $nilai, ?int $bytes): void
    {
        $this->assertSame($bytes, BatasUnggah::shorthandBytes($nilai));
    }

    public function test_per_file_limit_never_exceeds_the_application_cap(): void
    {
        $appBytes = Informasi::LAMPIRAN_MAKS_KB * 1024;
        $server = BatasUnggah::iniBytes('upload_max_filesize');

        $this->assertLessThanOrEqual($appBytes, BatasUnggah::perFileBytes());
        $this->assertSame($server === null ? $appBytes : min($appBytes, $server), BatasUnggah::perFileBytes());
        $this->assertSame(BatasUnggah::perFileBytes() < $appBytes, BatasUnggah::dibatasiServer());
    }

    public function test_mb_is_formatted_for_indonesian_readers(): void
    {
        $this->assertSame('5', BatasUnggah::mb(5 * 1024 * 1024));
        $this->assertSame('1,5', BatasUnggah::mb(1572864));
        $this->assertSame('0,3', BatasUnggah::mb(300 * 1024));
    }

    public function test_form_hint_shows_the_effective_limit_and_warns_when_the_server_is_stricter(): void
    {
        $kelas = $this->kelas();
        $perFile = BatasUnggah::mb(BatasUnggah::perFileBytes());

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->assertSee("Maks. {$perFile} MB per file, 5 file per informasi");

        BatasUnggah::dibatasiServer()
            ? $component->assertSee("Server saat ini hanya menerima {$perFile} MB per file")
            : $component->assertDontSee('Server saat ini hanya menerima');
    }
}
