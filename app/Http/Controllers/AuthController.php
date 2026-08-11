<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\AuditLogService;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'          => 'required|string',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:6',
            'pin'           => 'required|min:4|max:6',
            'role_id'       => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'pin'           => Hash::make($request->pin),
            'role_id'       => $request->role_id,
            'department_id' => $request->department_id,
        ]);

        return response()->json($user, 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'nullable|string|required_without:pin',
            'pin' => 'nullable|string|min:4|max:6|required_without:password',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message'=> 'Invalid Credentials'], 401);
        }

        $isPasswordLogin = $request->filled('password');
        $isPinLogin = $request->filled('pin');

        $isValidCredentials = ($isPasswordLogin && Hash::check($request->password, $user->password))
            || ($isPinLogin && Hash::check($request->pin, $user->pin));

        if (!$isValidCredentials) {
            AuditLogService::record(
                'login.failed',
                "Failed login attempt for {$request->email}",
                $user
            );

            return response()->json(['message'=> 'Invalid Credentials'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'This account has been deactivated'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        app(AuditLogService::class)->log(
            $user->id,
            'login',
            $isPinLogin ? 'Signed in with PIN' : 'Signed in with password',
            ['model' => 'User', 'model_id' => $user->id]
        );

        // The SPA gates its whole admin UI on user.role.name, so the relations
        // must be eager-loaded here.
        return response()->json([
            'user' => $user->load('role', 'department'),
            'token' => $token
        ]);
    }

    public function pinLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'pin' => 'required|string|min:4|max:6',
        ]);

        return $this->login($request);
    }

    public function logout(Request $request)
    {
        AuditLogService::record('logout', 'Signed out', $request->user());

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        AuditLogService::record('password.changed', 'Password changed', $user);

        // Return the user so the SPA can clear its "must change" gate.
        return response()->json([
            'message' => 'Password changed successfully',
            'user' => $user->fresh()->load('role', 'department'),
        ]);
    }

    public function changePin(Request $request)
    {
        $request->validate([
            'current_pin' => 'required|string|min:4|max:6',
            'pin' => 'required|string|min:4|max:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_pin, $user->pin)) {
            return response()->json(['message' => 'Current PIN is incorrect'], 422);
        }

        $user->update([
            'pin' => Hash::make($request->pin),
            'must_change_pin' => false,
        ]);

        AuditLogService::record('pin.changed', 'Attendance PIN changed', $user);

        return response()->json([
            'message' => 'PIN changed successfully',
            'user' => $user->fresh()->load('role', 'department'),
        ]);
    }
}
