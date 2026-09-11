<?php

namespace App\Models;

use App\Models\Concerns\ScopedByMataKuliah;
use Carbon\CarbonInterface;
use Database\Factories\TugasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tugas')]
#[Fillable(['mata_kuliah_id', 'nama', 'deskripsi', 'deadline', 'created_by'])]
class Tugas extends Model
{
    /** @use HasFactory<TugasFactory> */
    use HasFactory, ScopedByMataKuliah;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    #[Scope]
    protected function belumDeadline(Builder $query): void
    {
        $query->where('deadline', '>=', now());
    }

    #[Scope]
    protected function lewatDeadline(Builder $query): void
    {
        $query->where('deadline', '<', now());
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term !== '') {
            $query->where('nama', 'like', "%{$term}%");
        }
    }

    public function isLewat(): bool
    {
        return $this->deadline->isPast();
    }

    /**
     * "3 hari lagi" for upcoming deadlines, "2 jam yang lalu" for passed ones.
     */
    public function sisaWaktu(): string
    {
        if ($this->isLewat()) {
            return $this->deadline->diffForHumans();
        }

        return $this->deadline->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE).' lagi';
    }

    /**
     * Status used for the badge: lewat | segera (< 3 hari) | aktif.
     */
    public function status(): string
    {
        if ($this->isLewat()) {
            return 'lewat';
        }

        return $this->deadline->lessThan(now()->addDays(3)) ? 'segera' : 'aktif';
    }
}
