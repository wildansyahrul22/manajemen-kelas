<?php

namespace App\Policies;

use App\Models\KategoriInformasi;
use App\Models\User;

class KategoriInformasiPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, KategoriInformasi $kategori): bool
    {
        return $user->belongsToKelas($kategori->kelas_id);
    }

    public function delete(User $user, KategoriInformasi $kategori): bool
    {
        return $this->update($user, $kategori);
    }
}
