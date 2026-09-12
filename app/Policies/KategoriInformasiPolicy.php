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

    /**
     * Admin of the kelas may edit everything; mahasiswa only kategori they created.
     */
    public function update(User $user, KategoriInformasi $kategori): bool
    {
        return $user->canManageKelas($kategori->kelas_id)
            || ($kategori->created_by === $user->id && $user->belongsToKelas($kategori->kelas_id));
    }

    public function delete(User $user, KategoriInformasi $kategori): bool
    {
        return $this->update($user, $kategori);
    }
}
