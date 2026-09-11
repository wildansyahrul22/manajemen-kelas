<?php

namespace App\Livewire\Forms;

use App\Models\Kelas;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TugasForm extends Form
{
    public ?Tugas $tugas = null;

    public string $nama = '';

    public string $mata_kuliah_id = '';

    public string $deadline = '';

    public string $deskripsi = '';

    protected ?Kelas $kelas = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'mata_kuliah_id' => [
                'required',
                Rule::exists('mata_kuliah', 'id')->where('kelas_id', $this->kelas?->id),
            ],
            'deadline' => ['required', 'date'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'nama' => 'nama tugas',
            'mata_kuliah_id' => 'mata kuliah',
            'deadline' => 'deadline',
            'deskripsi' => 'deskripsi',
        ];
    }

    public function fillFrom(Tugas $tugas): void
    {
        $this->tugas = $tugas;
        $this->nama = $tugas->nama;
        $this->mata_kuliah_id = (string) $tugas->mata_kuliah_id;
        $this->deadline = $tugas->deadline->format('Y-m-d\TH:i');
        $this->deskripsi = (string) $tugas->deskripsi;
    }

    public function save(Kelas $kelas, User $user): Tugas
    {
        $this->kelas = $kelas;

        $data = $this->validate();

        $attributes = [
            'mata_kuliah_id' => (int) $data['mata_kuliah_id'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] !== '' ? $data['deskripsi'] : null,
            'deadline' => Carbon::parse($data['deadline']),
        ];

        if ($this->tugas !== null) {
            $this->tugas->update($attributes);

            return $this->tugas;
        }

        return Tugas::query()->create([...$attributes, 'created_by' => $user->id]);
    }
}
