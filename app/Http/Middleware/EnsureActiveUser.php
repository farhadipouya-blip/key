<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'name' => 'حساب شما غیرفعال شده است. با پشتیبانی تماس بگیرید.',
            ]);
        }

        return $next($request);
    }
}
