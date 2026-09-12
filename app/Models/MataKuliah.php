<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Models\Concerns\LogsActivity;
use Database\Factories\MataKuliahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('mata_kuliah')]
#[Fillable(['kelas_id', 'semester_id', 'kode', 'nama', 'dosen', 'sks'])]
class MataKuliah extends Model
{
    /** @use HasFactory<MataKuliahFactory> */
    use HasFactory, LogsActivity;

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalKelas::class, 'mata_kuliah_id');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class, 'mata_kuliah_id');
    }

    public function kelompok(): HasMany
    {
        return $this->hasMany(Kelompok::class, 'mata_kuliah_id');
    }

    public function kategoriKelompok(): HasMany
    {
        return $this->hasMany(KategoriKelompok::class, 'mata_kuliah_id');
    }

    #[Scope]
    protected function forKelasAktif(Builder $query, Kelas $kelas): void
    {
        $query->where('kelas_id', $kelas->id)->where('semester_id', $kelas->semester_aktif_id);
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('nama', 'like', "%{$term}%")
                ->orWhere('dosen', 'like', "%{$term}%")
                ->orWhere('kode', 'like', "%{$term}%");
        });
    }

    /**
     * Sub-select of mata kuliah ids for a kelas in its active semester.
     * Reused by every "per semester" scope so filtering stays a single indexed query.
     */
    public static function idsForKelasAktif(Kelas $kelas): Builder
    {
        return static::query()
            ->select('id')
            ->where('kelas_id', $kelas->id)
            ->where('semester_id', $kelas->semester_aktif_id);
    }

    public function activityModul(): ModulLog
    {
        return ModulLog::MataKuliah;
    }

    public function activityLabel(): string
    {
        return $this->nama;
    }

    public function activityKelasId(): ?int
    {
        return $this->kelas_id;
    }
}
