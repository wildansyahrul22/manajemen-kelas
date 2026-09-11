<?php

namespace App\Livewire\Forms;

use App\Models\Kelas;
use Illuminate\Validation\Rule;
use Livewire\Form;

class KelasForm extends Form
{
    public ?Kelas $kelas = null;

    public string $nama = '';

    public string $prodi = '';

    public string $angkatan = '';

    public string $semester_aktif_id = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:50', Rule::unique('kelas', 'nama')->ignore($this->kelas?->id)],
            'prodi' => ['nullable', 'string', 'max:100'],
            'angkatan' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'semester_aktif_id' => ['required', Rule::exists('semesters', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'nama' => 'nama kelas',
            'prodi' => 'program studi',
            'angkatan' => 'angkatan',
            'semester_aktif_id' => 'semester aktif',
        ];
    }

    public function fillFrom(Kelas $kelas): void
    {
        $this->kelas = $kelas;
        $this->nama = $kelas->nama;
        $this->prodi = (string) $kelas->prodi;
        $this->angkatan = (string) $kelas->angkatan;
        $this->semester_aktif_id = (string) $kelas->semester_aktif_id;
    }

    public function save(): Kelas
    {
        $data = $this->validate();

        $attributes = [
            'nama' => $data['nama'],
            'prodi' => $data['prodi'] !== '' ? $data['prodi'] : null,
            'angkatan' => (int) $data['angkatan'],
            'semester_aktif_id' => (int) $data['semester_aktif_id'],
        ];

        if ($this->kelas !== null) {
            $this->kelas->update($attributes);

            return $this->kelas;
        }

        return Kelas::query()->create($attributes);
    }
}
