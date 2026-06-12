<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(Model $model, string $event, array $oldValues = [], array $newValues = [], ?Request $request = null): void
    {
        $request ??= request();

        $model->audits()->create([
            'user_id' => $request->user()?->id,
            'event' => $event,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 2000),
        ]);
    }
}
