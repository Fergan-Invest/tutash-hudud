<?php

namespace App\Policies;

use App\Models\RegistryRequest;
use App\Models\User;

class RegistryRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['viloyat_hokimi', 'invest', 'tuman'], true);
    }

    public function view(User $user, RegistryRequest $request): bool
    {
        return $user->isInvest()
            || $user->isViloyatHokimi()
            || ($user->isTuman() && (int) $user->district_id === (int) $request->district_id);
    }

    public function create(User $user): bool
    {
        return $user->isInvest() || $user->isTuman();
    }

    public function update(User $user, RegistryRequest $request): bool
    {
        if ($user->isViloyatHokimi() || in_array($request->status, ['approved'], true)) {
            return false;
        }

        return $user->isInvest();
    }

    public function delete(User $user, RegistryRequest $request): bool
    {
        return $user->isInvest() && $request->status !== 'approved';
    }
}
