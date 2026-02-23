<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewUserCredentialsMail;
use Illuminate\Support\Str;
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
        'email' => 'required|email|unique:users',
        'role_id' => 'required|exists:roles,id',
        'department_id' => 'nullable|exists:departments,id'
    ]);

    $tempPassword = Str::random(8);
    $tempPin = rand(1000, 9999);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($tempPassword),
        'pin' => Hash::make($tempPin),
        'role_id' => $request->role_id,
        'department_id' => $request->department_id,
        'must_change_pin' => true,
        'must_change_password' => true,
    ]);

    Mail::to($user->email)
        ->queue(new NewUserCredentialsMail(
            $user,
            $tempPassword,
            $tempPin
        ));

    return response()->json([
        'message' => 'User created and credentials sent by email'
    ]);
}

    public function show(User $user)
    {
        return $user->load('role', 'department');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->all();

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->pin) {
            $data['pin'] = Hash::make($request->pin);
        }

        $user->update($data);

        return $user;
    }

    public function destroy(User $user)
    {
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

    Mail::to($user->email)
        ->queue(new NewUserCredentialsMail(
            $user,
            $tempPassword,
            $tempPin
        ));

    return response()->json([
        'message' => 'New credentials sent successfully'
    ]);
}
}
