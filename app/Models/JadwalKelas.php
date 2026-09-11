<?php

namespace App\Models;

use App\Enums\Hari;
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
    use HasFactory, ScopedByMataKuliah;

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
}
