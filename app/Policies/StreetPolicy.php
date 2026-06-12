<?php

namespace App\Policies;

use App\Models\Street;
use App\Models\User;

class StreetPolicy
{
    public function create(User $user): bool
    {
        return $user->isInvest() || $user->isTuman();
    }

    public function update(User $user, Street $street): bool
    {
        return $user->isInvest()
            || ($user->isTuman() && (int) $user->district_id === (int) $street->district_id);
    }
}
