<?php

namespace App\Livewire\Forms;

use App\Enums\Hari;
use App\Models\JadwalKelas;
use App\Models\Kelas;
use Illuminate\Validation\Rule;
use Livewire\Form;

class JadwalKelasForm extends Form
{
    public ?JadwalKelas $jadwal = null;

    public string $mata_kuliah_id = '';

    public string $hari = '';

    public string $jam_mulai = '';

    public string $jam_selesai = '';

    public string $ruangan = '';

    protected ?Kelas $kelas = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mata_kuliah_id' => [
                'required',
                Rule::exists('mata_kuliah', 'id')->where('kelas_id', $this->kelas?->id),
            ],
            'hari' => ['required', Rule::enum(Hari::class)],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'ruangan' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'mata_kuliah_id' => 'mata kuliah',
            'hari' => 'hari',
            'jam_mulai' => 'jam mulai',
            'jam_selesai' => 'jam selesai',
            'ruangan' => 'ruangan',
        ];
    }

    public function fillFrom(JadwalKelas $jadwal): void
    {
        $this->jadwal = $jadwal;
        $this->mata_kuliah_id = (string) $jadwal->mata_kuliah_id;
        $this->hari = (string) $jadwal->hari->value;
        $this->jam_mulai = $jadwal->jam_mulai->format('H:i');
        $this->jam_selesai = $jadwal->jam_selesai->format('H:i');
        $this->ruangan = (string) $jadwal->ruangan;
    }

    public function save(Kelas $kelas): JadwalKelas
    {
        $this->kelas = $kelas;

        $data = $this->validate();

        $attributes = [
            'mata_kuliah_id' => (int) $data['mata_kuliah_id'],
            'hari' => (int) $data['hari'],
            'jam_mulai' => $data['jam_mulai'],
            'jam_selesai' => $data['jam_selesai'],
            'ruangan' => $data['ruangan'] !== '' ? $data['ruangan'] : null,
        ];

        if ($this->jadwal !== null) {
            $this->jadwal->update($attributes);

            return $this->jadwal;
        }

        return JadwalKelas::query()->create($attributes);
    }
}
