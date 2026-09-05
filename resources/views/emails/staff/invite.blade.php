<x-mail::message>
# You've been invited!

Hello,

You have been invited to join the shop team as a staff member.

Please click the button below to set up your account and choose your username and password.

<x-mail::button :url="url('/staff/setup?token='.$token.'&email='.$email)">
Set Up Account
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
