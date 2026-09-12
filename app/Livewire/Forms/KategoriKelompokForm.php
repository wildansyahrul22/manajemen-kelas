<?php

namespace App\Livewire\Forms;

use App\Models\KategoriKelompok;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class KategoriKelompokForm extends Form
{
    public ?KategoriKelompok $kategori = null;

    public string $nama = '';

    public string $mata_kuliah_id = '';

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
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori_kelompok', 'nama')
                    ->where('mata_kuliah_id', (int) $this->mata_kuliah_id)
                    ->ignore($this->kategori?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return ['nama' => 'nama kategori', 'mata_kuliah_id' => 'mata kuliah'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['nama.unique' => 'Kategori dengan nama ini sudah ada pada mata kuliah tersebut.'];
    }

    public function fillFrom(KategoriKelompok $kategori): void
    {
        $this->kategori = $kategori;
        $this->nama = $kategori->nama;
        $this->mata_kuliah_id = (string) $kategori->mata_kuliah_id;
    }

    public function save(Kelas $kelas, User $user): KategoriKelompok
    {
        $this->kelas = $kelas;

        $data = $this->validate();

        $attributes = [
            'mata_kuliah_id' => (int) $data['mata_kuliah_id'],
            'nama' => $data['nama'],
        ];

        if ($this->kategori !== null) {
            $this->kategori->update($attributes);

            return $this->kategori;
        }

        return KategoriKelompok::query()->create([...$attributes, 'created_by' => $user->id]);
    }
}
