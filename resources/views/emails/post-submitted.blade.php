<x-mail::message>
# New Post Needs Approval

**{{ $creatorName }}** has submitted a post for your approval.

**Post:** {{ $postTitle }}<br>
**Brand:** {{ $brandName }}

<x-mail::button :url="$reviewUrl">
Review Post
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
