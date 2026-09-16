<?php

namespace App\Models;

use Database\Factories\InformasiLampiranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * A file attached to an informasi, stored on the private disk; the file is removed with the row.
 */
#[Table('informasi_lampiran')]
#[Fillable(['informasi_id', 'path', 'nama', 'ukuran'])]
class InformasiLampiran extends Model
{
    /** @use HasFactory<InformasiLampiranFactory> */
    use HasFactory;

    /** @var list<string> */
    public const array EKSTENSI_GAMBAR = ['jpg', 'jpeg', 'png', 'webp'];

    protected static function booted(): void
    {
        static::deleted(fn (InformasiLampiran $lampiran) => Storage::disk(Informasi::LAMPIRAN_DISK)->delete($lampiran->path));
    }

    public function informasi(): BelongsTo
    {
        return $this->belongsTo(Informasi::class, 'informasi_id');
    }

    public function ekstensi(): string
    {
        return strtolower(pathinfo($this->nama, PATHINFO_EXTENSION));
    }

    public function isImage(): bool
    {
        return in_array($this->ekstensi(), self::EKSTENSI_GAMBAR, true);
    }

    public function isPdf(): bool
    {
        return $this->ekstensi() === 'pdf';
    }

    /**
     * Images and PDFs are streamed inline so they can be viewed (in a modal, or a new tab) instead
     * of downloaded; every other type is only ever sent as a download.
     */
    public function bisaDipratinjau(): bool
    {
        return $this->isImage() || $this->isPdf();
    }

    /**
     * "1,2 MB" / "340 KB", or null when the size was never recorded.
     */
    public function ukuranTerbaca(): ?string
    {
        return $this->ukuran === null ? null : Number::fileSize($this->ukuran, precision: 1);
    }
}
