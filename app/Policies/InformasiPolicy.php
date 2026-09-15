<?php

namespace App\Policies;

use App\Models\Informasi;
use App\Models\User;

class InformasiPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Informasi $informasi): bool
    {
        return $user->belongsToKelas($informasi->kelas_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Admin of the kelas may edit everything; mahasiswa only their own posts.
     */
    public function update(User $user, Informasi $informasi): bool
    {
        return $user->canManageKelas($informasi->kelas_id)
            || ($informasi->created_by === $user->id && $user->belongsToKelas($informasi->kelas_id));
    }

    public function delete(User $user, Informasi $informasi): bool
    {
        return $this->update($user, $informasi);
    }

    public function pin(User $user, Informasi $informasi): bool
    {
        return $user->canManageKelas($informasi->kelas_id);
    }

    /**
     * Admin kelas and super admin may attach files; mahasiswa share a link instead.
     */
    public function upload(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }
}
