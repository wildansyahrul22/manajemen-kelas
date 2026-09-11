<?php

namespace App\Livewire\Forms;

use App\Enums\Role;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Form;

class KelompokForm extends Form
{
    public ?Kelompok $kelompok = null;

    public string $nama = '';

    public string $mata_kuliah_id = '';

    public string $deskripsi = '';

    /** @var array<int, int> */
    public array $anggota = [];

    public string $ketua_id = '';

    protected ?Kelas $kelas = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'mata_kuliah_id' => [
                'required',
                Rule::exists('mata_kuliah', 'id')->where('kelas_id', $this->kelas?->id),
            ],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'anggota' => ['required', 'array', 'min:1'],
            'anggota.*' => [
                'integer',
                Rule::exists('users', 'id')
                    ->where('kelas_id', $this->kelas?->id)
                    ->whereIn('role', [Role::Mahasiswa->value, Role::Admin->value]),
            ],
            'ketua_id' => ['nullable', 'integer', Rule::in($this->anggota)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'nama' => 'nama kelompok',
            'mata_kuliah_id' => 'mata kuliah',
            'deskripsi' => 'deskripsi',
            'anggota' => 'anggota',
            'anggota.*' => 'anggota',
            'ketua_id' => 'ketua',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'anggota.required' => 'Pilih minimal satu anggota.',
            'anggota.min' => 'Pilih minimal satu anggota.',
            'ketua_id.in' => 'Ketua harus dipilih dari anggota kelompok.',
        ];
    }

    public function fillFrom(Kelompok $kelompok): void
    {
        $this->kelompok = $kelompok;
        $this->nama = $kelompok->nama;
        $this->mata_kuliah_id = (string) $kelompok->mata_kuliah_id;
        $this->deskripsi = (string) $kelompok->deskripsi;

        $anggota = $kelompok->anggota()->get(['users.id', 'kelompok_anggota.is_ketua']);

        $this->anggota = $anggota->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->ketua_id = (string) ($anggota->first(fn (User $user) => (bool) $user->pivot->is_ketua)?->id ?? '');
    }

    public function save(Kelas $kelas, User $user): Kelompok
    {
        $this->kelas = $kelas;
        $this->anggota = array_values(array_unique(array_map('intval', $this->anggota)));

        $data = $this->validate();

        $attributes = [
            'nama' => $data['nama'],
            'mata_kuliah_id' => (int) $data['mata_kuliah_id'],
            'deskripsi' => $data['deskripsi'] !== '' ? $data['deskripsi'] : null,
        ];

        $ketuaId = $data['ketua_id'] !== '' ? (int) $data['ketua_id'] : null;

        $sync = collect($data['anggota'])
            ->mapWithKeys(fn (int $id) => [$id => ['is_ketua' => $id === $ketuaId]])
            ->all();

        return DB::transaction(function () use ($attributes, $sync, $user) {
            $kelompok = $this->kelompok ?? new Kelompok(['created_by' => $user->id]);
            $kelompok->fill($attributes)->save();
            $kelompok->anggota()->sync($sync);

            return $kelompok;
        });
    }
}
