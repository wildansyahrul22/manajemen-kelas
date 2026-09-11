<?php

namespace App\Livewire\Profile;

use App\Livewire\Concerns\Notifies;
use App\Rules\NomorHpIndonesia;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profil Saya')]
class Edit extends Component
{
    use Notifies;

    public string $name = '';

    public string $no_hp = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->no_hp = (string) $user->no_hp;
    }

    public function updateProfile(): void
    {
        $this->no_hp = trim($this->no_hp) !== '' ? NomorHpIndonesia::normalize($this->no_hp) : '';

        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', new NomorHpIndonesia],
        ], attributes: ['name' => 'nama', 'no_hp' => 'nomor HP']);

        auth()->user()->update([
            'name' => $data['name'],
            'no_hp' => ($data['no_hp'] ?? '') !== '' ? $data['no_hp'] : null,
        ]);

        $this->notify('Profil berhasil diperbarui.');
    }

    public function updatePassword(): void
    {
        $data = $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], attributes: ['current_password' => 'password saat ini', 'password' => 'password baru']);

        auth()->user()->update(['password' => $data['password']]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->notify('Password berhasil diubah.');
    }

    public function render()
    {
        return view('livewire.profile.edit', ['user' => auth()->user()->loadMissing('kelas:id,nama')]);
    }
}
