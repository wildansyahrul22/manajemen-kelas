<?php

namespace App\Livewire\Forms;

use App\Enums\AksiLog;
use App\Models\Informasi;
use App\Models\InformasiLampiran;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class InformasiForm extends Form
{
    public ?Informasi $informasi = null;

    public string $judul = '';

    public string $kategori_informasi_id = '';

    public string $isi = '';

    public string $link = '';

    /**
     * New files picked in the form (admin kelas / super admin only); several at once.
     *
     * @var list<TemporaryUploadedFile>
     */
    public array $lampiran = [];

    /**
     * Ids of attachments already stored on the informasi that should be removed.
     *
     * @var list<int|string>
     */
    public array $hapus_lampiran = [];

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
            'lampiran' => ['array', 'max:'.Informasi::LAMPIRAN_MAKS_JUMLAH],
            'lampiran.*' => [
                'file',
                'max:'.Informasi::LAMPIRAN_MAKS_KB,
                'extensions:'.implode(',', Informasi::LAMPIRAN_EKSTENSI),
                'mimes:'.implode(',', Informasi::LAMPIRAN_EKSTENSI),
            ],
            'hapus_lampiran' => ['array'],
            'hapus_lampiran.*' => ['integer'],
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
            'lampiran.*' => 'lampiran',
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
            'lampiran.max' => 'Maksimal '.Informasi::LAMPIRAN_MAKS_JUMLAH.' file per informasi.',
            'lampiran.*.max' => 'Ukuran tiap lampiran maksimal '.(Informasi::LAMPIRAN_MAKS_KB / 1024).' MB.',
            'lampiran.*.extensions' => 'Jenis file lampiran tidak didukung.',
            'lampiran.*.mimes' => 'Jenis file lampiran tidak didukung.',
        ];
    }

    /**
     * The total after removals and additions may not exceed the per-informasi limit.
     */
    protected function pastikanJumlahLampiran(Validator $validator): void
    {
        if ($validator->errors()->has('lampiran')) {
            return;
        }

        $total = $this->lampiranTersimpan()->count() - $this->idHapus()->count() + count($this->lampiran);

        if ($total > Informasi::LAMPIRAN_MAKS_JUMLAH) {
            $validator->errors()->add('lampiran', 'Maksimal '.Informasi::LAMPIRAN_MAKS_JUMLAH.' file per informasi (sudah ada '.$this->lampiranTersimpan()->count().').');
        }
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

        if (! $canUpload) {
            $this->lampiran = [];
            $this->hapus_lampiran = [];
        }

        $this->withValidator(fn (Validator $validator) => $validator->after(fn (Validator $validator) => $this->pastikanJumlahLampiran($validator)));

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

    /**
     * Remove the ticked attachments and store the newly picked files; on edit, log the change of the
     * attachment list as an "ubah" entry so the activity log shows it (rows live in another table).
     */
    protected function sinkronkanLampiran(Informasi $informasi, Kelas $kelas, bool $log = true): void
    {
        $sebelum = $this->lampiranTersimpan()->pluck('nama');

        if ($this->idHapus()->isNotEmpty()) {
            $informasi->lampiran()->whereKey($this->idHapus())->get()->each->delete();
        }

        foreach ($this->lampiran as $file) {
            $informasi->lampiran()->create([
                'path' => $file->store(Informasi::LAMPIRAN_DIR.'/'.$kelas->id, Informasi::LAMPIRAN_DISK),
                'nama' => mb_substr($file->getClientOriginalName(), 0, 255),
                'ukuran' => $file->getSize(),
            ]);
        }

        $sesudah = $informasi->lampiran()->pluck('nama');

        if ($log && $sebelum->all() !== $sesudah->all()) {
            $informasi->catatAktivitas(AksiLog::Ubah, [
                'lampiran' => [$sebelum->implode(', ') ?: null, $sesudah->implode(', ') ?: null],
            ]);
        }

        $informasi->unsetRelation('lampiran');
        $this->lampiran = [];
        $this->hapus_lampiran = [];
    }

    /**
     * Attachments already stored on the informasi being edited. Livewire re-fetches the model
     * without relations on every request, so the relation is (re)loaded here.
     *
     * @return EloquentCollection<int, InformasiLampiran>
     */
    public function lampiranTersimpan(): EloquentCollection
    {
        return $this->informasi?->loadMissing('lampiran')->lampiran ?? new EloquentCollection;
    }

    /**
     * @return Collection<int, int>
     */
    protected function idHapus(): Collection
    {
        $tersimpan = $this->lampiranTersimpan()->pluck('id');

        return collect($this->hapus_lampiran)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $tersimpan->contains($id))
            ->values();
    }
}
