<?php

namespace App\Policies;

use App\Models\KategoriKelompok;
use App\Models\User;

class KategoriKelompokPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, KategoriKelompok $kategori): bool
    {
        return $user->belongsToKelas($kategori->mataKuliah->kelas_id);
    }

    public function delete(User $user, KategoriKelompok $kategori): bool
    {
        return $this->update($user, $kategori);
    }
}
