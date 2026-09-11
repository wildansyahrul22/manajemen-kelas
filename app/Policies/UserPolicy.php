<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(User $user, User $target): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && $target->kelas_id === $user->kelas_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Super admin manages everyone; admin manages non-super-admin accounts in their kelas.
     */
    public function update(User $user, User $target): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin()
            && ! $target->isSuperAdmin()
            && $target->kelas_id === $user->kelas_id;
    }

    public function delete(User $user, User $target): bool
    {
        return $target->id !== $user->id && $this->update($user, $target);
    }
}
