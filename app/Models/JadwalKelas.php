<?php

namespace App\Models;

use App\Enums\Hari;
use App\Enums\ModulLog;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedByMataKuliah;
use Database\Factories\JadwalKelasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('jadwal_kelas')]
#[Fillable(['mata_kuliah_id', 'hari', 'jam_mulai', 'jam_selesai', 'ruangan'])]
class JadwalKelas extends Model
{
    /** @use HasFactory<JadwalKelasFactory> */
    use HasFactory, LogsActivity, ScopedByMataKuliah;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hari' => Hari::class,
            'jam_mulai' => 'datetime:H:i',
            'jam_selesai' => 'datetime:H:i',
        ];
    }

    #[Scope]
    protected function hariIni(Builder $query): void
    {
        $query->where('hari', Hari::today());
    }

    #[Scope]
    protected function urut(Builder $query): void
    {
        $query->orderBy('hari')->orderBy('jam_mulai');
    }

    public function jam(): string
    {
        return $this->jam_mulai->format('H:i').' - '.$this->jam_selesai->format('H:i');
    }

    public function activityModul(): ModulLog
    {
        return ModulLog::Jadwal;
    }

    public function activityLabel(): string
    {
        $mataKuliah = MataKuliah::query()->whereKey($this->mata_kuliah_id)->value('nama') ?? 'Mata kuliah';
        $hari = $this->hari instanceof Hari ? $this->hari->label() : Hari::from((int) $this->hari)->label();

        return "{$mataKuliah} · {$hari} ".$this->jam();
    }

    public function activityKelasId(): ?int
    {
        return $this->kelasIdViaMataKuliah();
    }
}
