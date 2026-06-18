<x-layouts.app>
    <x-slot name="title">{{ __('Dashboard') }} — georeference.it</x-slot>

    <div class="space-y-8">
        {{-- Welcome + level --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Welcome back,') }} {{ auth()->user()->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Level') }}: <span class="font-medium text-green-600">{{ auth()->user()->userLevel?->name ?? __('Beginner') }}</span>
                    · {{ auth()->user()->total_validated }} {{ __('validated contributions') }}
                </p>
            </div>
            <a href="{{ route('georef.index') }}" class="bg-green-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700">
                {{ __('Start georeferencing') }}
            </a>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Suggestions') }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ auth()->user()->suggestions()->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Validated') }}</p>
                <p class="text-3xl font-bold text-green-600 mt-1">{{ auth()->user()->total_validated }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Reviews') }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ auth()->user()->validations()->whereHas('suggestion', fn($q) => $q->where('user_id', '!=', auth()->id())->orWhereNull('user_id'))->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Vote weight') }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ auth()->user()->getVoteWeight() }}</p>
            </div>
        </div>

        {{-- Level progress --}}
        @if(auth()->user()->userLevel)
        @php
            $currentLevel = auth()->user()->userLevel;
            $nextLevel = \App\Models\UserLevel::where('min_validated', '>', $currentLevel->min_validated)->orderBy('min_validated')->first();
            $progress = $nextLevel && $nextLevel->min_validated > $currentLevel->min_validated
                ? min(100, (auth()->user()->total_validated - $currentLevel->min_validated) / ($nextLevel->min_validated - $currentLevel->min_validated) * 100)
                : 100;
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $currentLevel->name }}</span>
                @if($nextLevel)
                    <span class="text-sm text-gray-500">{{ __('Next') }}: {{ $nextLevel->name }} ({{ $nextLevel->min_validated }} {{ __('validated') }})</span>
                @else
                    <span class="text-sm text-green-600">{{ __('Maximum level reached!') }}</span>
                @endif
            </div>
            @if($nextLevel)
            @php $remaining = $nextLevel->min_validated - auth()->user()->total_validated; @endphp
            <div class="bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all" style="width:{{ $progress }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ auth()->user()->total_validated }} / {{ $nextLevel->min_validated }} validated · <span class="text-green-600 font-medium">{{ $remaining }} more</span> to reach <span class="font-medium text-gray-600 dark:text-gray-300">{{ $nextLevel->name }}</span></p>
            @endif
        </div>
        @endif

        {{-- My suggestions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('My suggestions') }}</h2>
                <span class="text-xs text-gray-400">{{ $suggestions->total() }} {{ __('total') }}</span>
            </div>
            @if($suggestions->isEmpty())
                <div class="p-8 text-sm text-gray-400 text-center">
                    {{ __('No suggestions yet.') }}
                    <a href="{{ route('georef.index') }}" class="text-green-600 hover:underline ml-1">{{ __('Start now!') }}</a>
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Locality') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-32">{{ __('Coordinates') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-20">{{ __('Status') }}</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-20">{{ __('Points') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-28">{{ __('Submitted') }}</th>
                            <th class="px-4 py-3 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($suggestions as $s)
                        @php
                            $g = $s->localityGroup;
                            $locality = $g ? ($g->verbatim_locality ?: ($g->municipality ?: ($g->county ?: $g->state_province))) : '—';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750" id="sug-row-{{ $s->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white text-sm leading-snug">{{ $locality }}</div>
                                @if($g && $g->country_code)
                                    <div class="text-xs text-gray-400 font-mono">{{ $g->country_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500">
                                @if($s->decimal_latitude)
                                    {{ number_format((float)$s->decimal_latitude, 4) }},
                                    {{ number_format((float)$s->decimal_longitude, 4) }}
                                    <div class="text-gray-400">±{{ number_format($s->coordinate_uncertainty_m) }}m</div>
                                @else —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    @if($s->status === 'validated') bg-green-100 text-green-700
                                    @elseif($s->status === 'rejected') bg-red-100 text-red-700
                                    @else bg-amber-100 text-amber-700 @endif">
                                    {{ $s->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ $s->total_points }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ $s->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($g)
                                    <a href="{{ route('georef.index') }}?group={{ $g->id }}"
                                       class="text-xs text-green-600 hover:underline whitespace-nowrap">{{ __('View') }}</a>
                                    @endif
                                    @if($s->status === 'pending')
                                    <button onclick="deleteSuggestion({{ $s->id }})"
                                        class="text-xs text-red-500 hover:text-red-700 hover:underline whitespace-nowrap">{{ __('Delete') }}</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($suggestions->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $suggestions->links() }}
            </div>
            @endif
            @endif
        </div>

        {{-- My validations --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('My reviews') }}</h2>
                <span class="text-xs text-gray-400">{{ $validations->total() }} {{ __('total') }}</span>
            </div>
            @if($validations->isEmpty())
                <div class="p-8 text-sm text-gray-400 text-center">{{ __('No reviews yet.') }}</div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Locality') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-32">{{ __('Coordinates') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-20">{{ __('Vote') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-28">{{ __('When') }}</th>
                            <th class="px-4 py-3 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($validations as $v)
                        @php
                            $sg = $v->suggestion;
                            $grp = $sg?->localityGroup;
                            $loc = $grp ? ($grp->verbatim_locality ?: ($grp->municipality ?: ($grp->county ?: $grp->state_province))) : '—';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750" id="val-row-{{ $v->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white text-sm leading-snug">{{ $loc }}</div>
                                @if($grp && $grp->country_code)
                                    <div class="text-xs text-gray-400 font-mono">{{ $grp->country_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500">
                                @if($sg && $sg->decimal_latitude)
                                    {{ number_format((float)$sg->decimal_latitude, 4) }},
                                    {{ number_format((float)$sg->decimal_longitude, 4) }}
                                @else —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    @if($v->vote === 'agree') bg-green-100 text-green-700
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ $v->vote }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ $v->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($grp)
                                    <a href="{{ route('georef.index') }}?group={{ $grp->id }}"
                                       class="text-xs text-green-600 hover:underline whitespace-nowrap">{{ __('View') }}</a>
                                    @endif
                                    <button onclick="revokeValidation({{ $v->id }})"
                                        class="text-xs text-red-500 hover:text-red-700 hover:underline whitespace-nowrap">{{ __('Revoke') }}</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($validations->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $validations->links() }}
            </div>
            @endif
            @endif
        </div>

        {{-- Preferences --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Preferences') }}</h2>
            <form action="{{ route('dashboard.preferences') }}" method="POST" class="flex flex-wrap gap-6 items-end">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Preferred task') }}</label>
                    <select name="preferred_task" class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="both" @selected(auth()->user()->preferred_task === 'both')>{{ __('Both (platform decides)') }}</option>
                        <option value="georef" @selected(auth()->user()->preferred_task === 'georef')>{{ __('Georeference only') }}</option>
                        <option value="validate" @selected(auth()->user()->preferred_task === 'validate')>{{ __('Validate only') }}</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="email_notifications" id="email_notifications" value="1" @checked(auth()->user()->email_notifications) class="rounded border-gray-300">
                    <label for="email_notifications" class="text-sm text-gray-600 dark:text-gray-400">{{ __('Email notifications') }}</label>
                </div>
                <button type="submit" class="text-sm bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">{{ __('Save') }}</button>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    const CSRF_DASH = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const APP_URL_DASH = document.querySelector('meta[name="app-url"]').getAttribute('content');

    function deleteSuggestion(id) {
        if (!confirm('{{ __("Delete this georeferencing suggestion? This cannot be undone.") }}')) return;
        fetch(APP_URL_DASH + '/georef/suggestion/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_DASH, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            if (d.success) {
                var row = document.getElementById('sug-row-' + id);
                if (row) row.remove();
            }
        });
    }

    function revokeValidation(id) {
        if (!confirm('{{ __("Revoke this vote?") }}')) return;
        fetch(APP_URL_DASH + '/georef/validation/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_DASH, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            if (d.success) {
                var row = document.getElementById('val-row-' + id);
                if (row) row.remove();
            }
        });
    }
    </script>
    @endpush
</x-layouts.app>
