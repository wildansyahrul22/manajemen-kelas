<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ImportMahasiswaTest extends TestCase
{
    public function test_roster_is_imported_with_npm_as_password_and_bad_rows_skipped(): void
    {
        $kelas = $this->kelas(1, ['nama' => 'TI-R8']);
        $this->mahasiswa($kelas, ['npm' => '202343502433']);

        $file = tempnam(sys_get_temp_dir(), 'roster').'.csv';
        file_put_contents($file, implode("\n", [
            'npm,nama,no_hp',
            '202343502433,SUDAH ADA,',
            '202343502434,AHMAD HANAFI,',
            '202343502436,"ASY-SYAHID ABDURRAHMAN",0812-3456-7890',
            '12345,NPM TERLALU PENDEK,',
            '',
        ]));

        $this->artisan('mahasiswa:import', ['file' => $file, '--kelas' => 'TI-R8'])
            ->expectsOutputToContain('2 mahasiswa ditambahkan ke kelas TI-R8 (2 dilewati).')
            ->assertSuccessful();

        $hanafi = User::query()->where('npm', '202343502434')->firstOrFail();

        $this->assertNotSame('SUDAH ADA', User::query()->where('npm', '202343502433')->value('name'));
        $this->assertDatabaseMissing('users', ['npm' => '12345']);

        unlink($file);
    }

    public function test_import_fails_for_unknown_kelas(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'roster').'.csv';
        file_put_contents($file, "npm,nama\n202343502434,AHMAD HANAFI\n");

        $this->artisan('mahasiswa:import', ['file' => $file, '--kelas' => 'TIDAK-ADA'])->assertFailed();

        unlink($file);
    }
}
