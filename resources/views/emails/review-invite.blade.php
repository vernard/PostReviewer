<x-mail::message>
# Review Requested

**{{ $requesterName }}** has requested your review on a post for **{{ $brandName }}**.

**Post:** {{ $postTitle }}

Please click the button below to review and approve or request changes.

<x-mail::button :url="$reviewUrl">
Review Post
</x-mail::button>

This link will expire on {{ $expiresAt }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
