<?php

namespace App\Models\Concerns;

use App\Models\Kelas;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared behaviour for models that hang off a mata kuliah (tugas, jadwal, kelompok).
 * Rows are scoped to a kelas + its active semester through a single indexed subquery.
 */
trait ScopedByMataKuliah
{
    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    /**
     * Limit rows to the mata kuliah of the given kelas in its active semester.
     */
    #[Scope]
    protected function forKelasAktif(Builder $query, Kelas $kelas): void
    {
        $query->whereIn('mata_kuliah_id', MataKuliah::idsForKelasAktif($kelas));
    }

    /**
     * Whether this row belongs to the given kelas (any semester).
     */
    public function belongsToKelas(int $kelasId): bool
    {
        return $this->mataKuliah()->where('kelas_id', $kelasId)->exists();
    }
}
