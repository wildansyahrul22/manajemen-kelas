<?php

namespace App\Models;

use App\Models\Concerns\LampiranFile;
use Database\Factories\InformasiLampiranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file attached to an informasi, stored on the private disk; the file is removed with the row.
 */
#[Table('informasi_lampiran')]
#[Fillable(['informasi_id', 'path', 'nama', 'ukuran'])]
class InformasiLampiran extends Model
{
    /** @use HasFactory<InformasiLampiranFactory> */
    use HasFactory, LampiranFile;

    public static function diskLampiran(): string
    {
        return Informasi::LAMPIRAN_DISK;
    }

    public function informasi(): BelongsTo
    {
        return $this->belongsTo(Informasi::class, 'informasi_id');
    }
}
