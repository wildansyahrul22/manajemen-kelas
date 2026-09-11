<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Kelas;
use App\Models\User;
use App\Rules\NomorHpIndonesia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Import a class roster from a CSV file (header: npm, nama, optional no_hp).
 *
 * Example: php artisan mahasiswa:import storage/app/ti-r8.csv --kelas=TI-R8
 */
class ImportMahasiswa extends Command
{
    protected $signature = 'mahasiswa:import
        {file : Path file CSV dengan header npm, nama, (opsional) no_hp}
        {--kelas= : Nama kelas tujuan (harus sudah ada)}
        {--password= : Password awal untuk semua akun (default: sama dengan NPM)}
        {--dry-run : Hanya menampilkan hasil validasi tanpa menyimpan}';

    protected $description = 'Import mahasiswa ke sebuah kelas dari file CSV';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_readable($path)) {
            $this->error("File tidak ditemukan atau tidak bisa dibaca: {$path}");

            return self::FAILURE;
        }

        $kelas = Kelas::query()->where('nama', $this->option('kelas'))->first();

        if ($kelas === null) {
            $this->error('Kelas tidak ditemukan. Gunakan --kelas=<nama kelas yang sudah ada>.');

            return self::FAILURE;
        }

        $rows = $this->readCsv($path);

        if ($rows === []) {
            $this->warn('Tidak ada baris data pada file.');

            return self::SUCCESS;
        }

        [$valid, $invalid, $skipped] = $this->validateRows($rows);

        foreach ($invalid as $row) {
            $this->warn("Baris {$row['line']} dilewati: {$row['error']}");
        }

        foreach ($skipped as $row) {
            $this->line("Baris {$row['line']} dilewati: NPM {$row['npm']} sudah terdaftar.");
        }

        if ($valid === []) {
            $this->warn('Tidak ada mahasiswa baru yang bisa diimport.');

            return self::SUCCESS;
        }

        $this->table(['NPM', 'Nama', 'No. HP'], array_map(fn ($row) => [$row['npm'], $row['nama'], $row['no_hp'] ?? '—'], $valid));

        if ($this->option('dry-run')) {
            $this->info(count($valid).' mahasiswa siap diimport (dry-run, tidak ada yang disimpan).');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($valid, $kelas) {
            foreach ($valid as $row) {
                User::query()->create([
                    'npm' => $row['npm'],
                    'name' => $row['nama'],
                    'no_hp' => $row['no_hp'],
                    'password' => $this->option('password') ?: $row['npm'],
                    'role' => Role::Mahasiswa,
                    'kelas_id' => $kelas->id,
                ]);
            }
        });

        $this->info(sprintf('%d mahasiswa ditambahkan ke kelas %s (%d dilewati).', count($valid), $kelas->nama, count($invalid) + count($skipped)));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{line: int, npm: string, nama: string, no_hp: string|null}>
     */
    protected function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = null;
        $rows = [];
        $line = 0;

        while (($data = fgetcsv($handle, escape: '\\')) !== false) {
            $line++;

            if ($header === null) {
                $header = array_map(fn ($column) => strtolower(trim((string) $column)), $data);

                continue;
            }

            $row = array_combine($header, array_pad($data, count($header), null));

            if ($row === false || trim((string) ($row['npm'] ?? '')) === '') {
                continue;
            }

            $noHp = trim((string) ($row['no_hp'] ?? ''));

            $rows[] = [
                'line' => $line,
                'npm' => trim((string) $row['npm']),
                'nama' => trim((string) ($row['nama'] ?? $row['name'] ?? '')),
                'no_hp' => $noHp !== '' ? NomorHpIndonesia::normalize($noHp) : null,
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<int, array{line: int, npm: string, nama: string, no_hp: string|null}>  $rows
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: array<int, array<string, mixed>>}
     */
    protected function validateRows(array $rows): array
    {
        $existing = User::query()->whereIn('npm', array_column($rows, 'npm'))->pluck('npm')->all();
        $seen = [];
        $valid = $invalid = $skipped = [];

        foreach ($rows as $row) {
            if (in_array($row['npm'], $existing, true)) {
                $skipped[] = $row;

                continue;
            }

            $validator = Validator::make($row, [
                'npm' => ['required', 'string', 'min:6', 'max:20', 'alpha_num:ascii', Rule::notIn($seen)],
                'nama' => ['required', 'string', 'max:100'],
                'no_hp' => ['nullable', 'string', new NomorHpIndonesia],
            ], ['npm.not_in' => 'NPM duplikat di dalam file.']);

            if ($validator->fails()) {
                $invalid[] = [...$row, 'error' => $validator->errors()->first()];

                continue;
            }

            $seen[] = $row['npm'];
            $valid[] = $row;
        }

        return [$valid, $invalid, $skipped];
    }
}
