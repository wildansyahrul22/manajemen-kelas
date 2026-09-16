<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_unknown_url_shows_an_indonesian_not_found_page(): void
    {
        $this->actingAs($this->mahasiswa($this->kelas()))
            ->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Ke dashboard')
            ->assertDontSee('Not Found', false);
    }

    public function test_forbidden_page_shows_our_reason_but_never_the_framework_wording(): void
    {
        $kelas = $this->kelas();

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('users.index'))
            ->assertForbidden()
            ->assertSee('Akses tidak diizinkan')
            ->assertSee('Anda tidak memiliki akses ke halaman ini.')
            ->assertDontSee('This action is unauthorized', false)
            ->assertDontSee('Forbidden', false);

        $tanpaKelas = User::factory()->create(['role' => Role::Mahasiswa, 'kelas_id' => null]);

        $this->actingAs($tanpaKelas)
            ->get(route('dashboard'))
            ->assertForbidden()
            ->assertSee('Akun Anda belum terhubung ke kelas mana pun. Hubungi admin.');
    }

    public function test_generic_forbidden_gets_a_plain_indonesian_message(): void
    {
        $html = view('errors.403', ['exception' => new HttpException(403, 'This action is unauthorized.')])->render();

        $this->assertStringContainsString('Anda tidak memiliki akses ke halaman atau aksi ini.', $html);
        $this->assertStringNotContainsString('This action is unauthorized', $html);
    }

    #[TestWith(['419', 'Sesi sudah berakhir', 'Muat ulang'])]
    #[TestWith(['429', 'Terlalu banyak permintaan', 'Muat ulang'])]
    #[TestWith(['500', 'Terjadi kesalahan', 'Muat ulang'])]
    #[TestWith(['503', 'Sedang dalam pemeliharaan', 'Muat ulang'])]
    #[TestWith(['401', 'Perlu masuk terlebih dahulu', 'Masuk'])]
    public function test_other_error_pages_are_plain_indonesian(string $kode, string $judul, string $tombol): void
    {
        $html = view("errors.{$kode}", ['exception' => new HttpException((int) $kode)])->render();

        $this->assertStringContainsString($judul, $html);
        $this->assertStringContainsString($tombol, $html);
        $this->assertStringContainsString("Kode {$kode}", $html);

        foreach (['Server Error', 'Page Expired', 'Too Many Requests', 'Service Unavailable', 'Unauthorized', 'stack', 'exception'] as $teknis) {
            $this->assertStringNotContainsString($teknis, strip_tags($html));
        }
    }
}
