<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user=$request->user();
        // - If the user is an admin, the request continues without restrictions.
        // - Admins have full access.
        if ($user->isAdmin())
        {
            return $next($request);
        }
        // - If the user is a manager, their department ID is injected into the request as department_scope_id.
        // - This means downstream controllers or queries can automatically filter data by that department.
        // - Managers only see data scoped to their department.
        // - Managers → restricted to their own department; the middleware adds a department_scope_id to the request for convenience.
        if ($user->isManager())
        {
            $departmentId = $user->department_id;
            $request->merge(['department_scope_id' => $departmentId]);
            return $next($request);
        }
        return response()->json(['message'=> 'Forbidden'], 403);
    }
}
