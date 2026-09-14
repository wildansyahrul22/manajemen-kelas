<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedByMataKuliah;
use Database\Factories\JadwalLabFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One praktikum session of a mata kuliah on a specific date (unlike JadwalKelas, which repeats weekly).
 */
#[Table('jadwal_lab')]
#[Fillable(['mata_kuliah_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'ruangan', 'keterangan'])]
class JadwalLab extends Model
{
    /** @use HasFactory<JadwalLabFactory> */
    use HasFactory, LogsActivity, ScopedByMataKuliah;

    /**
     * Custom-format casts keep the DB value exactly as given ('Y-m-d' / 'H:i'), so the date and
     * time scopes below compare correctly on both MySQL and the SQLite test database.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'jam_mulai' => 'datetime:H:i',
            'jam_selesai' => 'datetime:H:i',
        ];
    }

    #[Scope]
    protected function hariIni(Builder $query): void
    {
        $query->where('tanggal', today()->toDateString());
    }

    /**
     * Sessions that have not ended yet (today's session stays until its jam selesai passes).
     */
    #[Scope]
    protected function mendatang(Builder $query): void
    {
        $today = today()->toDateString();

        $query->where(function (Builder $query) use ($today) {
            $query->where('tanggal', '>', $today)
                ->orWhere(fn (Builder $query) => $query->where('tanggal', $today)->where('jam_selesai', '>=', now()->format('H:i')));
        });
    }

    #[Scope]
    protected function lewat(Builder $query): void
    {
        $today = today()->toDateString();

        $query->where(function (Builder $query) use ($today) {
            $query->where('tanggal', '<', $today)
                ->orWhere(fn (Builder $query) => $query->where('tanggal', $today)->where('jam_selesai', '<', now()->format('H:i')));
        });
    }

    #[Scope]
    protected function urut(Builder $query): void
    {
        $query->orderBy('tanggal')->orderBy('jam_mulai');
    }

    public function jam(): string
    {
        return $this->jam_mulai->format('H:i').' - '.$this->jam_selesai->format('H:i');
    }

    /**
     * Moment the session ends: its tanggal combined with jam selesai.
     */
    public function selesaiPada(): Carbon
    {
        return $this->tanggal->copy()->setTimeFrom($this->jam_selesai);
    }

    public function isLewat(): bool
    {
        return $this->selesaiPada()->isPast();
    }

    public function isHariIni(): bool
    {
        return $this->tanggal->isToday();
    }

    public function activityModul(): ModulLog
    {
        return ModulLog::JadwalLab;
    }

    public function activityLabel(): string
    {
        $mataKuliah = MataKuliah::query()->whereKey($this->mata_kuliah_id)->value('nama') ?? 'Mata kuliah';

        return "{$mataKuliah} · ".$this->tanggal->isoFormat('D MMM YYYY').' '.$this->jam();
    }

    public function activityKelasId(): ?int
    {
        return $this->kelasIdViaMataKuliah();
    }
}
