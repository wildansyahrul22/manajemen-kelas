<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Enums\Role;
use App\Models\Concerns\LogsActivity;
use Database\Factories\KelasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kelas')]
#[Fillable(['kampus_id', 'nama', 'prodi', 'angkatan', 'semester_aktif_id', 'masa_aktif_mulai', 'masa_aktif_selesai', 'upload'])]
class Kelas extends Model
{
    /** @use HasFactory<KelasFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'masa_aktif_mulai' => 'date',
            'masa_aktif_selesai' => 'date',
            'upload' => 'boolean',
        ];
    }

    public function kampus(): BelongsTo
    {
        return $this->belongsTo(Kampus::class, 'kampus_id');
    }

    public function semesterAktif(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_aktif_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'kelas_id');
    }

    /**
     * Students of the kelas (mahasiswa + admin kelas).
     */
    public function mahasiswa(): HasMany
    {
        return $this->users()->whereIn('role', [Role::Mahasiswa, Role::Admin]);
    }

    public function mataKuliah(): HasMany
    {
        return $this->hasMany(MataKuliah::class, 'kelas_id');
    }

    public function mataKuliahAktif(): HasMany
    {
        return $this->mataKuliah()->where('semester_id', $this->semester_aktif_id);
    }

    public function kategoriInformasi(): HasMany
    {
        return $this->hasMany(KategoriInformasi::class, 'kelas_id');
    }

    public function informasi(): HasMany
    {
        return $this->hasMany(Informasi::class, 'kelas_id');
    }

    /**
     * Whether the subscription window covers today; an empty window means the kelas never expires.
     * Members of an inactive kelas cannot sign in.
     */
    public function isAktif(): bool
    {
        $hariIni = now()->startOfDay();

        return ! ($this->masa_aktif_mulai?->startOfDay()->gt($hariIni) ?? false)
            && ! ($this->masa_aktif_selesai?->endOfDay()->lt($hariIni) ?? false);
    }

    public function sudahBerakhir(): bool
    {
        return $this->masa_aktif_selesai !== null && $this->masa_aktif_selesai->endOfDay()->lt(now());
    }

    /**
     * "1 Sep 2026 - 28 Feb 2027", one-sided when only one date is set, null when there is no window.
     */
    public function masaAktifTerbaca(): ?string
    {
        $mulai = $this->masa_aktif_mulai?->isoFormat('D MMM YYYY');
        $selesai = $this->masa_aktif_selesai?->isoFormat('D MMM YYYY');

        return match (true) {
            $mulai !== null && $selesai !== null => "{$mulai} - {$selesai}",
            $mulai !== null => "Mulai {$mulai}",
            $selesai !== null => "Sampai {$selesai}",
            default => null,
        };
    }

    /**
     * Whether this kelas' plan includes the upload features (attachments, import).
     */
    public function bolehUpload(): bool
    {
        return (bool) $this->upload;
    }

    public function activityModul(): ModulLog
    {
        return ModulLog::Kelas;
    }

    public function activityLabel(): string
    {
        return $this->nama;
    }

    /**
     * A deleted kelas can no longer be referenced, so its removal is logged without kelas.
     */
    public function activityKelasId(): ?int
    {
        return $this->exists ? $this->id : null;
    }
}
