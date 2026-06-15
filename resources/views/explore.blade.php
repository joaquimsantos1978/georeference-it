<x-layouts.app>
    <x-slot name="title">{{ __('Explore') }} — georeference.it</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Explore') }}</h1>
        </div>

        {{-- Filters --}}
        @if(request('dataset_key'))
        <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg px-4 py-2 text-sm text-green-800 dark:text-green-300">
            <span>Filtered to dataset <code class="font-mono text-xs">{{ request('dataset_key') }}</code></span>
            <a href="{{ route('explore') }}" class="ml-auto text-xs text-green-600 hover:underline">Clear filter</a>
        </div>
        @endif

        <form method="GET" action="{{ route('explore') }}" class="flex flex-wrap gap-2 items-end">
            @if(request('dataset_key'))
            <input type="hidden" name="dataset_key" value="{{ request('dataset_key') }}">
            @endif
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Search locality') }}</label>
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="e.g. Redinha, Serra da Estrela..."
                    class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Country') }}</label>
                <select name="country" class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
                    <option value="">{{ __('All countries') }}</option>
                    @foreach($countries as $code)
                        <option value="{{ $code }}" {{ request('country') === $code ? 'selected' : '' }}>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Status') }}</label>
                <select name="status" class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
                    <option value="">{{ __('All') }}</option>
                    <option value="ungeoreferenced" {{ request('status') === 'ungeoreferenced' ? 'selected' : '' }}>{{ __('Needs georeferencing') }}</option>
                    <option value="has_suggestion" {{ request('status') === 'has_suggestion' ? 'selected' : '' }}>{{ __('Has suggestions') }}</option>
                    <option value="validated" {{ request('status') === 'validated' ? 'selected' : '' }}>{{ __('Validated') }}</option>
                    <option value="georeferenced" {{ request('status') === 'georeferenced' ? 'selected' : '' }}>{{ __('Georeferenced (any)') }}</option>
                    <option value="inconsistent" {{ request('status') === 'inconsistent' ? 'selected' : '' }}>{{ __('Inconsistent') }}</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="text-sm bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">{{ __('Search') }}</button>
                @if(request()->hasAny(['q','country','status','dataset_key']))
                    <a href="{{ route('explore') }}" class="text-sm border border-gray-200 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800">{{ __('Clear') }}</a>
                @endif
            </div>
        </form>

        @if($groups === null)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-6 py-16 text-center text-gray-400">
            <p class="text-base mb-1">{{ __('Search or select a country to browse localities.') }}</p>
            <p class="text-xs">{{ __('The database contains over 43 million locality groups from around the world.') }}</p>
        </div>
        @else

        <p class="text-xs text-gray-400">{{ number_format($groups->total()) }} {{ __('locality groups') }}</p>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Locality') }}</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-10">CC</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-20">{{ __('Occurrences') }}</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-24">{{ __('Suggestions') }}</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-20">{{ __('Validated') }}</th>
                        <th class="px-4 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($groups as $group)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-2.5">
                            <div class="font-medium text-gray-900 dark:text-white text-sm leading-snug">
                                {{ $group->verbatim_locality ?: ($group->municipality ?: ($group->county ?: $group->state_province)) }}
                            </div>
                            @if($group->state_province || $group->county || $group->municipality)
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ implode(', ', array_filter([$group->municipality, $group->county, $group->state_province])) }}
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-xs text-gray-500 font-mono">{{ $group->country_code }}</td>
                        <td class="px-4 py-2.5 text-right text-gray-600 dark:text-gray-300">{{ number_format($group->occurrence_count) }}</td>
                        <td class="px-4 py-2.5 text-right">
                            @if($group->pending_count > 0)
                                <span class="text-amber-600 font-medium">{{ $group->pending_count }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            @if($group->validated_count > 0)
                                <span class="text-green-600 font-medium">{{ $group->validated_count }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            @php
                            $focusVal = $group->verbatim_locality ?: ($group->municipality ?: ($group->county ?: $group->state_province));
                            $focusParams = http_build_query(array_filter(['focus' => $focusVal, 'country' => $group->country_code, 'group' => $group->id]));
                        @endphp
                        <a href="{{ route('georef.index') }}?{{ $focusParams }}"
                               class="text-xs text-green-600 hover:underline whitespace-nowrap">{{ __('Georeference →') }}</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">{{ __('No locality groups found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $groups->links() }}
        @endif
    </div>
</x-layouts.app>
