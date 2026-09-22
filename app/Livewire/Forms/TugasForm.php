<?php

namespace App\Livewire\Forms;

use App\Livewire\Concerns\WithLampiran;
use App\Models\KategoriKelompok;
use App\Models\Kelas;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TugasForm extends Form
{
    use WithLampiran;

    public ?Tugas $tugas = null;

    public string $nama = '';

    public string $mata_kuliah_id = '';

    /** Tugas kelompok: done per kelompok of the chosen kategori (must belong to the same mata kuliah). */
    public bool $tugas_kelompok = false;

    public string $kategori_kelompok_id = '';

    public string $deadline = '';

    /** Where students hand the work in (Google Form / Drive / LMS); optional. */
    public string $link_pengumpulan = '';

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
            'tugas_kelompok' => ['boolean'],
            'kategori_kelompok_id' => [
                $this->tugas_kelompok ? 'required' : 'nullable',
                Rule::exists('kategori_kelompok', 'id')->where('mata_kuliah_id', (int) $this->mata_kuliah_id),
            ],
            'deadline' => ['required', 'date'],
            'link_pengumpulan' => ['nullable', 'url:http,https', 'max:2048'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            ...$this->rulesLampiran(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'link_pengumpulan.url' => 'Link pengumpulan harus berupa alamat lengkap yang diawali http:// atau https://.',
            'kategori_kelompok_id.required' => 'Pilih kategori kelompok untuk tugas kelompok ini.',
            'kategori_kelompok_id.exists' => 'Kategori kelompok harus berasal dari mata kuliah yang sama dengan tugas.',
            ...$this->messagesLampiran(),
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
            'tugas_kelompok' => 'tugas kelompok',
            'kategori_kelompok_id' => 'kategori kelompok',
            'deadline' => 'deadline',
            'link_pengumpulan' => 'link pengumpulan',
            'deskripsi' => 'deskripsi',
            ...$this->validationAttributesLampiran(),
        ];
    }

    protected function modelLampiran(): string
    {
        return Tugas::class;
    }

    protected function indukLampiran(): ?Model
    {
        return $this->tugas;
    }

    /**
     * Changing the mata kuliah drops a kategori that belongs to the previous one.
     */
    public function updatedMataKuliahId(): void
    {
        if ($this->kategori_kelompok_id !== '' && ! KategoriKelompok::query()->whereKey((int) $this->kategori_kelompok_id)->where('mata_kuliah_id', (int) $this->mata_kuliah_id)->exists()) {
            $this->kategori_kelompok_id = '';
        }
    }

    public function fillFrom(Tugas $tugas): void
    {
        $this->reset();
        $this->tugas = $tugas->loadMissing('lampiran');
        $this->nama = $tugas->nama;
        $this->mata_kuliah_id = (string) $tugas->mata_kuliah_id;
        $this->tugas_kelompok = $tugas->isTugasKelompok();
        $this->kategori_kelompok_id = (string) ($tugas->kategori_kelompok_id ?? '');
        $this->deadline = $tugas->deadline->format('Y-m-d\TH:i');
        $this->link_pengumpulan = (string) $tugas->link_pengumpulan;
        $this->deskripsi = (string) $tugas->deskripsi;
    }

    public function save(Kelas $kelas, User $user, bool $canUpload): Tugas
    {
        $this->kelas = $kelas;
        $this->link_pengumpulan = trim($this->link_pengumpulan);
        $this->siapkanValidasiLampiran($canUpload);

        $data = $this->validate();

        $attributes = [
            'mata_kuliah_id' => (int) $data['mata_kuliah_id'],
            'kategori_kelompok_id' => $data['tugas_kelompok'] ? (int) $data['kategori_kelompok_id'] : null,
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] !== '' ? $data['deskripsi'] : null,
            'deadline' => Carbon::parse($data['deadline']),
            'link_pengumpulan' => ($data['link_pengumpulan'] ?? '') !== '' ? $data['link_pengumpulan'] : null,
        ];

        if ($this->tugas !== null) {
            $this->tugas->update($attributes);
            $this->sinkronkanLampiran($this->tugas, $kelas);

            return $this->tugas;
        }

        $tugas = Tugas::query()->create([...$attributes, 'created_by' => $user->id]);

        $this->sinkronkanLampiran($tugas, $kelas, log: false);

        return $tugas;
    }
}
