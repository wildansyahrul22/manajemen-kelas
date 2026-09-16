<?php

namespace App\Support;

use App\Enums\Hari;
use App\Models\Informasi;
use App\Models\JadwalKelas;
use App\Models\JadwalLab;
use App\Models\KategoriKelompok;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Ready-to-paste WhatsApp messages (WhatsApp markup: *bold*, _italic_) for the share buttons.
 * Each builder expects its relations already loaded; url() wraps the text in a wa.me link that
 * opens WhatsApp with the message prefilled so the user only has to pick the chat.
 *
 * Emoji render fine on WhatsApp mobile; the desktop apps may show them as "?" (a WhatsApp bug).
 */
final class PesanWhatsApp
{
    private const int PANJANG_ISI_MAKS = 1500;

    public static function url(string $teks): string
    {
        return 'https://wa.me/?text='.rawurlencode($teks);
    }

    public static function informasi(Informasi $informasi, Kelas $kelas): string
    {
        $baris = [
            "📢 *INFORMASI KELAS {$kelas->nama}*",
            "Kategori: {$informasi->kategori->nama}".($informasi->is_pinned ? ' 📌' : ''),
            '',
            "*{$informasi->judul}*",
            Str::limit(trim($informasi->isi), self::PANJANG_ISI_MAKS, ' … (selengkapnya di aplikasi)'),
        ];

        $lampiran = $informasi->jumlahLampiran();

        if ($informasi->link || $lampiran > 0) {
            $baris[] = '';
        }

        if ($informasi->link) {
            $baris[] = "🔗 Tautan: {$informasi->link}";
        }

        if ($lampiran > 0) {
            $baris[] = "📎 {$lampiran} lampiran (buka di aplikasi, login dengan NPM)";
        }

        $baris[] = '';
        $baris[] = '👉 Selengkapnya: '.route('informasi.show', $informasi);
        $baris[] = "_Dibagikan dari aplikasi Kelas KampusKu {$kelas->nama}_";

        return implode("\n", $baris);
    }

    public static function tugas(Tugas $tugas, Kelas $kelas): string
    {
        $status = match ($tugas->status()) {
            'lewat' => 'sudah lewat',
            'segera' => 'segera! '.$tugas->sisaWaktu(),
            default => $tugas->sisaWaktu(),
        };

        $baris = [
            "📝 *TUGAS KELAS {$kelas->nama}*",
            "Mata kuliah: {$tugas->mataKuliah->nama} ({$tugas->mataKuliah->dosen})",
            '',
            "*{$tugas->nama}*",
            '⏰ Deadline: '.$tugas->deadline->isoFormat('dddd, D MMMM YYYY [pukul] HH:mm')." ({$status})",
        ];

        if ($tugas->isTugasKelompok()) {
            $baris[] = "👥 Tugas kelompok — kategori {$tugas->kategoriKelompok->nama} (cek kelompokmu di aplikasi)";
        }

        if (filled($tugas->deskripsi)) {
            $baris[] = '';
            $baris[] = Str::limit(trim($tugas->deskripsi), 1000, ' … (selengkapnya di aplikasi)');
        }

        $baris[] = '';

        if ($tugas->link_pengumpulan) {
            $baris[] = "📤 Kumpulkan di: {$tugas->link_pengumpulan}";
        }

        $baris[] = '👉 Detail tugas: '.route('tugas.show', $tugas);
        $baris[] = 'Jangan sampai terlewat ya 💪';

        return implode("\n", $baris);
    }

    /**
     * The whole week in one message; days without sessions are left out.
     *
     * @param  Collection<int, Collection<int, JadwalKelas>>  $jadwalPerHari  hari value => sessions (ordered), mataKuliah loaded
     */
    public static function jadwalKelasMingguan(Collection $jadwalPerHari, Kelas $kelas): string
    {
        $baris = [
            "📅 *JADWAL KELAS {$kelas->nama}*",
            $kelas->semesterAktif->nama,
        ];

        foreach ($jadwalPerHari as $hariValue => $daftar) {
            if ($daftar->isEmpty()) {
                continue;
            }

            $hari = Hari::from((int) $hariValue);
            $baris[] = '';
            $baris[] = '*'.Str::upper($hari->label()).'*'.($hari === Hari::today() ? ' (hari ini)' : '');

            foreach ($daftar->values() as $indeks => $sesi) {
                $baris[] = ($indeks + 1).'. '.$sesi->jam_mulai->format('H:i').'–'.$sesi->jam_selesai->format('H:i')." · {$sesi->mataKuliah->nama}".($sesi->ruangan ? " · 📍 {$sesi->ruangan}" : '');
            }
        }

        $baris[] = '';
        $baris[] = 'Simpan jadwalnya, jangan sampai salah ruangan 🙌';
        $baris[] = '👉 Jadwal lengkap: '.route('jadwal.index');

        return implode("\n", $baris);
    }

