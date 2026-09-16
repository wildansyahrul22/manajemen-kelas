<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedByMataKuliah;
use Database\Factories\KategoriKelompokFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Groups kelompok of one mata kuliah (e.g. "Project Akhir", "Presentasi"). A student may only be in
 * one kelompok per kategori. While the kategori is not final every member of the kelas may arrange
 * its kelompok; marking it final freezes them.
 */
#[Table('kategori_kelompok')]
#[Fillable(['mata_kuliah_id', 'nama', 'final', 'created_by'])]
class KategoriKelompok extends Model
{
    /** @use HasFactory<KategoriKelompokFactory> */
    use HasFactory, LogsActivity, ScopedByMataKuliah;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'final' => 'boolean',
        ];
    }

    public function kelompok(): HasMany
    {
        return $this->hasMany(Kelompok::class, 'kategori_kelompok_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term !== '') {
            $query->where('nama', 'like', "%{$term}%");
        }
    }

    /**
     * A final kategori is locked: its kelompok may not be created, edited or removed by anyone
     * until an admin kelas (or super admin) unlocks it.
     */
    public function isFinal(): bool
    {
        return (bool) $this->final;
    }

    /**
     * Ids of students already placed in a kelompok of this kategori (optionally ignoring one kelompok).
     */
    public static function anggotaIds(int $kategoriId, ?int $exceptKelompokId = null): Builder
    {
        return Kelompok::query()
            ->join('kelompok_anggota', 'kelompok_anggota.kelompok_id', '=', 'kelompok.id')
            ->select('kelompok_anggota.user_id')
            ->where('kelompok.kategori_kelompok_id', $kategoriId)
            ->when($exceptKelompokId !== null, fn (Builder $query) => $query->where('kelompok.id', '!=', $exceptKelompokId));
    }

    public function activityModul(): ModulLog
    {
        return ModulLog::KategoriKelompok;
    }

    public function activityLabel(): string
    {
        return $this->nama;
    }

    public function activityKelasId(): ?int
    {
        return $this->kelasIdViaMataKuliah();
    }
}
