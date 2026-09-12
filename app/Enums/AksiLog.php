<?php

namespace App\Enums;

enum AksiLog: string
{
    case Buat = 'buat';
    case Ubah = 'ubah';
    case Hapus = 'hapus';
    case Masuk = 'masuk';
    case Keluar = 'keluar';

    public function label(): string
    {
        return match ($this) {
            self::Buat => 'Buat',
            self::Ubah => 'Ubah',
            self::Hapus => 'Hapus',
            self::Masuk => 'Masuk',
            self::Keluar => 'Keluar',
        };
    }

    /**
     * Tailwind classes used for the aksi badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Buat => 'bg-emerald-50 text-emerald-700',
            self::Ubah => 'bg-sky-50 text-sky-700',
            self::Hapus => 'bg-rose-50 text-rose-700',
            self::Masuk => 'bg-slate-100 text-slate-700',
            self::Keluar => 'bg-slate-100 text-slate-500',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $aksi) => [$aksi->value => $aksi->label()])
            ->all();
    }
}
