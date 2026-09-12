<?php

namespace App\Livewire\Forms;

use App\Models\Informasi;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class InformasiForm extends Form
{
    public ?Informasi $informasi = null;

    public string $judul = '';

    public string $kategori_informasi_id = '';

    public string $isi = '';

    public string $link = '';

    /** New attachment picked in the form (super admin only). */
    public ?TemporaryUploadedFile $lampiran = null;

    /** Tick to drop the attachment already stored on the informasi. */
    public bool $hapus_lampiran = false;

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
            'lampiran' => [
                'nullable',
                'file',
                'max:'.Informasi::LAMPIRAN_MAKS_KB,
                'extensions:'.implode(',', Informasi::LAMPIRAN_EKSTENSI),
                'mimes:'.implode(',', Informasi::LAMPIRAN_EKSTENSI),
            ],
            'hapus_lampiran' => ['boolean'],
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
            'lampiran' => 'lampiran',
            'hapus_lampiran' => 'hapus lampiran',
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
            'lampiran.max' => 'Ukuran lampiran maksimal '.(Informasi::LAMPIRAN_MAKS_KB / 1024).' MB.',
            'lampiran.extensions' => 'Jenis file lampiran tidak didukung.',
            'lampiran.mimes' => 'Jenis file lampiran tidak didukung.',
        ];
    }

    public function fillFrom(Informasi $informasi): void
    {
        $this->reset();
        $this->informasi = $informasi;
        $this->judul = $informasi->judul;
        $this->kategori_informasi_id = (string) $informasi->kategori_informasi_id;
        $this->isi = $informasi->isi;
        $this->link = (string) $informasi->link;
        $this->is_pinned = $informasi->is_pinned;
    }

    public function save(Kelas $kelas, User $user, bool $canPin, bool $canUpload): Informasi
    {
        $this->kelas = $kelas;

        if (! $canUpload) {
            $this->lampiran = null;
            $this->hapus_lampiran = false;
        }

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
            if ($this->lampiran !== null || $this->hapus_lampiran) {
                $this->informasi->hapusLampiran();
                $attributes = [...$attributes, 'lampiran_path' => null, 'lampiran_nama' => null, ...$this->simpanLampiran($kelas)];
            }

            $this->informasi->update($attributes);

            return $this->informasi;
        }

        return Informasi::query()->create([
            ...$attributes,
            ...$this->simpanLampiran($kelas),
            'kelas_id' => $kelas->id,
            'created_by' => $user->id,
        ]);
    }

    /**
     * Store the picked file on the private disk.
     *
     * @return array{lampiran_path?: string, lampiran_nama?: string}
     */
    protected function simpanLampiran(Kelas $kelas): array
    {
        if ($this->lampiran === null) {
            return [];
        }

        $path = $this->lampiran->store(Informasi::LAMPIRAN_DIR.'/'.$kelas->id, Informasi::LAMPIRAN_DISK);

        return [
            'lampiran_path' => $path,
            'lampiran_nama' => mb_substr($this->lampiran->getClientOriginalName(), 0, 255),
        ];
    }
}
