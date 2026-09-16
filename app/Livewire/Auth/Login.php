<?php

namespace App\Livewire\Auth;

use App\Support\MasaAktifKelas;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Masuk')]
class Login extends Component
{
    public string $npm = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'npm' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], attributes: ['npm' => 'NPM', 'password' => 'password']);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['npm' => trim($this->npm), 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'npm' => 'NPM atau password salah.',
            ]);
        }

        $kelas = Auth::user()->kelas;

        if ($kelas !== null && ! $kelas->isAktif()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'npm' => MasaAktifKelas::pesan($kelas),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'npm' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::lower(trim($this->npm)).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
