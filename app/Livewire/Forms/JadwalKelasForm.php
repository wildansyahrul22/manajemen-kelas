<?php

namespace App\Livewire\Forms;

use App\Enums\Hari;
use App\Models\JadwalKelas;
use App\Models\Kelas;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * One hari with one or more sesi (mata kuliah + jam). Creating saves every sesi as its own
 * JadwalKelas row; editing works on a single existing row.
 */
class JadwalKelasForm extends Form
{
    public ?JadwalKelas $jadwal = null;

    public string $hari = '';

    /**
     * Keyed by a random id so removing a row never shifts the others' wire:model paths.
     *
     * @var array<string, array{mata_kuliah_id: string, jam_mulai: string, jam_selesai: string, ruangan: string}>
     */
    public array $sesi = [];

    protected ?Kelas $kelas = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'hari' => ['required', Rule::enum(Hari::class)],
            'sesi' => ['required', 'array', 'min:1'],
            'sesi.*.mata_kuliah_id' => [
                'required',
                Rule::exists('mata_kuliah', 'id')->where('kelas_id', $this->kelas?->id),
            ],
            'sesi.*.jam_mulai' => ['required', 'date_format:H:i'],
            'sesi.*.jam_selesai' => ['required', 'date_format:H:i', 'after:sesi.*.jam_mulai'],
            'sesi.*.ruangan' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'hari' => 'hari',
            'sesi' => 'mata kuliah',
            'sesi.*.mata_kuliah_id' => 'mata kuliah',
            'sesi.*.jam_mulai' => 'jam mulai',
            'sesi.*.jam_selesai' => 'jam selesai',
            'sesi.*.ruangan' => 'ruangan',
        ];
    }

    public function startCreate(?int $hari = null): void
    {
        $this->reset();
        $this->hari = (string) ($hari ?? '');
        $this->addSesi();
    }

    public function fillFrom(JadwalKelas $jadwal): void
    {
        $this->reset();
        $this->jadwal = $jadwal;
        $this->hari = (string) $jadwal->hari->value;
        $this->sesi = [
            Str::random(8) => [
                'mata_kuliah_id' => (string) $jadwal->mata_kuliah_id,
                'jam_mulai' => $jadwal->jam_mulai->format('H:i'),
                'jam_selesai' => $jadwal->jam_selesai->format('H:i'),
                'ruangan' => (string) $jadwal->ruangan,
            ],
        ];
    }

    public function addSesi(): void
    {
        $this->sesi[Str::random(8)] = [
            'mata_kuliah_id' => '',
            'jam_mulai' => '',
            'jam_selesai' => '',
            'ruangan' => '',
        ];
    }

    public function removeSesi(string $key): void
    {
        if (count($this->sesi) <= 1) {
            return;
        }

        unset($this->sesi[$key]);
    }

    /**
     * @return Collection<int, JadwalKelas>
     */
    public function save(Kelas $kelas): Collection
    {
        $this->kelas = $kelas;

        $data = $this->validate();

        $rows = collect($data['sesi'])->map(fn (array $sesi) => [
            'mata_kuliah_id' => (int) $sesi['mata_kuliah_id'],
            'hari' => (int) $data['hari'],
            'jam_mulai' => $sesi['jam_mulai'],
            'jam_selesai' => $sesi['jam_selesai'],
            'ruangan' => ($sesi['ruangan'] ?? '') !== '' ? $sesi['ruangan'] : null,
        ]);

        if ($this->jadwal !== null) {
            $this->jadwal->update($rows->first());

            return collect([$this->jadwal]);
        }

        return $rows
            ->sortBy('jam_mulai')
            ->map(fn (array $attributes) => JadwalKelas::query()->create($attributes))
            ->values();
    }
}
