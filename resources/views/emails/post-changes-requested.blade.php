<x-mail::message>
# Changes Requested

**{{ $reviewerName }}** has requested changes to your post.

**Post:** {{ $postTitle }}

@if($feedback)
**Feedback:**
{{ $feedback }}
@endif

<x-mail::button :url="$postUrl">
View Post
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
