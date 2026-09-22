<?php

namespace App\Livewire\Forms;

use App\Livewire\Concerns\WithLampiran;
use App\Models\Informasi;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Form;

class InformasiForm extends Form
{
    use WithLampiran;

    public ?Informasi $informasi = null;

    public string $judul = '';

    public string $kategori_informasi_id = '';

    public string $isi = '';

    public string $link = '';

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
            'link' => ['nullable', 'url:http,https', 'max:2048'],
            ...$this->rulesLampiran(),
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
            'link' => 'tautan',
            ...$this->validationAttributesLampiran(),
            'is_pinned' => 'sematkan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'link.url' => 'Tautan harus berupa alamat lengkap yang diawali http:// atau https://.',
            ...$this->messagesLampiran(),
        ];
    }

    protected function modelLampiran(): string
    {
        return Informasi::class;
    }

    protected function indukLampiran(): ?Model
    {
        return $this->informasi;
    }

    public function fillFrom(Informasi $informasi): void
    {
        $this->reset();
        $this->informasi = $informasi->loadMissing('lampiran');
        $this->judul = $informasi->judul;
        $this->kategori_informasi_id = (string) $informasi->kategori_informasi_id;
        $this->isi = $informasi->isi;
        $this->link = (string) $informasi->link;
        $this->is_pinned = $informasi->is_pinned;
    }

    public function save(Kelas $kelas, User $user, bool $canPin, bool $canUpload): Informasi
    {
        $this->kelas = $kelas;
        $this->siapkanValidasiLampiran($canUpload);

        $data = $this->validate();

        $attributes = [
            'judul' => $data['judul'],
            'kategori_informasi_id' => (int) $data['kategori_informasi_id'],
            'isi' => $data['isi'],
            'link' => $data['link'] !== '' ? $data['link'] : null,
        ];

        if ($canPin) {
            $attributes['is_pinned'] = (bool) $data['is_pinned'];
        }

        if ($this->informasi !== null) {
            $this->informasi->update($attributes);
            $this->sinkronkanLampiran($this->informasi, $kelas);

            return $this->informasi;
        }

        $informasi = Informasi::query()->create([
            ...$attributes,
            'kelas_id' => $kelas->id,
            'created_by' => $user->id,
        ]);

        $this->sinkronkanLampiran($informasi, $kelas, log: false);

        return $informasi;
    }
}
