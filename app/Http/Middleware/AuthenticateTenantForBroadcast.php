<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateTenantForBroadcast
{
    public function handle(Request $request, Closure $next)
    {
        // Try to authenticate tenant from tenant guard
        if (Auth::guard('tenant')->check()) {
            // Authenticate in the default guard so Broadcast::auth() can find the user
            Auth::login(Auth::guard('tenant')->user(), true);
        }

        return $next($request);
    }
}
