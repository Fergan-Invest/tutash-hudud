<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\RegistryRequest;
use App\Models\User;

class AuditPolicy
{
    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->isInvest() || $user->isViloyatHokimi()) {
            return true;
        }

        if ($auditLog->auditable instanceof RegistryRequest) {
            return (int) $user->district_id === (int) $auditLog->auditable->district_id;
        }

        return false;
    }
}
