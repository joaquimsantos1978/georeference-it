<x-mail::message>
# New comment on "{{ $group->verbatim_locality ?? $group->municipality ?? $group->county ?? 'a locality' }}"

**{{ $comment->user->name }}** commented:

> {{ $comment->body }}

<x-mail::button :url="route('georef.group', $group->id)">
View Discussion
</x-mail::button>

You're receiving this because you contributed to this locality group.
To stop these notifications, update your [email preferences]({{ route('profile.edit') }}).

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
