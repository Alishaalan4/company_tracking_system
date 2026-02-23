<h2>Welcome {{ $user->name }}</h2>

<p>Your account has been created.</p>

<p><strong>Email:</strong> {{ $user->email }}</p>
<p><strong>Temporary Password:</strong> {{ $tempPassword }}</p>
<p><strong>Temporary PIN:</strong> {{ $tempPin }}</p>

<p>You must change your password and PIN after first login.</p>

<p>Regards,<br>Smart Attendance System</p>