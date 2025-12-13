<x-mail::message>
# You're Invited!

**{{ $inviterName }}** has invited you to join **{{ $agencyName }}** as a **{{ $role }}**.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

This invitation will expire on {{ $expiresAt }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
