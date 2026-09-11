<?php

namespace App\Livewire\Forms;

use App\Enums\Role;
use App\Models\User;
use App\Rules\NomorHpIndonesia;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public string $npm = '';

    public string $name = '';

    public string $no_hp = '';

    public string $role = Role::Mahasiswa->value;

    public string $kelas_id = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var array<int, string> */
    protected array $allowedRoles = [];

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $needsKelas = $this->role !== Role::SuperAdmin->value;

        return [
            'npm' => ['required', 'string', 'min:6', 'max:20', 'alpha_num:ascii', Rule::unique('users', 'npm')->ignore($this->user?->id)],
            'name' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', new NomorHpIndonesia],
            'role' => ['required', Rule::in($this->allowedRoles)],
            'kelas_id' => [$needsKelas ? 'required' : 'nullable', Rule::exists('kelas', 'id')],
            'password' => [$this->user === null ? 'required' : 'nullable', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'npm' => 'NPM',
            'name' => 'nama',
            'no_hp' => 'nomor HP',
            'role' => 'role',
            'kelas_id' => 'kelas',
            'password' => 'password',
        ];
    }

    public function fillFrom(User $user): void
    {
        $this->user = $user;
        $this->npm = $user->npm;
        $this->name = $user->name;
        $this->no_hp = (string) $user->no_hp;
        $this->role = $user->role->value;
        $this->kelas_id = (string) $user->kelas_id;
        $this->password = '';
        $this->password_confirmation = '';
    }

    /**
     * @param  array<int, string>  $allowedRoles  Roles the acting user may assign.
     * @param  int|null  $lockedKelasId  Kelas forced by the acting user (admin kelas), null for free choice.
     */
    public function save(array $allowedRoles, ?int $lockedKelasId): User
    {
        $this->allowedRoles = $allowedRoles;
        $this->no_hp = trim($this->no_hp) !== '' ? NomorHpIndonesia::normalize($this->no_hp) : '';

        if ($lockedKelasId !== null) {
            $this->kelas_id = (string) $lockedKelasId;
        }

        $data = $this->validate();

        $attributes = [
            'npm' => $data['npm'],
            'name' => $data['name'],
            'no_hp' => ($data['no_hp'] ?? '') !== '' ? $data['no_hp'] : null,
            'role' => $data['role'],
            'kelas_id' => $data['role'] === Role::SuperAdmin->value ? null : (int) $data['kelas_id'],
        ];

        if ($data['password'] !== null && $data['password'] !== '') {
            $attributes['password'] = $data['password'];
        }

        if ($this->user !== null) {
            $this->user->update($attributes);

            return $this->user;
        }

        return User::query()->create($attributes);
    }
}
