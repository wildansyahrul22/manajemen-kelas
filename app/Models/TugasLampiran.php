<?php

namespace App\Models;

use App\Models\Concerns\LampiranFile;
use Database\Factories\TugasLampiranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file attached to a tugas (soal, template laporan, …), stored on the private disk; the file is
 * removed with the row.
 */
#[Table('tugas_lampiran')]
#[Fillable(['tugas_id', 'path', 'nama', 'ukuran'])]
class TugasLampiran extends Model
{
    /** @use HasFactory<TugasLampiranFactory> */
    use HasFactory, LampiranFile;

    public static function diskLampiran(): string
    {
        return Tugas::LAMPIRAN_DISK;
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }
}
