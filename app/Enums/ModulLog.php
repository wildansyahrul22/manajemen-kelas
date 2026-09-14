<?php

namespace App\Enums;

enum ModulLog: string
{
    case Informasi = 'informasi';
    case KategoriInformasi = 'kategori_informasi';
    case Kelompok = 'kelompok';
    case KategoriKelompok = 'kategori_kelompok';
    case Tugas = 'tugas';
    case Jadwal = 'jadwal';
    case JadwalLab = 'jadwal_lab';
    case MataKuliah = 'mata_kuliah';
    case User = 'user';
    case Kelas = 'kelas';
    case Auth = 'auth';

    public function label(): string
    {
        return match ($this) {
            self::Informasi => 'Informasi',
            self::KategoriInformasi => 'Kategori Informasi',
            self::Kelompok => 'Kelompok',
            self::KategoriKelompok => 'Kategori Kelompok',
            self::Tugas => 'Tugas',
            self::Jadwal => 'Jadwal Kelas',
            self::JadwalLab => 'Jadwal Lab',
            self::MataKuliah => 'Mata Kuliah',
            self::User => 'User',
            self::Kelas => 'Kelas',
            self::Auth => 'Autentikasi',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $modul) => [$modul->value => $modul->label()])
            ->all();
    }
}
