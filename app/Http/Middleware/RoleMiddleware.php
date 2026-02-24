<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */



    // - Purpose: Prevent duplicate or invalid attendance actions.
    // - Admins/Managers: Not considered here — this middleware is purely attendance-focused.
    // - Effect: Once a user has both check-in and check-out recorded for the day, they cannot hit attendance-related endpoints again
    public function handle(Request $request, Closure $next,...$roles): Response
    {
        $user = $request->user();
        if (!$user) 
        {
            return response()->json(['msg' => 'Unauthenticated'], 401);
        }

        $userRole = strtolower((string) optional($user->role)->name);
        $allowedRoles = array_map(fn ($role) => strtolower((string) $role), $roles);

        if ($userRole === '' || !in_array($userRole, $allowedRoles, true)) {
            return response()->json(['msg' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
