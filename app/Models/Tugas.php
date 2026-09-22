<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Models\Concerns\HasLampiran;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\RoutesByUlid;
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
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('tugas')]
#[Fillable(['mata_kuliah_id', 'kategori_kelompok_id', 'nama', 'deskripsi', 'deadline', 'link_pengumpulan', 'created_by'])]
class Tugas extends Model
{
    /** @use HasFactory<TugasFactory> */
    use HasFactory, HasLampiran, LogsActivity, RoutesByUlid, ScopedByMataKuliah;

    public const string LAMPIRAN_DIR = 'tugas';

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

    /**
     * Set when the tugas is done per kelompok; the kategori's kelompok are the working groups.
     */
    public function kategoriKelompok(): BelongsTo
    {
        return $this->belongsTo(KategoriKelompok::class, 'kategori_kelompok_id');
    }

    public function lampiran(): HasMany
    {
        return $this->hasMany(TugasLampiran::class, 'tugas_id')->orderBy('id');
    }

    public function isTugasKelompok(): bool
    {
        return $this->kategori_kelompok_id !== null;
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

    public function activityModul(): ModulLog
    {
        return ModulLog::Tugas;
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
