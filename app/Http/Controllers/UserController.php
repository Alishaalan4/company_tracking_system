<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewUserCredentialsMail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class UserController extends Controller
{
    public function index()
    {
        return User::with('role', 'department')->paginate(20);
    }

   public function store(Request $request)
{
    $request->validate([
        'name' => 'required',
        // Ignore soft-deleted rows so a removed employee's address can be
        // reused; the trashed record is restored below instead of colliding
        // with the table's unique index on email.
        'email' => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')],
        'role_id' => 'required|exists:roles,id',
        'department_id' => 'nullable|exists:departments,id',
        'password' => 'nullable|string|min:6',
        'pin' => 'nullable|string|min:4|max:6',
    ]);

    // Honour credentials the admin typed; only generate when they left the
    // fields blank. Previously these inputs were silently discarded and the
    // account always got random credentials instead.
    $chosenPassword = $request->filled('password');
    $chosenPin = $request->filled('pin');

    $tempPassword = $chosenPassword ? $request->password : Str::random(8);
    $tempPin = $chosenPin ? $request->pin : (string) rand(1000, 9999);

    $attributes = [
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($tempPassword),
        'pin' => Hash::make($tempPin),
        'role_id' => $request->role_id,
        'department_id' => $request->department_id,
        'is_active' => true,
        // Only force a change for credentials the user has not chosen.
        'must_change_password' => !$chosenPassword,
        'must_change_pin' => !$chosenPin,
    ];

    $trashed = User::onlyTrashed()->where('email', $request->email)->first();

    if ($trashed) {
        $trashed->restore();
        $trashed->update($attributes);
        $user = $trashed;
        $action = 'user.restored';
    } else {
        $user = User::create($attributes);
        $action = 'user.created';
    }

    $delivered = $this->sendCredentials($user, $tempPassword, $tempPin);

    AuditLogService::record($action, "Created user {$user->email}", $user);

    return response()->json([
        'message' => $delivered
            ? 'User created and credentials sent by email'
            : 'User created, but the credentials email could not be sent. Use "Resend credentials" to try again.',
        'email_sent' => $delivered,
    ], 201);
}

    /**
     * Sent synchronously: the queue connection is `database` with no worker
     * running, so queued credential emails were never actually delivered.
     * A mail failure must not discard the user that was just created.
     */
    private function sendCredentials(User $user, string $password, $pin): bool
    {
        try {
            Mail::to($user->email)->send(new NewUserCredentialsMail($user, $password, $pin));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    public function show(User $user)
    {
        return $user->load('role', 'department');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes', 'required', 'email',
                Rule::unique('users')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'role_id' => 'sometimes|required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'password' => 'sometimes|required|string|min:6|confirmed',
            'pin' => 'sometimes|required|string|min:4|max:6|confirmed',
            'must_change_password' => 'sometimes|boolean',
            'must_change_pin' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            unset($data['password_confirmation']);
        }

        if ($request->filled('pin')) {
            $data['pin'] = Hash::make($request->pin);
            unset($data['pin_confirmation']);
        }

        $changed = array_keys(array_diff_key($data, array_flip(['password', 'pin'])));
        if (isset($data['password'])) { $changed[] = 'password'; }
        if (isset($data['pin'])) { $changed[] = 'pin'; }

        $user->update($data);

        AuditLogService::record(
            'user.updated',
            "Updated user {$user->email}",
            $user,
            ['fields' => array_values(array_unique($changed))]
        );

        return $user;
    }

    public function destroy(User $user)
    {
        AuditLogService::record('user.deleted', "Deleted user {$user->email}", $user);

        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }


    public function resendCredentials(User $user)
{
    $tempPassword = Str::random(8);
    $tempPin = rand(1000, 9999);

    $user->update([
        'password' => Hash::make($tempPassword),
        'pin' => Hash::make($tempPin),
        'must_change_password' => true,
        'must_change_pin' => true,
    ]);

    $delivered = $this->sendCredentials($user, $tempPassword, $tempPin);

    AuditLogService::record('user.credentials_resent', "Reissued credentials for {$user->email}", $user);

    return response()->json([
        'message' => $delivered
            ? 'New credentials sent successfully'
            : 'Credentials were reset, but the email could not be sent.',
        'email_sent' => $delivered,
    ], $delivered ? 200 : 502);
}
}
