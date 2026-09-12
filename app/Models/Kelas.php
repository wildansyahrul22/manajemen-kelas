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
#[Fillable(['nama', 'prodi', 'angkatan', 'semester_aktif_id'])]
class Kelas extends Model
{
    /** @use HasFactory<KelasFactory> */
    use HasFactory, LogsActivity;

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
