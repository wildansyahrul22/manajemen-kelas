<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Models\Informasi;
use App\Models\JadwalKelas;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    use InteractsWithKelas;

    /**
     * @return array{mahasiswa: int, mata_kuliah: int, tugas_aktif: int, jadwal_hari_ini: int, kelompok: int, informasi: int}
     */
    #[Computed]
    public function ringkasan(): array
    {
        $kelas = $this->kelas;

        return [
            'mahasiswa' => User::query()->forKelas($kelas->id)->anggotaKelas()->count(),
            'mata_kuliah' => MataKuliah::query()->forKelasAktif($kelas)->count(),
            'tugas_aktif' => Tugas::query()->forKelasAktif($kelas)->belumDeadline()->count(),
            'jadwal_hari_ini' => $this->jadwalHariIni->count(),
            'kelompok' => Kelompok::query()->forKelasAktif($kelas)->count(),
            'informasi' => Informasi::query()->forKelas($kelas->id)->count(),
        ];
    }

    #[Computed]
    public function jadwalHariIni(): Collection
    {
        return JadwalKelas::query()
            ->select(['id', 'mata_kuliah_id', 'hari', 'jam_mulai', 'jam_selesai', 'ruangan'])
            ->with('mataKuliah:id,nama,dosen')
            ->forKelasAktif($this->kelas)
            ->hariIni()
            ->orderBy('jam_mulai')
            ->get();
    }

    #[Computed]
    public function tugasTerdekat(): Collection
    {
        return Tugas::query()
            ->select(['id', 'mata_kuliah_id', 'nama', 'deadline'])
            ->with('mataKuliah:id,nama')
            ->forKelasAktif($this->kelas)
            ->belumDeadline()
            ->orderBy('deadline')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function informasiTerbaru(): Collection
    {
        return Informasi::query()
            ->select(['id', 'kategori_informasi_id', 'judul', 'is_pinned', 'created_at'])
            ->with('kategori:id,nama,warna')
            ->forKelas($this->kelas->id)
            ->terbaru()
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