    /**
     * Every listed lab session of one mata kuliah, in date order.
     *
     * @param  Collection<int, JadwalLab>  $sesi  ordered by tanggal and jam mulai
     */
    public static function jadwalLabMataKuliah(MataKuliah $mataKuliah, Collection $sesi, Kelas $kelas): string
    {
        $baris = [
            '🔬 *JADWAL LAB '.Str::upper($mataKuliah->nama).'*',
            "Kelas {$kelas->nama} · {$mataKuliah->dosen}",
            '',
        ];

        foreach ($sesi->values() as $indeks => $lab) {
            $penanda = match (true) {
                $lab->isLewat() => ' ✅',
                $lab->isHariIni() => ' (hari ini)',
                default => '',
            };

            $baris[] = ($indeks + 1).'. '.$lab->tanggal->isoFormat('ddd, D MMM YYYY').' · '.$lab->jam_mulai->format('H:i').'–'.$lab->jam_selesai->format('H:i').$penanda;

            $detail = array_filter([
                $lab->ruangan ? "📍 {$lab->ruangan}" : null,
                $lab->keterangan ? "📝 {$lab->keterangan}" : null,
            ]);

            if ($detail !== []) {
                $baris[] = '    '.implode(' · ', $detail);
            }
        }

        $baris[] = '';
        $baris[] = 'Catat tanggalnya dan siapkan perlengkapan praktikum 🙌';
        $baris[] = '👉 Jadwal lab lengkap: '.route('jadwal-lab.index', ['mk' => $mataKuliah->id]);

        return implode("\n", $baris);
    }

    /**
     * @param  Collection<int, JadwalKelas>  $jadwal  sessions of the day, ordered by jam mulai, with mataKuliah loaded
     */
    public static function jadwalKelas(Hari $hari, Collection $jadwal, Kelas $kelas): string
    {
        $label = Str::upper($hari->label()).($hari === Hari::today() ? ' (HARI INI)' : '');

        $baris = [
            "📅 *JADWAL KELAS {$kelas->nama} — {$label}*",
            $kelas->semesterAktif->nama,
            '',
        ];

        foreach ($jadwal->values() as $indeks => $sesi) {
            $baris[] = ($indeks + 1).'. '.$sesi->jam_mulai->format('H:i').'–'.$sesi->jam_selesai->format('H:i')." · *{$sesi->mataKuliah->nama}*";
            $baris[] = '    '.$sesi->mataKuliah->dosen.($sesi->ruangan ? " · 📍 {$sesi->ruangan}" : '');
        }

        $baris[] = '';
        $baris[] = 'Jangan lupa hadir tepat waktu 🙌';
        $baris[] = '👉 Jadwal lengkap: '.route('jadwal.index');

        return implode("\n", $baris);
    }

    /**
     * @param  Collection<int, JadwalLab>  $sesi  sessions on that date, ordered by jam mulai, with mataKuliah loaded
     */
    public static function jadwalLab(Carbon $tanggal, Collection $sesi, Kelas $kelas): string
    {
        $penanda = match (true) {
            $tanggal->isToday() => ' (hari ini)',
            $tanggal->isTomorrow() => ' (besok)',
            default => '',
        };

        $baris = [
            "🔬 *JADWAL LAB / PRAKTIKUM {$kelas->nama}*",
            '📆 '.$tanggal->isoFormat('dddd, D MMMM YYYY').$penanda,
            '',
        ];

        foreach ($sesi->values() as $indeks => $lab) {
            $baris[] = ($indeks + 1).'. '.$lab->jam_mulai->format('H:i').'–'.$lab->jam_selesai->format('H:i')." · *{$lab->mataKuliah->nama}*";

            $detail = array_filter([
                $lab->ruangan ? "📍 {$lab->ruangan}" : null,
                $lab->keterangan ? "📝 {$lab->keterangan}" : null,
            ]);

            if ($detail !== []) {
                $baris[] = '    '.implode(' · ', $detail);
            }
        }

        $baris[] = '';
        $baris[] = 'Siapkan perlengkapan praktikum dan hadir tepat waktu ya 🙌';
        $baris[] = '👉 Jadwal lab lengkap: '.route('jadwal-lab.index');

        return implode("\n", $baris);
    }

    /**
     * @param  Collection<int, Kelompok>  $daftar  kelompok of the kategori with anggota (npm, name, pivot is_ketua) loaded
     * @param  Collection<int, User>  $belumPunyaKelompok  students of the kelas not placed in any kelompok of this kategori
     */
    public static function kelompok(KategoriKelompok $kategori, Collection $daftar, Collection $belumPunyaKelompok, Kelas $kelas): string
    {
        $baris = [
            '👥 *PEMBAGIAN KELOMPOK — '.Str::upper($kategori->nama).'*',
            "{$kategori->mataKuliah->nama} · Kelas {$kelas->nama}",
            '',
        ];

        foreach ($daftar as $kelompok) {
            $ketua = $kelompok->anggota->first(fn (User $anggota) => (bool) $anggota->pivot->is_ketua);

            $baris[] = "*{$kelompok->nama}*".($ketua ? " — Ketua: {$ketua->name}" : '');

            foreach ($kelompok->anggota->values() as $indeks => $anggota) {
                $baris[] = ($indeks + 1).". {$anggota->name} ({$anggota->npm})";
            }

            $baris[] = '';
        }

        if ($daftar->isEmpty()) {
            $baris[] = '_Belum ada kelompok pada kategori ini._';
            $baris[] = '';
        }

        if ($belumPunyaKelompok->isNotEmpty()) {
            $baris[] = '⚠️ Belum masuk kelompok: '.$belumPunyaKelompok->map(fn (User $user) => $user->name)->implode(', ');
            $baris[] = '';
        }

        $jumlahAnggota = $daftar->sum(fn (Kelompok $kelompok) => $kelompok->anggota->count());
        $baris[] = "Total: {$daftar->count()} kelompok · {$jumlahAnggota} mahasiswa";
        $baris[] = '👉 Detail kelompok: '.route('kelompok.index', ['kategori' => $kategori->id]);

        return implode("\n", $baris);
    }
}
