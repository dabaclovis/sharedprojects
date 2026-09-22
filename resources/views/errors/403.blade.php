<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Account access</title></head>
<body style="font-family: sans-serif; max-width: 640px; margin: 4rem auto; padding: 1rem;">
    @if (auth()->user()?->status === 'suspended')
        <h1>Your account is suspended</h1>
        <p style="white-space: pre-wrap;">{{ auth()->user()->suspension_reason ?: 'Please contact support for the reason for your suspension.' }}</p>
        <p>Please contact support at <a href="mailto:info@myapp.com">info@myapp.com</a>.</p>
        <form method="POST" action="{{ route('auth.logout') }}">@csrf<button type="submit">Sign out</button></form>
    @else
        <h1>Access denied</h1><p>You do not have permission to access this page.</p>
    @endif
</body>
</html>
