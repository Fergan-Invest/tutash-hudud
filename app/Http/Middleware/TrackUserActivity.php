<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            $request->user()->forceFill([
                'last_seen_at' => now(),
                'last_ip' => $request->ip(),
                'last_user_agent' => substr((string) $request->userAgent(), 0, 2000),
            ])->save();
        }

        return $next($request);
    }
}
