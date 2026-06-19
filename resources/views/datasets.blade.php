<x-layouts.app>
<x-slot name="title">Datasets — georeference.it</x-slot>

<div class="space-y-4 pb-16">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Datasets') }}</h1>
    </div>

    <p class="text-sm text-gray-500">
        {{ number_format($datasets->total()) }} datasets imported from
        <a href="https://www.gbif.org" target="_blank" class="text-green-600 hover:underline">GBIF</a>.
        Browse, filter by title or publisher, query via API, or download as CSV.
    </p>

    <form method="GET" action="{{ route('datasets') }}" class="flex flex-wrap gap-2 items-end">
        <input type="hidden" name="country" value="{{ $country }}">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="q" value="{{ $q }}"
                   placeholder="{{ __('Search institution, collection, dataset title or publisher...') }}"
                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-500">
        </div>
        <button type="submit" class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">{{ __('Search') }}</button>
        @if($q || $country)
        <a href="{{ route('datasets') }}" class="text-sm text-gray-500 hover:text-gray-700 px-2 py-2">{{ __('Clear') }}</a>
        @endif
    </form>

    {{-- Actions bar --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
            Showing <strong>{{ $datasets->firstItem() }}–{{ $datasets->lastItem() }}</strong>
            of <strong>{{ number_format($datasets->total()) }}</strong> datasets
            @if($q) matching "<em>{{ $q }}</em>"@endif
        </p>
        <div class="flex items-center gap-2">
            <a href="{{ route('api.datasets') }}" target="_blank"
               class="text-xs border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-green-500 hover:text-green-600 px-3 py-1.5 rounded-lg transition">
                API →
            </a>
            <a href="{{ request()->fullUrlWithQuery(['csv' => 1]) }}"
               class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition">
                ↓ CSV
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Institution / Collection</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-24">Total</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-28">Georeferenced</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-24">Validated</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-24">Missing</th>
                        <th class="px-4 py-3 w-20 text-xs font-medium text-gray-500 uppercase tracking-wide">%</th>
                        <th class="px-4 py-3 w-32"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($datasets as $ds)
                    @php
                        $pct = $ds->total > 0 ? round($ds->georeferenced / $ds->total * 100) : 0;
                        $barColor = $pct >= 75 ? '#22c55e' : ($pct >= 40 ? '#fbbf24' : '#f87171');
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3">
                            @if($ds->dataset_title)
                                <div class="font-medium text-gray-900 dark:text-white leading-snug text-sm">{{ $ds->dataset_title }}</div>
                                @if($ds->publisher_name)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $ds->publisher_name }}</div>
                                @endif
                                <div class="text-xs text-gray-300 dark:text-gray-600 font-mono mt-0.5">
                                    {{ $ds->institution_code ?: '' }}{{ $ds->institution_code && $ds->collection_code ? ' / ' : '' }}{{ $ds->collection_code ?: '' }}
                                </div>
                            @else
                                <div class="font-medium text-gray-900 dark:text-white leading-snug">
                                    {{ $ds->institution_code ?: '—' }}
                                    @if($ds->collection_code)
                                        <span class="text-gray-400 font-normal">/ {{ $ds->collection_code }}</span>
                                    @endif
                                </div>
                            @endif
                            <div class="text-xs text-gray-400 font-mono mt-0.5">
                                <a href="https://www.gbif.org/dataset/{{ $ds->dataset_key }}" target="_blank"
                                   class="hover:text-green-600">{{ $ds->dataset_key }}</a>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300 tabular-nums">{{ number_format($ds->total) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300 tabular-nums">{{ number_format($ds->georeferenced) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-green-700 dark:text-green-400 tabular-nums">{{ number_format($ds->validated) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-red-500 tabular-nums">{{ number_format($ds->ungeoreferenced) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-8 text-right tabular-nums">{{ $pct }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('explore') }}?dataset_key={{ $ds->dataset_key }}"
                                   class="text-xs text-green-600 hover:underline whitespace-nowrap">Browse</a>
                                <a href="{{ url('/api/v1/occurrences') }}?dataset_key={{ $ds->dataset_key }}&status=validated"
                                   target="_blank"
                                   class="text-xs text-gray-400 hover:text-gray-600 whitespace-nowrap">API</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($datasets->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $datasets->links() }}
        </div>
        @endif
    </div>
</div>
</x-layouts.app>
