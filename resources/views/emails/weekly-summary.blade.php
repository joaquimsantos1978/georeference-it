<x-mail::message>
# {{ __('Your week on georeference.it') }}

{{ __('Hi :name, here\'s what happened this week:', ['name' => $user->name]) }}

**{{ __('Your activity') }}**

- **{{ $suggestions }}** {{ __('Georefs') }}
- **{{ $validations }}** {{ __('Reviews') }}
- **{{ $specimens }}** {{ __('Specimens') }}
- **{{ $validated }}** {{ __('Validated') }}

**{{ __('Platform activity') }}**

- **{{ $totalGeoreferenced }}** {{ __('new specimens georeferenced this week') }}
- **{{ $totalContributors }}** {{ __('active contributors this week') }}

<x-mail::button :url="route('georef.index')">
{{ __('Continue contributing') }}
</x-mail::button>

{{ __('To stop receiving weekly summaries, update your :link.', ['link' => '['.__('email preferences').']('.route('profile.edit').')']) }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
