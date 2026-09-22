<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Models\Concerns\HasLampiran;
use App\Models\Concerns\LogsActivity;
use Database\Factories\InformasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('informasi')]
#[Fillable(['kelas_id', 'kategori_informasi_id', 'judul', 'isi', 'link', 'is_pinned', 'created_by'])]
class Informasi extends Model
{
    /** @use HasFactory<InformasiFactory> */
    use HasFactory, HasLampiran, LogsActivity;

    public const string LAMPIRAN_DIR = 'informasi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriInformasi::class, 'kategori_informasi_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lampiran(): HasMany
    {
        return $this->hasMany(InformasiLampiran::class, 'informasi_id')->orderBy('id');
    }

    #[Scope]
    protected function forKelas(Builder $query, int $kelasId): void
    {
        $query->where('kelas_id', $kelasId);
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term !== '') {
            $query->where('judul', 'like', "%{$term}%");
        }
    }

    #[Scope]
    protected function terbaru(Builder $query): void
    {
        $query->orderByDesc('is_pinned')->orderByDesc('created_at');
    }

    public function activityModul(): ModulLog
    {
        return ModulLog::Informasi;
    }

    public function activityLabel(): string
    {
        return $this->judul;
    }

    public function activityKelasId(): ?int
    {
        return $this->kelas_id;
    }
}
