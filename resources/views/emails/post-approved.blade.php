<x-mail::message>
# Post Approved!

Great news! **{{ $approverName }}** has approved your post.

**Post:** {{ $postTitle }}

<x-mail::button :url="$postUrl">
View Post
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
