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
use Illuminate\Support\Facades\Storage;

#[Table('informasi')]
#[Fillable(['kelas_id', 'kategori_informasi_id', 'judul', 'isi', 'link', 'lampiran_path', 'lampiran_nama', 'is_pinned', 'created_by'])]
class Informasi extends Model
{
    /** @use HasFactory<InformasiFactory> */
    use HasFactory, LogsActivity;

    /** Private disk: attachments are streamed through a route that checks kelas membership. */
    public const string LAMPIRAN_DISK = 'local';

    public const string LAMPIRAN_DIR = 'informasi';

    /** @var list<string> */
    public const array LAMPIRAN_EKSTENSI = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip'];

    public const int LAMPIRAN_MAKS_KB = 5120;

    protected static function booted(): void
    {
        static::deleting(fn (Informasi $informasi) => $informasi->hapusLampiran());
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

    public function hasLampiran(): bool
    {
        return $this->lampiran_path !== null;
    }

    public function lampiranIsImage(): bool
    {
        return in_array($this->lampiranEkstensi(), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }

    public function lampiranEkstensi(): string
    {
        return strtolower(pathinfo((string) $this->lampiran_nama, PATHINFO_EXTENSION));
    }

    /**
     * Remove the stored file (if any) without touching the row.
     */
    public function hapusLampiran(): void
    {
        if ($this->lampiran_path !== null) {
            Storage::disk(self::LAMPIRAN_DISK)->delete($this->lampiran_path);
        }
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
