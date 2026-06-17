<x-mail::message>
# Your week on georeference.it

Hi {{ $user->name }}, here's what happened this week:

**Your activity**

| | Count |
|---|---|
| Georeferencing suggestions | {{ $suggestions }} |
| Validations | {{ $validations }} |
| Comments | {{ $comments }} |

**Platform activity**

- **{{ $totalGeoreferenced }}** new specimens georeferenced
- **{{ $totalContributors }}** active contributors

<x-mail::button :url="route('georef.index')">
Continue contributing
</x-mail::button>

To stop receiving weekly summaries, update your [email preferences]({{ route('profile.edit') }}).

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
