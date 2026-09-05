<x-mail::message>
# You've been invited!

Hello {{ $user->name }},

You have been invited to join the shop team as a staff member.

**Your login details:**
- **Username:** {{ $user->username }}
- **Email:** {{ $user->email }}
- **Temporary Password:** `{{ $password }}`

Please login and change your password immediately.

<x-mail::button :url="url('/login')">
Login Now
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
