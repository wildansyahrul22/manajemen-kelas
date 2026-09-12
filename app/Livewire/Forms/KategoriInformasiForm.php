<?php

namespace App\Livewire\Forms;

use App\Models\KategoriInformasi;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class KategoriInformasiForm extends Form
{
    public ?KategoriInformasi $kategori = null;

    public string $nama = '';

    public string $warna = 'slate';

    protected ?Kelas $kelas = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kategori_informasi', 'nama')
                    ->where('kelas_id', $this->kelas?->id)
                    ->ignore($this->kategori?->id),
            ],
            'warna' => ['required', Rule::in(array_keys(KategoriInformasi::WARNA))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return ['nama' => 'nama kategori', 'warna' => 'warna'];
    }

    public function fillFrom(KategoriInformasi $kategori): void
    {
        $this->kategori = $kategori;
        $this->nama = $kategori->nama;
        $this->warna = $kategori->warna;
    }

    public function save(Kelas $kelas, User $user): KategoriInformasi
    {
        $this->kelas = $kelas;

        $data = $this->validate();

        if ($this->kategori !== null) {
            $this->kategori->update($data);

            return $this->kategori;
        }

        return KategoriInformasi::query()->create([...$data, 'kelas_id' => $kelas->id, 'created_by' => $user->id]);
    }
}
