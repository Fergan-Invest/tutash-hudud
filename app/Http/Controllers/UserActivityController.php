<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserActivityController extends Controller
{
    public function online()
    {
        return User::query()
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->orderByDesc('last_seen_at')
            ->get(['id', 'name', 'email', 'role', 'district_id', 'last_seen_at', 'last_ip']);
    }
}
