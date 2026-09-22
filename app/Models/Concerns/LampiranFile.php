<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * Shared behaviour of attachment rows (informasi_lampiran, tugas_lampiran): the file lives on the
 * parent model's private disk and is removed with the row. Using models declare which parent they
 * belong to through diskLampiran().
 */
trait LampiranFile
{
    /** @var list<string> */
    public const array EKSTENSI_GAMBAR = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Disk the file is stored on (the parent's LAMPIRAN_DISK).
     */
    abstract public static function diskLampiran(): string;

    public static function bootLampiranFile(): void
    {
        static::deleted(fn (self $lampiran) => Storage::disk(static::diskLampiran())->delete($lampiran->path));
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
