<?php

namespace App\Policies;

use App\Models\Kampus;
use App\Models\User;

class KampusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Kampus $kampus): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Kampus $kampus): bool
    {
        return $user->isSuperAdmin();
    }
}
