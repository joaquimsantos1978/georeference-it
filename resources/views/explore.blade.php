<x-layouts.app title="Explore" description="Browse georeferenced and ungeoreferenced GBIF occurrences. Filter by country, status, or dataset and explore the progress of crowdsourced georeferencing.">

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Explore') }}</h1>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('explore') }}" class="flex flex-wrap gap-2 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Search locality') }}</label>
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="e.g. Redinha, Serra da Estrela..."
                    class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Country') }}</label>
                <select name="country" class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg pl-3 pr-8 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
                    <option value="">{{ __('All countries') }}</option>
                    @foreach($countries as $code)
                        <option value="{{ $code }}" {{ request('country') === $code ? 'selected' : '' }}>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Status') }}</label>
                <select name="status" class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg pl-3 pr-8 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
                    <option value="">{{ __('All') }}</option>
                    <option value="ungeoreferenced" {{ request('status') === 'ungeoreferenced' ? 'selected' : '' }}>{{ __('Needs georeferencing') }}</option>
                    <option value="has_suggestion" {{ request('status') === 'has_suggestion' ? 'selected' : '' }}>{{ __('Has suggestions') }}</option>
                    <option value="validated" {{ request('status') === 'validated' ? 'selected' : '' }}>{{ __('Validated') }}</option>
                    <option value="georeferenced" {{ request('status') === 'georeferenced' ? 'selected' : '' }}>{{ __('Georeferenced (any)') }}</option>
                    <option value="inconsistent" {{ request('status') === 'inconsistent' ? 'selected' : '' }}>{{ __('Inconsistent') }}</option>
                </select>
            </div>
            <div class="relative min-w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Collection') }}</label>
                <input type="text" id="dataset-search"
                    placeholder="{{ __('Search collection...') }}"
                    value="{{ request('dataset_key') ? ($currentDataset->title ?? request('dataset_key')) : '' }}"
                    autocomplete="off"
                    class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
                <input type="hidden" name="dataset_key" id="dataset-key-input" value="{{ request('dataset_key') }}">
                <div id="dataset-suggestions" class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="text-sm bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">{{ __('Search') }}</button>
                @if(request()->hasAny(['q','country','status','dataset_key']))
                    <a href="{{ route('explore') }}" class="text-sm border border-gray-200 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800">{{ __('Clear') }}</a>
                @endif
            </div>
        </form>

        <p class="text-xs text-gray-400">{{ number_format($groups->count()) }}{{ $groups->hasMorePages() ? '+' : '' }} {{ __('locality groups') }}</p>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Locality') }}</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-10">CC</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-20">{{ __('Occurrences') }}</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-24">{{ __('Suggestions') }}</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-20">{{ __('Validated') }}</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-32">{{ __('Status') }}</th>
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
                        <td class="px-4 py-2.5">
                            @if($group->consistency_status === 'inconsistent')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border text-red-600 bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800">{{ __('Inconsistent') }}</span>
                            @elseif($group->validated_count > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border text-green-600 bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800">{{ __('Validated') }}</span>
                            @elseif($group->pending_count > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800">{{ __('Has suggestions') }}</span>
                            @elseif($group->ungeoreferenced_count > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border text-gray-500 bg-gray-50 border-gray-200 dark:bg-gray-700 dark:border-gray-600">{{ __('Needs georeferencing') }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border text-blue-600 bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">{{ __('Georeferenced by GBIF') }}</span>
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
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400">{{ __('No locality groups found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{ $groups->links() }}
    </div>

<script>
(function () {
    const input = document.getElementById('dataset-search');
    const hidden = document.getElementById('dataset-key-input');
    const box    = document.getElementById('dataset-suggestions');
    let timer;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        hidden.value = '';
        if (q.length < 2) { box.classList.add('hidden'); return; }
        timer = setTimeout(() => fetchDatasets(q), 250);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { box.classList.add('hidden'); }
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !box.contains(e.target)) box.classList.add('hidden');
    });

    function fetchDatasets(q) {
        fetch('/api/v1/datasets?q=' + encodeURIComponent(q) + '&per_page=8')
            .then(r => r.json())
            .then(data => {
                if (!data.data.length) { box.innerHTML = '<p class="px-3 py-2 text-xs text-gray-400">No results</p>'; box.classList.remove('hidden'); return; }
                box.innerHTML = data.data.map(ds => {
                    const label = ds.title || ((ds.institution_code || '') + (ds.collection_code ? ' / ' + ds.collection_code : ''));
                    return `<div class="px-3 py-2 text-sm cursor-pointer hover:bg-green-50 dark:hover:bg-green-900/30 border-b border-gray-100 dark:border-gray-700 last:border-0"
                                 data-key="${ds.dataset_key}" data-label="${label.replace(/"/g, '&quot;')}">
                                <div class="font-medium text-gray-800 dark:text-white truncate">${label}</div>
                                <div class="text-xs text-gray-400">${ds.total.toLocaleString()} occurrences</div>
                            </div>`;
                }).join('');
                box.classList.remove('hidden');
                box.querySelectorAll('[data-key]').forEach(el => {
                    el.addEventListener('click', function () {
                        hidden.value = this.dataset.key;
                        input.value  = this.dataset.label;
                        box.classList.add('hidden');
                        input.closest('form').submit();
                    });
                });
            });
    }
})();
</script>
</x-layouts.app>
