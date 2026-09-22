<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\RoutesByUlid;
use App\Models\Concerns\ScopedByMataKuliah;
use Database\Factories\KelompokFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('kelompok')]
#[Fillable(['mata_kuliah_id', 'kategori_kelompok_id', 'nama', 'deskripsi', 'created_by'])]
class Kelompok extends Model
{
    /** @use HasFactory<KelompokFactory> */
    use HasFactory, LogsActivity, RoutesByUlid, ScopedByMataKuliah;

    public function anggota(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kelompok_anggota', 'kelompok_id', 'user_id')
            ->withPivot('is_ketua')
            ->withTimestamps()
            ->orderByDesc('kelompok_anggota.is_ketua')
            ->orderBy('users.name');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriKelompok::class, 'kategori_kelompok_id');
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

    public function activityModul(): ModulLog
    {
        return ModulLog::Kelompok;
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
