<?php

namespace App\Livewire\Forms;

use App\Models\Informasi;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class InformasiForm extends Form
{
    public ?Informasi $informasi = null;

    public string $judul = '';

    public string $kategori_informasi_id = '';

    public string $isi = '';

    public bool $is_pinned = false;

    protected ?Kelas $kelas = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            'kategori_informasi_id' => [
                'required',
                Rule::exists('kategori_informasi', 'id')->where('kelas_id', $this->kelas?->id),
            ],
            'isi' => ['required', 'string', 'max:10000'],
            'is_pinned' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'judul' => 'judul',
            'kategori_informasi_id' => 'kategori',
            'isi' => 'isi informasi',
            'is_pinned' => 'sematkan',
        ];
    }

    public function fillFrom(Informasi $informasi): void
    {
        $this->informasi = $informasi;
        $this->judul = $informasi->judul;
        $this->kategori_informasi_id = (string) $informasi->kategori_informasi_id;
        $this->isi = $informasi->isi;
        $this->is_pinned = $informasi->is_pinned;
    }

    public function save(Kelas $kelas, User $user, bool $canPin): Informasi
    {
        $this->kelas = $kelas;

        $data = $this->validate();

        $attributes = [
            'judul' => $data['judul'],
            'kategori_informasi_id' => (int) $data['kategori_informasi_id'],
            'isi' => $data['isi'],
        ];

        if ($canPin) {
            $attributes['is_pinned'] = (bool) $data['is_pinned'];
        }

        if ($this->informasi !== null) {
            $this->informasi->update($attributes);

            return $this->informasi;
        }

        return Informasi::query()->create([
            ...$attributes,
            'kelas_id' => $kelas->id,
            'created_by' => $user->id,
        ]);
    }
}
