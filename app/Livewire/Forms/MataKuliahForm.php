<?php

namespace App\Livewire\Forms;

use App\Models\Kelas;
use App\Models\MataKuliah;
use Illuminate\Validation\Rule;
use Livewire\Form;

class MataKuliahForm extends Form
{
    public ?MataKuliah $mataKuliah = null;

    public string $semester_id = '';

    public string $kode = '';

    public string $nama = '';

    public string $dosen = '';

    public int $sks = 2;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'semester_id' => ['required', Rule::exists('semesters', 'id')],
            'kode' => ['nullable', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:100'],
            'dosen' => ['required', 'string', 'max:100'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'semester_id' => 'semester',
            'kode' => 'kode',
            'nama' => 'nama mata kuliah',
            'dosen' => 'dosen',
            'sks' => 'SKS',
        ];
    }

    public function fillFrom(MataKuliah $mataKuliah): void
    {
        $this->mataKuliah = $mataKuliah;
        $this->semester_id = (string) $mataKuliah->semester_id;
        $this->kode = (string) $mataKuliah->kode;
        $this->nama = $mataKuliah->nama;
        $this->dosen = $mataKuliah->dosen;
        $this->sks = $mataKuliah->sks;
    }

    public function save(Kelas $kelas): MataKuliah
    {
        $data = $this->validate();

        $attributes = [
            'semester_id' => (int) $data['semester_id'],
            'kode' => $data['kode'] !== '' ? strtoupper($data['kode']) : null,
            'nama' => $data['nama'],
            'dosen' => $data['dosen'],
            'sks' => (int) $data['sks'],
        ];

        if ($this->mataKuliah !== null) {
            $this->mataKuliah->update($attributes);

            return $this->mataKuliah;
        }

        return MataKuliah::query()->create([...$attributes, 'kelas_id' => $kelas->id]);
    }
}
