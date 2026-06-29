<x-layouts.app title="Impact" description="Specimens georeferenced or improved through georeference.it — making biodiversity data more useful.">

    <div class="space-y-6 max-w-6xl mx-auto">

        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Impact</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ number_format($totalCount) }} {{ Str::plural('specimen', $totalCount) }} georeferenced or improved on this platform</p>
            </div>

            <form method="GET" action="{{ route('impact') }}" class="flex items-center gap-2 flex-wrap">
                <select name="status" onchange="this.form.submit()"
                    class="text-xs border border-gray-200 dark:border-gray-600 rounded-lg pl-2 pr-7 py-1.5 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 cursor-pointer">
                    <option value="">All statuses</option>
                    <option value="validated"      {{ $status === 'validated'      ? 'selected' : '' }}>Validated</option>
                    <option value="has_suggestion" {{ $status === 'has_suggestion' ? 'selected' : '' }}>Suggestion pending</option>
                    <option value="gbif_reviewed"  {{ $status === 'gbif_reviewed'  ? 'selected' : '' }}>GBIF reviewed</option>
                </select>
                <input type="text" name="country" value="{{ $country }}" placeholder="Country (e.g. PT)"
                    maxlength="2"
                    class="text-xs border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 w-28 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase placeholder:normal-case placeholder:text-gray-400">
                <button type="submit" class="text-xs bg-green-600 hover:bg-green-700 text-white rounded-lg px-3 py-1.5 transition-colors">Filter</button>
                @if($status || $country)
                    <a href="{{ route('impact') }}" class="text-xs text-gray-400 hover:text-gray-600 py-1.5">Clear</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

            @if($occurrences->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-gray-400">No specimens found.</div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Specimen</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Locality</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">GBIF coords</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($occurrences as $occ)
                        @php
                            $localityParts = array_filter([
                                $occ->verbatim_locality,
                                $occ->municipality,
                                $occ->county,
                                $occ->state_province,
                            ]);
                            $locality = trim(implode(', ', $localityParts));

                            $catalogRef = trim(implode(' ', array_filter([
                                $occ->institution_code,
                                $occ->collection_code,
                                $occ->catalog_number,
                            ])));

                            $pills = [
                                'has_suggestion' => ['label' => 'Pending',       'class' => 'text-amber-600 bg-amber-50 border-amber-200'],
                                'validated'      => ['label' => 'Validated',     'class' => 'text-green-600 bg-green-50 border-green-200'],
                                'gbif_reviewed'  => ['label' => 'GBIF reviewed', 'class' => 'text-blue-600 bg-blue-50 border-blue-200'],
                            ];
                            $pill = $pills[$occ->georef_status] ?? ['label' => $occ->georef_status, 'class' => 'text-gray-500 bg-gray-50 border-gray-200'];

                            $eventYear = $occ->event_date ? substr($occ->event_date, 0, 4) : null;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <td class="px-4 py-3 max-w-[200px]">
                                <div class="font-medium italic text-gray-900 dark:text-white text-xs leading-snug">{{ $occ->scientific_name }}</div>
                                <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-0.5">
                                    @if($occ->country_code)
                                        <span class="font-mono text-xs text-gray-400">{{ strtoupper($occ->country_code) }}</span>
                                    @endif
                                    @if($catalogRef)
                                        <span class="text-xs text-gray-400">·</span>
                                        <span class="text-xs text-gray-500 font-mono">{{ $catalogRef }}</span>
                                    @endif
                                    @if($eventYear)
                                        <span class="text-xs text-gray-400">·</span>
                                        <span class="text-xs text-gray-400">{{ $eventYear }}</span>
                                    @endif
                                </div>
                                @if($occ->recorded_by)
                                    <div class="text-xs text-gray-400 truncate max-w-[180px]" title="{{ $occ->recorded_by }}">{{ $occ->recorded_by }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs max-w-xs">
                                @if($locality)
                                    <div class="line-clamp-2" title="{{ $locality }}">{{ $locality }}</div>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 whitespace-nowrap">
                                @if($occ->gbif_decimal_latitude !== null)
                                    {{ number_format((float)$occ->gbif_decimal_latitude, 4) }},
                                    {{ number_format((float)$occ->gbif_decimal_longitude, 4) }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $pill['class'] }}">
                                    {{ $pill['label'] }}
                                </span>
                                @if($occ->updated_at)
                                    <div class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($occ->updated_at)->diffForHumans() }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 justify-end">
                                    @if($occ->locality_group_id)
                                        <a href="{{ route('georef.index') }}?group={{ $occ->locality_group_id }}"
                                           title="Open in georeference.it"
                                           class="text-gray-400 hover:text-green-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if($occ->gbif_occurrence_key)
                                        <a href="https://www.gbif.org/occurrence/{{ $occ->gbif_occurrence_key }}"
                                           target="_blank" title="View on GBIF"
                                           class="text-gray-400 hover:text-green-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

                @if(request()->has('before') || $nextId)
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                    @php
                        $baseParams = array_filter(['status' => $status, 'country' => $country]);
                    @endphp
                    <div>
                        @if(request()->has('before'))
                            <a href="{{ route('impact', $baseParams) }}" class="text-xs text-gray-500 hover:text-green-600">← Back to start</a>
                        @endif
                    </div>
                    @if($nextId)
                        <a href="{{ route('impact', array_merge($baseParams, ['before' => $nextId])) }}"
                           class="text-xs bg-white border border-gray-200 hover:border-green-500 text-gray-600 hover:text-green-600 rounded-lg px-4 py-1.5 transition-colors">
                            Next →
                        </a>
                    @endif
                </div>
                @endif
            @endif
        </div>
    </div>

</x-layouts.app>
