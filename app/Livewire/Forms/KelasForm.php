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

    public string $masa_aktif_mulai = '';

    public string $masa_aktif_selesai = '';

    public bool $upload = true;

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
            'masa_aktif_mulai' => ['nullable', 'date', 'required_with:masa_aktif_selesai'],
            'masa_aktif_selesai' => ['nullable', 'date', 'after_or_equal:masa_aktif_mulai', 'required_with:masa_aktif_mulai'],
            'upload' => ['boolean'],
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
            'masa_aktif_mulai' => 'mulai masa aktif',
            'masa_aktif_selesai' => 'akhir masa aktif',
            'upload' => 'fitur upload',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'masa_aktif_selesai.after_or_equal' => 'Akhir masa aktif tidak boleh lebih awal dari tanggal mulai.',
            'masa_aktif_mulai.required_with' => 'Isi juga tanggal mulai masa aktif.',
            'masa_aktif_selesai.required_with' => 'Isi juga tanggal akhir masa aktif.',
        ];
    }

    public function fillFrom(Kelas $kelas): void
    {
        $this->kelas = $kelas;
        $this->nama = $kelas->nama;
        $this->prodi = (string) $kelas->prodi;
        $this->angkatan = (string) $kelas->angkatan;
        $this->semester_aktif_id = (string) $kelas->semester_aktif_id;
        $this->masa_aktif_mulai = $kelas->masa_aktif_mulai?->toDateString() ?? '';
        $this->masa_aktif_selesai = $kelas->masa_aktif_selesai?->toDateString() ?? '';
        $this->upload = $kelas->bolehUpload();
    }

    public function save(): Kelas
    {
        $data = $this->validate();

        $attributes = [
            'nama' => $data['nama'],
            'prodi' => $data['prodi'] !== '' ? $data['prodi'] : null,
            'angkatan' => (int) $data['angkatan'],
            'semester_aktif_id' => (int) $data['semester_aktif_id'],
            'masa_aktif_mulai' => $data['masa_aktif_mulai'] !== '' ? $data['masa_aktif_mulai'] : null,
            'masa_aktif_selesai' => $data['masa_aktif_selesai'] !== '' ? $data['masa_aktif_selesai'] : null,
            'upload' => (bool) $data['upload'],
        ];

        if ($this->kelas !== null) {
            $this->kelas->update($attributes);

            return $this->kelas;
        }

        return Kelas::query()->create($attributes);
    }
}
