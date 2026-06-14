<x-layouts.app>
<x-slot name="title">Datasets — georeference.it</x-slot>

{{-- Hero --}}
<div class="-mx-4 sm:-mx-6 lg:-mx-8 -mt-6 mb-8 px-6 py-10" style="background:linear-gradient(135deg,#15803d,#14532d);color:#fff;">
    <div class="max-w-5xl mx-auto">
        <h1 style="font-size:1.875rem;font-weight:700;margin-bottom:0.5rem;">Datasets</h1>
        <p style="color:#bbf7d0;font-size:0.875rem;max-width:36rem;">
            {{ number_format($datasets->total()) }} datasets imported from
            <a href="https://www.gbif.org" target="_blank" style="text-decoration:underline;color:#fff">GBIF</a>.
            Browse, filter by title or publisher, query via API, or download as CSV.
        </p>
        <form method="GET" action="{{ route('datasets') }}" class="mt-5 flex flex-wrap gap-2">
            <input type="text" name="q" value="{{ $q }}"
                   placeholder="Search institution, collection, dataset title or publisher…"
                   style="flex:1;min-width:200px;font-size:0.875rem;padding:0.5rem 1rem;border-radius:0.5rem;color:#111;background:#fff;border:none;outline:none;">
            <input type="hidden" name="country" value="{{ $country }}">
            <button type="submit"
                    style="font-size:0.875rem;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.35);color:#fff;padding:0.5rem 1rem;border-radius:0.5rem;cursor:pointer;">
                Search
            </button>
            @if($q || $country)
            <a href="{{ route('datasets') }}" style="font-size:0.875rem;color:rgba(255,255,255,0.7);padding:0.5rem 1rem;border-radius:0.5rem;text-decoration:none;">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="max-w-5xl mx-auto pb-16">

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
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
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
