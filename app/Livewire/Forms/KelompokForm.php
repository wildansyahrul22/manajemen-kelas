<?php

namespace App\Livewire\Forms;

use App\Enums\AksiLog;
use App\Enums\Role;
use App\Models\KategoriKelompok;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Form;

class KelompokForm extends Form
{
    public ?Kelompok $kelompok = null;

    public string $nama = '';

    public string $kategori_kelompok_id = '';

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
            'kategori_kelompok_id' => [
                'required',
                Rule::exists('kategori_kelompok', 'id')->whereIn('mata_kuliah_id', MataKuliah::query()->select('id')->where('kelas_id', $this->kelas?->id)),
                $this->kategoriBelumFinal(),
            ],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'anggota' => ['required', 'array', 'min:1'],
            'anggota.*' => [
                'integer',
                Rule::exists('users', 'id')
                    ->where('kelas_id', $this->kelas?->id)
                    ->whereIn('role', [Role::Mahasiswa->value, Role::Admin->value])
                    // Kelas terbang students only count in their own semester.
                    ->where(fn ($query) => $query
                        ->whereNull('kelas_terbang_semester_id')
                        ->orWhere('kelas_terbang_semester_id', $this->kelas?->semester_aktif_id)),
                $this->belumPunyaKelompokDiKategori(),
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
            'kategori_kelompok_id' => 'kategori kelompok',
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

    /**
     * Changing kategori drops members that already belong to a kelompok of the new kategori.
     */
    public function updatedKategoriKelompokId(): void
    {
        $kategoriId = (int) $this->kategori_kelompok_id;

        if ($kategoriId === 0 || $this->anggota === []) {
            $this->anggota = [];
            $this->ketua_id = '';

            return;
        }

        $terpakai = KategoriKelompok::anggotaIds($kategoriId, $this->kelompok?->id)->pluck('user_id')->all();

        $this->anggota = array_values(array_diff(array_map('intval', $this->anggota), $terpakai));

        if (! in_array((int) $this->ketua_id, $this->anggota, true)) {
            $this->ketua_id = '';
        }
    }

    public function fillFrom(Kelompok $kelompok): void
    {
        $this->kelompok = $kelompok;
        $this->nama = $kelompok->nama;
        $this->kategori_kelompok_id = (string) $kelompok->kategori_kelompok_id;
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

        $kategori = KategoriKelompok::query()->findOrFail((int) $data['kategori_kelompok_id']);

        $attributes = [
            'nama' => $data['nama'],
            'kategori_kelompok_id' => $kategori->id,
            'mata_kuliah_id' => $kategori->mata_kuliah_id,
            'deskripsi' => $data['deskripsi'] !== '' ? $data['deskripsi'] : null,
        ];

        $ketuaId = $data['ketua_id'] !== '' ? (int) $data['ketua_id'] : null;

        $sync = collect($data['anggota'])
            ->mapWithKeys(fn (int $id) => [$id => ['is_ketua' => $id === $ketuaId]])
            ->all();

        return DB::transaction(function () use ($attributes, $sync, $user) {
            $kelompok = $this->kelompok ?? new Kelompok(['created_by' => $user->id]);
            $sebelum = $this->kelompok !== null ? $this->ringkasanAnggota($kelompok) : null;

            $kelompok->fill($attributes)->save();
            $kelompok->anggota()->sync($sync);

            // Pivot syncs fire no model events, so membership changes are logged here.
            if ($sebelum !== null) {
                $sesudah = $this->ringkasanAnggota($kelompok);
                $perubahan = array_filter([
                    'anggota' => [$sebelum['anggota'], $sesudah['anggota']],
                    'ketua' => [$sebelum['ketua'], $sesudah['ketua']],
                ], fn (array $pasangan) => $pasangan[0] !== $pasangan[1]);

                if ($perubahan !== []) {
                    $kelompok->catatAktivitas(AksiLog::Ubah, $perubahan);
                }
            }

            return $kelompok;
        });
    }

    /**
     * @return array{anggota: string, ketua: ?string}
     */
    protected function ringkasanAnggota(Kelompok $kelompok): array
    {
        $anggota = $kelompok->anggota()->get(['users.name', 'kelompok_anggota.is_ketua']);

        return [
            'anggota' => $anggota->pluck('name')->sort()->values()->join(', '),
            'ketua' => $anggota->first(fn (User $user) => (bool) $user->pivot->is_ketua)?->name,
        ];
    }

    /**
     * A student can only sit in one kelompok per kategori.
     */
    /**
     * A kategori marked final is frozen: no kelompok may be created in it or moved into it.
     */
    protected function kategoriBelumFinal(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $kategori = KategoriKelompok::query()->find((int) $value);

            if ($kategori?->isFinal()) {
                $fail("Kategori {$kategori->nama} sudah final, kelompoknya tidak bisa diubah lagi.");
            }
        };
    }

    protected function belumPunyaKelompokDiKategori(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $kategoriId = (int) $this->kategori_kelompok_id;

            if ($kategoriId === 0) {
                return;
            }

            $sudahAda = KategoriKelompok::anggotaIds($kategoriId, $this->kelompok?->id)
                ->where('kelompok_anggota.user_id', (int) $value)
                ->exists();

            if ($sudahAda) {
                $nama = User::query()->whereKey((int) $value)->value('name') ?? 'Anggota';
                $fail("{$nama} sudah tergabung di kelompok lain pada kategori ini.");
            }
        };
    }
}
