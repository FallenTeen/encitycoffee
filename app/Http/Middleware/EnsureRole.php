<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (! $user) {
            \Log::warning('EnsureRole: unauthenticated access attempt to ' . $request->path());
            abort(403);
        }
        if (empty($roles)) {
            return $next($request);
        }
        $allowedRoles = [];
        foreach ($roles as $role) {
            $splitRoles = array_map('trim', explode(',', $role));
            $allowedRoles = array_merge($allowedRoles, $splitRoles);
        }

        $allowedRoles = array_unique($allowedRoles);
        $allowedRolesLower = array_map('strtolower', $allowedRoles);
        $userRoleLower = strtolower((string) $user->role);

        return $next($request);
    }
}
