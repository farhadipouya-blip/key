<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** ثبت آخرین حضور کاربر برای محاسبه کاربران آنلاین */
class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->last_seen_at || $user->last_seen_at->lt(now()->subSeconds(60)))) {
            \App\Models\User::withoutTimestamps(fn () => $user->forceFill(['last_seen_at' => now()])->saveQuietly());
        }

        return $next($request);
    }
}
