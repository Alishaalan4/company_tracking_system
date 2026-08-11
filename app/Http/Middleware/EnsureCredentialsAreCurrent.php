<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Users created by an admin receive a temporary password and PIN by email and
 * are flagged must_change_password / must_change_pin. Those flags existed but
 * nothing enforced them, so the temporary credentials stayed valid forever.
 *
 * Applied after auth:sanctum. The endpoints needed to actually resolve the
 * situation (change password/PIN, read own profile, sign out) stay reachable.
 */
class EnsureCredentialsAreCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->must_change_password || $user->must_change_pin) {
            return response()->json([
                'message' => $user->must_change_password
                    ? 'You must change your password before continuing.'
                    : 'You must change your attendance PIN before continuing.',
                'code' => 'credentials_change_required',
                'must_change_password' => (bool) $user->must_change_password,
                'must_change_pin' => (bool) $user->must_change_pin,
            ], 403);
        }

        return $next($request);
    }
}
