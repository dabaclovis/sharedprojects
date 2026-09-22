<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSuspension
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->status === 'suspended' && ! $request->routeIs('auth.logout', 'admins.logout')) {
            abort(403, $request->user()->suspensionMessage());
        }

        return $next($request);
    }
}
