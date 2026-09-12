<?php

use App\Http\Controllers\InformasiLampiranController;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Informasi;
use App\Livewire\Jadwal;
use App\Livewire\KategoriInformasi;
use App\Livewire\KategoriKelompok;
use App\Livewire\Kelas;
use App\Livewire\Kelompok;
use App\Livewire\LogAktivitas;
use App\Livewire\MataKuliah;
use App\Livewire\Profile;
use App\Livewire\SemesterAktif;
use App\Livewire\Tugas;
use App\Livewire\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::redirect('/', '/dashboard');

    Route::livewire('/profile', Profile\Edit::class)->name('profile.edit');

    // Pages scoped to a kelas (own kelas, or the one picked in the header by super admin).
    Route::middleware('kelas')->group(function () {
        Route::livewire('/dashboard', Dashboard::class)->name('dashboard');

        Route::livewire('/tugas', Tugas\Index::class)->name('tugas.index');
        Route::livewire('/tugas/{tugas}', Tugas\Show::class)->name('tugas.show');

        Route::livewire('/jadwal', Jadwal\Index::class)->name('jadwal.index');

        Route::livewire('/mata-kuliah', MataKuliah\Index::class)->name('mata-kuliah.index');
        Route::livewire('/mata-kuliah/{mataKuliah}', MataKuliah\Show::class)->name('mata-kuliah.show');

        Route::livewire('/informasi', Informasi\Index::class)->name('informasi.index');
        Route::livewire('/informasi/{informasi}', Informasi\Show::class)->name('informasi.show');
        Route::get('/informasi/{informasi}/lampiran', InformasiLampiranController::class)->name('informasi.lampiran');

        Route::livewire('/kategori-informasi', KategoriInformasi\Index::class)->name('kategori-informasi.index');

        Route::livewire('/kelompok', Kelompok\Index::class)->name('kelompok.index');
        Route::livewire('/kelompok/{kelompok}', Kelompok\Show::class)->name('kelompok.show');

        Route::livewire('/kategori-kelompok', KategoriKelompok\Index::class)->name('kategori-kelompok.index');

        Route::middleware('role:admin,super_admin')->group(function () {
            Route::livewire('/users', Users\Index::class)->name('users.index');
            Route::livewire('/users/{user}', Users\Show::class)->name('users.show');
        });
    });

    Route::middleware('role:admin,super_admin')->group(function () {
        Route::livewire('/semester-aktif', SemesterAktif\Index::class)->name('semester-aktif.index');
        Route::livewire('/log-aktivitas', LogAktivitas\Index::class)->name('log-aktivitas.index');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::livewire('/kelas', Kelas\Index::class)->name('kelas.index');
    });
});
