<?php

namespace App\Models;

use App\Enums\ModulLog;
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
    use HasFactory, LogsActivity;

    /** Private disk: attachments are streamed through a route that checks kelas membership. */
    public const string LAMPIRAN_DISK = 'local';

    public const string LAMPIRAN_DIR = 'informasi';

    /** @var list<string> */
    public const array LAMPIRAN_EKSTENSI = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip'];

    /** Per file. */
    public const int LAMPIRAN_MAKS_KB = 2048;

    /** Per informasi. */
    public const int LAMPIRAN_MAKS_JUMLAH = 2;

    protected static function booted(): void
    {
        // Deleting through the models (not the FK cascade) so each attachment removes its file.
        static::deleting(fn (Informasi $informasi) => $informasi->lampiran()->get()->each->delete());
    }

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

    /**
     * Scoped route binding for /informasi/{informasi}/lampiran/{lampiran}: the relation is named
     * "lampiran" (Indonesian has no plural form), not the "lampirans" Laravel would guess.
     */
    public function resolveChildRouteBinding($childType, $value, $field): ?Model
    {
        if ($childType === 'lampiran') {
            return $this->lampiran()->where($field ?? 'id', $value)->first();
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }

    /**
     * Number of attached files, from withCount('lampiran') when present, else the loaded relation.
     */
    public function jumlahLampiran(): int
    {
        if (array_key_exists('lampiran_count', $this->attributes)) {
            return (int) $this->attributes['lampiran_count'];
        }

        return $this->lampiran->count();
    }

    public function hasLampiran(): bool
    {
        return $this->jumlahLampiran() > 0;
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
