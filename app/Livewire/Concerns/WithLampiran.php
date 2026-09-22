<?php

namespace App\Livewire\Concerns;

use App\Enums\AksiLog;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Attachment handling shared by the form objects of models that use HasLampiran (informasi,
 * tugas): the picked files, the stored ones ticked for removal, their validation, and storing /
 * removing the files on save. The form says which model it edits through modelLampiran() and
 * indukLampiran().
 */
trait WithLampiran
{
    /**
     * New files picked in the form (admin kelas / super admin only); several at once.
     *
     * @var list<TemporaryUploadedFile>
     */
    public array $lampiran = [];

    /**
     * Ids of attachments already stored on the record that should be removed.
     *
     * @var list<int|string>
     */
    public array $hapus_lampiran = [];

    /**
     * Class of the parent model, for its LAMPIRAN_* limits.
     *
     * @return class-string<Model>
     */
    abstract protected function modelLampiran(): string;

    /**
     * The record being edited, or null while creating.
     */
    abstract protected function indukLampiran(): ?Model;

    /**
     * How the parent is called in messages: "per informasi", "per tugas".
     */
    protected function sebutanLampiran(): string
    {
        return Str::lower(class_basename($this->modelLampiran()));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rulesLampiran(): array
    {
        $model = $this->modelLampiran();

        return [
            'lampiran' => ['array', 'max:'.$model::LAMPIRAN_MAKS_JUMLAH],
            'lampiran.*' => [
                'file',
                'max:'.$model::LAMPIRAN_MAKS_KB,
                'extensions:'.implode(',', $model::LAMPIRAN_EKSTENSI),
                'mimes:'.implode(',', $model::LAMPIRAN_EKSTENSI),
            ],
            'hapus_lampiran' => ['array'],
            'hapus_lampiran.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributesLampiran(): array
    {
        return [
            'lampiran' => 'lampiran',
            'lampiran.*' => 'lampiran',
            'hapus_lampiran' => 'hapus lampiran',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messagesLampiran(): array
    {
        $model = $this->modelLampiran();

        return [
            'lampiran.max' => 'Maksimal '.$model::LAMPIRAN_MAKS_JUMLAH.' file per '.$this->sebutanLampiran().'.',
            'lampiran.*.max' => 'Ukuran tiap lampiran maksimal '.($model::LAMPIRAN_MAKS_KB / 1024).' MB.',
            'lampiran.*.extensions' => 'Jenis file lampiran tidak didukung.',
            'lampiran.*.mimes' => 'Jenis file lampiran tidak didukung.',
        ];
    }

    /**
     * Call before validate(): drops the picked files when the user may not upload, and adds the
     * per-record total check to the validator.
     */
    protected function siapkanValidasiLampiran(bool $canUpload): void
    {
        if (! $canUpload) {
            $this->lampiran = [];
            $this->hapus_lampiran = [];
        }

        $this->withValidator(fn (Validator $validator) => $validator->after(fn (Validator $validator) => $this->pastikanJumlahLampiran($validator)));
    }

    /**
     * The total after removals and additions may not exceed the per-record limit.
     */
    protected function pastikanJumlahLampiran(Validator $validator): void
    {
        if ($validator->errors()->has('lampiran')) {
            return;
        }

        $maks = $this->modelLampiran()::LAMPIRAN_MAKS_JUMLAH;
        $total = $this->lampiranTersimpan()->count() - $this->idHapus()->count() + count($this->lampiran);

        if ($total > $maks) {
            $validator->errors()->add('lampiran', 'Maksimal '.$maks.' file per '.$this->sebutanLampiran().' (sudah ada '.$this->lampiranTersimpan()->count().').');
        }
    }

    /**
     * Remove the ticked attachments and store the newly picked files; on edit, log the change of the
     * attachment list as an "ubah" entry so the activity log shows it (rows live in another table).
     */
    protected function sinkronkanLampiran(Model $induk, Kelas $kelas, bool $log = true): void
    {
        $model = $this->modelLampiran();
        $sebelum = $this->lampiranTersimpan()->pluck('nama');

        if ($this->idHapus()->isNotEmpty()) {
            $induk->lampiran()->whereKey($this->idHapus())->get()->each->delete();
        }

        foreach ($this->lampiran as $file) {
            $induk->lampiran()->create([
                'path' => $file->store($model::LAMPIRAN_DIR.'/'.$kelas->id, $model::LAMPIRAN_DISK),
                'nama' => mb_substr($file->getClientOriginalName(), 0, 255),
                'ukuran' => $file->getSize(),
            ]);
        }

        $sesudah = $induk->lampiran()->pluck('nama');

        if ($log && $sebelum->all() !== $sesudah->all()) {
            $induk->catatAktivitas(AksiLog::Ubah, [
                'lampiran' => [$sebelum->implode(', ') ?: null, $sesudah->implode(', ') ?: null],
            ]);
        }

        $induk->unsetRelation('lampiran');
        $this->lampiran = [];
        $this->hapus_lampiran = [];
    }

    /**
     * Attachments already stored on the record being edited. Livewire re-fetches the model
     * without relations on every request, so the relation is (re)loaded here.
     *
     * @return EloquentCollection<int, Model>
     */
    public function lampiranTersimpan(): EloquentCollection
    {
        return $this->indukLampiran()?->loadMissing('lampiran')->lampiran ?? new EloquentCollection;
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
