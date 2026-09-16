<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Models\Concerns\LogsActivity;
use Database\Factories\KampusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The university a kelas belongs to; one row per campus, each with its own set of kelas.
 */
#[Table('kampus')]
#[Fillable(['nama'])]
class Kampus extends Model
{
    /** @use HasFactory<KampusFactory> */
    use HasFactory, LogsActivity;

    /** The campus created on install; every kelas that predates campuses was moved into it. */
    public const string AWAL = 'Universitas Indraprasta PGRI';

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'kampus_id');
    }

    public function activityModul(): ModulLog
    {
        return ModulLog::Kampus;
    }

    public function activityLabel(): string
    {
        return $this->nama;
    }

    /**
     * A kampus spans kelas, so its log rows are not tied to any one kelas.
     */
    public function activityKelasId(): ?int
    {
        return null;
    }
}
