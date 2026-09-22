<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Models\Informasi;
use App\Models\JadwalKelas;
use App\Models\JadwalLab;
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
            'mahasiswa' => User::query()->forKelas($kelas->id)->anggotaKelas()->aktifDiSemester($kelas->semester_aktif_id)->count(),
            'mata_kuliah' => MataKuliah::query()->forKelasAktif($kelas)->count(),
            'tugas_aktif' => Tugas::query()->forKelasAktif($kelas)->belumDeadline()->count(),
            'jadwal_hari_ini' => $this->jadwalHariIni->count(),
            'kelompok' => Kelompok::query()->forKelasAktif($kelas)->count(),
            'informasi' => Informasi::query()->forKelas($kelas->id)->count(),
        ];
    }

    /**
     * Today's kelas sessions and lab sessions merged into one timeline.
     *
     * @return Collection<int, JadwalKelas|JadwalLab>
     */
    #[Computed]
    public function jadwalHariIni(): Collection
    {
        $jadwalKelas = JadwalKelas::query()
            ->select(['id', 'mata_kuliah_id', 'hari', 'jam_mulai', 'jam_selesai', 'ruangan'])
            ->with('mataKuliah:id,nama,dosen')
            ->forKelasAktif($this->kelas)
            ->hariIni()
            ->get();

        $jadwalLab = JadwalLab::query()
            ->select(['id', 'mata_kuliah_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'ruangan', 'keterangan'])
            ->with('mataKuliah:id,nama,dosen')
            ->forKelasAktif($this->kelas)
            ->hariIni()
            ->get();

        return $jadwalKelas->concat($jadwalLab)
            ->sortBy(fn (JadwalKelas|JadwalLab $jadwal) => $jadwal->jam_mulai->format('H:i'))
            ->values();
    }

    #[Computed]
    public function tugasTerdekat(): Collection
    {
        return Tugas::query()
            ->select(['id', 'ulid', 'mata_kuliah_id', 'nama', 'deadline', 'link_pengumpulan'])
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
            ->select(['id', 'ulid', 'kategori_informasi_id', 'judul', 'is_pinned', 'created_at'])
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
