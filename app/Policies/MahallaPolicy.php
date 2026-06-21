<?php

namespace App\Policies;

use App\Models\Mahalla;
use App\Models\User;

class MahallaPolicy
{
    public function create(User $user): bool
    {
        return $user->isInvest();
    }

    public function update(User $user, Mahalla $mahalla): bool
    {
        return $user->isInvest();
    }
}
