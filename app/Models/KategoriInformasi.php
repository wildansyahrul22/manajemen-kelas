<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Models\Concerns\LogsActivity;
use Database\Factories\KategoriInformasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kategori_informasi')]
#[Fillable(['kelas_id', 'nama', 'warna', 'created_by'])]
class KategoriInformasi extends Model
{
    /** @use HasFactory<KategoriInformasiFactory> */
    use HasFactory, LogsActivity;

    /**
     * Available badge colours (name => tailwind classes).
     *
     * @var array<string, string>
     */
    public const array WARNA = [
        'slate' => 'bg-slate-100 text-slate-700',
        'orange' => 'bg-orange-50 text-orange-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'rose' => 'bg-rose-50 text-rose-700',
        'sky' => 'bg-sky-50 text-sky-700',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function informasi(): HasMany
    {
        return $this->hasMany(Informasi::class, 'kategori_informasi_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function badgeClass(): string
    {
        return self::WARNA[$this->warna] ?? self::WARNA['slate'];
    }

    public function activityModul(): ModulLog
    {
        return ModulLog::KategoriInformasi;
    }

    public function activityLabel(): string
    {
        return $this->nama;
    }

    public function activityKelasId(): ?int
    {
        return $this->kelas_id;
    }
}
