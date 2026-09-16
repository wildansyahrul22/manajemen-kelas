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

    /**
     * Admin of the kelas may edit everything; mahasiswa only kategori they created, and never one
     * that has been marked final.
     */
    public function update(User $user, KategoriKelompok $kategori): bool
    {
        $kelasId = $kategori->mataKuliah->kelas_id;

        if ($user->canManageKelas($kelasId)) {
            return true;
        }

        return ! $kategori->isFinal()
            && $kategori->created_by === $user->id
            && $user->belongsToKelas($kelasId);
    }

    public function delete(User $user, KategoriKelompok $kategori): bool
    {
        return $this->update($user, $kategori);
    }

    /**
     * Whether kelompok inside this kategori may be arranged: every member of the kelas while the
     * kategori is open, nobody once it is final.
     */
    public function kelolaKelompok(User $user, KategoriKelompok $kategori): bool
    {
        return ! $kategori->isFinal() && $user->belongsToKelas($kategori->mataKuliah->kelas_id);
    }

    /**
     * Locking a kategori (and unlocking it again) is reserved for admin kelas and super admin.
     */
    public function setFinal(User $user, KategoriKelompok $kategori): bool
    {
        return $user->canManageKelas($kategori->mataKuliah->kelas_id);
    }
}
