<x-layouts.app title="Impact" description="Specimens georeferenced or improved through georeference.it — making biodiversity data more useful.">

    <div class="space-y-6">

        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Impact') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ trans_choice('{1} :count specimen georeferenced or improved on this platform|[2,*] :count specimens georeferenced or improved on this platform', $totalCount, ['count' => number_format($totalCount)]) }}</p>
            </div>

            <form method="GET" action="{{ route('impact') }}" class="flex items-center gap-2 flex-wrap">
                <select name="status" onchange="this.form.submit()"
                    class="text-xs border border-gray-200 dark:border-gray-600 rounded-lg pl-2 pr-7 py-1.5 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 cursor-pointer">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="validated"      {{ $status === 'validated'      ? 'selected' : '' }}>{{ __('Validated') }}</option>
                    <option value="has_suggestion" {{ $status === 'has_suggestion' ? 'selected' : '' }}>{{ __('Suggestion pending') }}</option>
                    <option value="gbif_reviewed"  {{ $status === 'gbif_reviewed'  ? 'selected' : '' }}>{{ __('GBIF reviewed') }}</option>
                </select>
                <select name="country" onchange="this.form.submit()"
                    class="text-xs border border-gray-200 dark:border-gray-600 rounded-lg pl-2 pr-7 py-1.5 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 cursor-pointer">
                    <option value="">{{ __('All countries') }}</option>
                    @php
                        $countryOptions = $countries->map(function ($code) {
                            $name = class_exists('Locale')
                                ? (\Locale::getDisplayRegion('-'.$code, app()->getLocale()) ?: $code)
                                : $code;
                            return ['code' => $code, 'name' => $name];
                        })->sortBy('name', SORT_FLAG_CASE | SORT_STRING);
                    @endphp
                    @foreach($countryOptions as $opt)
                        <option value="{{ $opt['code'] }}" {{ $country === $opt['code'] ? 'selected' : '' }}>{{ $opt['name'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="text-xs bg-green-600 hover:bg-green-700 text-white rounded-lg px-3 py-1.5 transition-colors">{{ __('Filter') }}</button>
                @if($status || $country)
                    <a href="{{ route('impact') }}" class="text-xs text-gray-400 hover:text-gray-600 py-1.5">{{ __('Clear') }}</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

            @if($occurrences->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-gray-400">{{ __('No specimens found.') }}</div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Specimen') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Locality') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('GBIF coords') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($occurrences as $occ)
                        @php
                            $interpretedParts = array_filter([
                                $occ->municipality,
                                $occ->county,
                                $occ->state_province,
                                $occ->continent,
                                $occ->island ?: ($occ->island_group ?: null),
                                $occ->water_body,
                                $occ->higher_geography,
                            ]);
                            $interpreted = trim(implode(', ', $interpretedParts));

                            $catalogRef = trim(implode(' ', array_filter([
                                $occ->institution_code,
                                $occ->collection_code,
                                $occ->catalog_number,
                            ])));

                            $pills = [
                                'has_suggestion' => ['label' => __('Pending'),       'class' => 'text-amber-600 bg-amber-50 border-amber-200'],
                                'validated'      => ['label' => __('Validated'),     'class' => 'text-green-600 bg-green-50 border-green-200'],
                                'gbif_reviewed'  => ['label' => __('GBIF reviewed'), 'class' => 'text-blue-600 bg-blue-50 border-blue-200'],
                            ];
                            $pill = $pills[$occ->georef_status] ?? ['label' => $occ->georef_status, 'class' => 'text-gray-500 bg-gray-50 border-gray-200'];

                            $eventYear = $occ->event_date ? substr($occ->event_date, 0, 4) : null;
                            $country = $occ->country_code ? strtoupper($occ->country_code) : null;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <td class="px-4 py-3 max-w-[220px]">
                                <div class="font-medium italic text-gray-900 dark:text-white text-xs leading-snug">{{ $occ->scientific_name }}</div>
                                <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-1">
                                    @if($country)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-mono">{{ $country }}</span>
                                    @endif
                                    @if($catalogRef)
                                        <span class="text-xs text-gray-500 font-mono">{{ $catalogRef }}</span>
                                    @endif
                                    @if($eventYear)
                                        <span class="text-xs text-gray-400">{{ $eventYear }}</span>
                                    @endif
                                </div>
                                @if($occ->recorded_by)
                                    <div class="text-xs text-gray-400 truncate max-w-[200px] mt-0.5" title="{{ $occ->recorded_by }}">{{ $occ->recorded_by }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs max-w-xs">
                                @if($occ->verbatim_locality)
                                    <div class="text-gray-700 dark:text-gray-200 line-clamp-2" title="{{ $occ->verbatim_locality }}">{{ $occ->verbatim_locality }}</div>
                                @endif
                                @if($interpreted)
                                    <div class="text-gray-400 mt-0.5 line-clamp-2" title="{{ $interpreted }}">{{ $interpreted }}</div>
                                @endif
                                @if($occ->location_remarks)
                                    <div class="text-gray-400 italic mt-0.5 line-clamp-1" title="{{ $occ->location_remarks }}">{{ $occ->location_remarks }}</div>
                                @endif
                                @if($occ->country || $country)
                                    <div class="text-gray-400 mt-0.5">{{ $occ->country }}@if($occ->country && $country) <span class="font-mono">({{ $country }})</span>@elseif($country)<span class="font-mono">{{ $country }}</span>@endif</div>
                                @endif
                                @if(!$occ->verbatim_locality && !$interpreted && !$occ->country && !$country)
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
                                @if($occ->suggestion_id)
                                    @php
                                        $markerPct = min(100, max(0, ($occ->total_points + $threshold) / (2 * $threshold) * 100));
                                    @endphp
                                    <div class="votes-bar" onclick="showImpactVotesPopup({{ $occ->suggestion_id }}, event)"
                                         style="cursor:pointer;position:relative;height:6px;width:80px;border-radius:4px;margin-top:4px;background:linear-gradient(to right,#b91c1c,#f59e0b,#15803d);">
                                        <div style="position:absolute;top:0;height:100%;width:2px;background:rgba(0,0,0,0.5);transform:translateX(-50%);left:{{ $markerPct }}%;pointer-events:none;"></div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 justify-end">
                                    @if($occ->locality_group_id)
                                        <a href="{{ route('georef.index') }}?group={{ $occ->locality_group_id }}"
                                           title="{{ __('Open in georeference.it') }}"
                                           class="text-gray-400 hover:text-green-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if($occ->gbif_occurrence_key)
                                        <a href="https://www.gbif.org/occurrence/{{ $occ->gbif_occurrence_key }}"
                                           target="_blank" title="{{ __('View on GBIF') }}"
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

                @if($occurrences->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $occurrences->links() }}
                </div>
                @endif
            @endif
        </div>
    </div>

    <script>
    function escHtmlImpact(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    async function showImpactVotesPopup(suggestionId, event) {
        event.stopPropagation();
        var anchor = event.currentTarget;
        var existing = document.getElementById('votes-popup');
        if (existing) existing.remove();
        var popup = document.createElement('div');
        popup.id = 'votes-popup';
        popup.style.cssText = 'position:fixed;z-index:9999;background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;font-size:11px;color:#374151;max-width:260px;box-shadow:0 4px 12px rgba(0,0,0,0.15);line-height:1.6;';
        popup.textContent = {{ Illuminate\Support\Js::from(__('Loading votes...')) }};
        document.body.appendChild(popup);
        var r = anchor.getBoundingClientRect();
        popup.style.left = Math.min(r.left, window.innerWidth - 268) + 'px';
        popup.style.top  = (r.bottom + 4) + 'px';
        var close = function(){ popup.remove(); document.removeEventListener('click', close); };
        setTimeout(function(){ document.addEventListener('click', close); }, 0);
        try {
            const data = await (await fetch('{{ url("/georef/suggestion") }}/'+suggestionId+'/votes')).json();
            if (!document.body.contains(popup)) return;
            var fmt = function(list) { return list.map(function(v){ return escHtmlImpact(v.name)+' ('+v.points+(v.points===1?' pt':' pts')+')'; }).join(', '); };
            var html = '';
            if (data.agree.length) html += '<div><strong style="color:#15803d">'+{{ Illuminate\Support\Js::from(__('Agreed')) }}+':</strong> '+fmt(data.agree)+'</div>';
            if (data.disagree.length) html += '<div style="margin-top:4px"><strong style="color:#b91c1c">'+{{ Illuminate\Support\Js::from(__('Disagreed')) }}+':</strong> '+fmt(data.disagree)+'</div>';
            popup.innerHTML = html || '<span style="color:#9ca3af">'+{{ Illuminate\Support\Js::from(__('No votes yet.')) }}+'</span>';
        } catch(e) { popup.textContent = {{ Illuminate\Support\Js::from(__('Search failed.')) }}; }
    }
    </script>

</x-layouts.app>
