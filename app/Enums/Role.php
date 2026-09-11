<?php

namespace App\Enums;

enum Role: string
{
    case Mahasiswa = 'mahasiswa';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Mahasiswa => 'Mahasiswa',
            self::Admin => 'Admin Kelas',
            self::SuperAdmin => 'Super Admin',
        };
    }

    /**
     * Tailwind classes used for the role badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Mahasiswa => 'bg-slate-100 text-slate-700',
            self::Admin => 'bg-sky-50 text-sky-700',
            self::SuperAdmin => 'bg-amber-50 text-amber-700',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
