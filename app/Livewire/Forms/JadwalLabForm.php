<?php

namespace App\Livewire\Forms;

use App\Models\JadwalLab;
use App\Models\Kelas;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * One mata kuliah with one or more praktikum sesi (tanggal + jam + ruangan). Creating saves every
 * sesi as its own JadwalLab row; editing works on a single existing row.
 */
class JadwalLabForm extends Form
{
    public ?JadwalLab $jadwalLab = null;

    public string $mata_kuliah_id = '';

    /**
     * Keyed by a random id so removing a row never shifts the others' wire:model paths.
     *
     * @var array<string, array{tanggal: string, jam_mulai: string, jam_selesai: string, ruangan: string, keterangan: string}>
     */
    public array $sesi = [];

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
            'sesi' => ['required', 'array', 'min:1'],
            'sesi.*.tanggal' => ['required', 'date_format:Y-m-d'],
            'sesi.*.jam_mulai' => ['required', 'date_format:H:i'],
            'sesi.*.jam_selesai' => ['required', 'date_format:H:i', 'after:sesi.*.jam_mulai'],
            'sesi.*.ruangan' => ['nullable', 'string', 'max:50'],
            'sesi.*.keterangan' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'mata_kuliah_id' => 'mata kuliah',
            'sesi' => 'jadwal lab',
            'sesi.*.tanggal' => 'tanggal',
            'sesi.*.jam_mulai' => 'jam mulai',
            'sesi.*.jam_selesai' => 'jam selesai',
            'sesi.*.ruangan' => 'ruangan',
            'sesi.*.keterangan' => 'keterangan',
        ];
    }

    public function startCreate(?int $mataKuliahId = null): void
    {
        $this->reset();
        $this->mata_kuliah_id = (string) ($mataKuliahId ?? '');
        $this->addSesi();
    }

    public function fillFrom(JadwalLab $jadwalLab): void
    {
        $this->reset();
        $this->jadwalLab = $jadwalLab;
        $this->mata_kuliah_id = (string) $jadwalLab->mata_kuliah_id;
        $this->sesi = [
            Str::random(8) => [
                'tanggal' => $jadwalLab->tanggal->format('Y-m-d'),
                'jam_mulai' => $jadwalLab->jam_mulai->format('H:i'),
                'jam_selesai' => $jadwalLab->jam_selesai->format('H:i'),
                'ruangan' => (string) $jadwalLab->ruangan,
                'keterangan' => (string) $jadwalLab->keterangan,
            ],
        ];
    }

    /**
     * Praktikum usually repeats weekly at the same jam and ruangan, so a new row copies those
     * from the last row and moves the tanggal one week ahead; keterangan starts empty.
     */
    public function addSesi(): void
    {
        $terakhir = $this->sesi !== [] ? end($this->sesi) : null;

        $this->sesi[Str::random(8)] = [
            'tanggal' => self::mingguBerikutnya($terakhir['tanggal'] ?? ''),
            'jam_mulai' => $terakhir['jam_mulai'] ?? '',
            'jam_selesai' => $terakhir['jam_selesai'] ?? '',
            'ruangan' => $terakhir['ruangan'] ?? '',
            'keterangan' => '',
        ];
    }

    public function removeSesi(string $key): void
    {
        if (count($this->sesi) <= 1) {
            return;
        }

        unset($this->sesi[$key]);
    }

    /**
     * @return Collection<int, JadwalLab>
     */
    public function save(Kelas $kelas): Collection
    {
        $this->kelas = $kelas;

        $data = $this->validate();

        $rows = collect($data['sesi'])->map(fn (array $sesi) => [
            'mata_kuliah_id' => (int) $data['mata_kuliah_id'],
            'tanggal' => $sesi['tanggal'],
            'jam_mulai' => $sesi['jam_mulai'],
            'jam_selesai' => $sesi['jam_selesai'],
            'ruangan' => ($sesi['ruangan'] ?? '') !== '' ? $sesi['ruangan'] : null,
            'keterangan' => ($sesi['keterangan'] ?? '') !== '' ? $sesi['keterangan'] : null,
        ]);

        if ($this->jadwalLab !== null) {
            $this->jadwalLab->update($rows->first());

            return collect([$this->jadwalLab]);
        }

        return $rows
            ->sortBy(fn (array $attributes) => $attributes['tanggal'].' '.$attributes['jam_mulai'])
            ->map(fn (array $attributes) => JadwalLab::query()->create($attributes))
            ->values();
    }

    /**
     * The date one week after the given Y-m-d string, or empty when it is not a valid date yet.
     */
    private static function mingguBerikutnya(string $tanggal): string
    {
        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $tanggal);
        } catch (InvalidFormatException) {
            return '';
        }

        return $parsed->format('Y-m-d') === $tanggal ? $parsed->addWeek()->format('Y-m-d') : '';
    }
}
