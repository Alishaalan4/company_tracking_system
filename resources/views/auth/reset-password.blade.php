<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>

<h2>Reset Password</h2>

<form method="POST" action="{{ url('/api/auth/reset-password') }}">

    <input type="hidden" name="token" value="{{ $token }}">

    <div>
        <label>Email</label>
        <input type="email" name="email" value="{{ $email }}" required readonly>
    </div>

    <div>
        <label>New Password</label>
        <input type="password" name="password" required>
    </div>

    <div>
        <label>Confirm Password</label>
        <input type="password" name="password_confirmation" required>
    </div>

    <button type="submit">Reset Password</button>
</form>

</body>
</html>
