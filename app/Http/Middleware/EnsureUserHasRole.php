<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     * Usage: role:super_admin or role:super_admin,admin_destinasi
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $allowed = collect(explode(',', $roles))
            ->map(fn ($r) => trim($r))
            ->filter()
            ->values();

        $hasRole = $user->roles()->whereIn('name', $allowed)->exists();
        if (!$hasRole) {
            abort(403);
        }

        return $next($request);
    }
}

