<?php

namespace App\Models;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail: who did what, on which record, in which kelas. Rows are written by the
 * LogsActivity model trait and the auth listener; they are never updated.
 */
#[Table('activity_log')]
#[Fillable(['kelas_id', 'user_id', 'user_name', 'aksi', 'modul', 'subjek_id', 'subjek_label', 'perubahan', 'ip'])]
class ActivityLog extends Model
{
    public const null UPDATED_AT = null;

    public const string NAMA_SISTEM = 'Sistem';

    /** Longest value kept per changed attribute. */
    public const int PANJANG_NILAI = 500;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aksi' => AksiLog::class,
            'modul' => ModulLog::class,
            'perubahan' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /**
     * Write one entry. The actor defaults to the logged-in user ("Sistem" from console/seeders).
     *
     * @param  array<string, array{0: mixed, 1: mixed}>  $perubahan  attribute => [before, after]
     */
    public static function catat(
        AksiLog $aksi,
        ModulLog $modul,
        string $label,
        ?int $kelasId = null,
        ?int $subjekId = null,
        array $perubahan = [],
        ?User $pelaku = null,
    ): self {
        $pelaku ??= auth()->user();

        return static::query()->create([
            'kelas_id' => $kelasId,
            'user_id' => $pelaku?->id,
            'user_name' => $pelaku?->name ?? self::NAMA_SISTEM,
            'aksi' => $aksi,
            'modul' => $modul,
            'subjek_id' => $subjekId,
            'subjek_label' => mb_substr($label, 0, 200),
            'perubahan' => $perubahan !== [] ? $perubahan : null,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('user_name', 'like', "%{$term}%")
                ->orWhere('subjek_label', 'like', "%{$term}%");
        });
    }
}
