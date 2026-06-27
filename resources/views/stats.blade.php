<x-layouts.app title="Georeferencing Progress" description="Track the global progress of georeferencing natural history specimens on georeference.it.">

    <div class="space-y-8 max-w-5xl mx-auto">

        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Georeferencing Progress</h1>
            <p class="text-sm text-gray-500 mt-1">How much is left to georeference across all collections.</p>
        </div>

        {{-- Global summary cards --}}
        @php
            $totalOcc      = (int) $global->total_occ;
            $ungeorefOcc   = (int) $global->ungeoref_occ;
            $pendingOcc    = (int) $global->pending_occ;
            $validatedOcc  = (int) $global->validated_occ;
            $gbifOcc       = (int) $global->gbif_occ + (int) $global->gbif_reviewed_occ;
            $pendingGroups = (int) $global->pending_groups;
            // "needs georef" = no coordinates at all
            $needsGeoref   = $ungeorefOcc;
            // "has coords" = gbif + validated + pending review
            $hasCoords     = $totalOcc - $ungeorefOcc;
            $pctDone       = $totalOcc > 0 ? round($hasCoords / $totalOcc * 100, 1) : 0;
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalOcc) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Total occurrences</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-2xl font-bold text-green-600">{{ number_format($hasCoords) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Have coordinates</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-2xl font-bold text-orange-500">{{ number_format($needsGeoref) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Need georeferencing</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $pctDone }}%</div>
                <div class="text-xs text-gray-500 mt-0.5">Have coordinates</div>
            </div>
        </div>

        {{-- Global progress bar --}}
        @php
            $pctValidated = $totalOcc > 0 ? number_format($validatedOcc / $totalOcc * 100, 2, '.', '') : '0';
            $pctPending   = $totalOcc > 0 ? number_format($pendingOcc   / $totalOcc * 100, 2, '.', '') : '0';
            $pctGbif      = $totalOcc > 0 ? number_format($gbifOcc      / $totalOcc * 100, 2, '.', '') : '0';
            $pctUngeoref  = $totalOcc > 0 ? number_format($needsGeoref  / $totalOcc * 100, 1, '.', '') : '0';
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="text-xs text-gray-500 mb-2">{{ number_format($pendingGroups) }} locality groups with work remaining</div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden flex">
                <div class="bg-green-500 h-4" style="width:{{ $pctGbif }}%"></div>
                <div class="bg-green-700 h-4" style="width:{{ $pctValidated }}%"></div>
                <div class="bg-orange-400 h-4" style="width:{{ $pctPending }}%"></div>
            </div>
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-x-6 gap-y-2 mt-4 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm flex-shrink-0 bg-green-500"></span> Coordinates from GBIF <strong class="text-gray-700 dark:text-gray-300 ml-1">{{ number_format($gbifOcc) }}</strong>&nbsp;({{ $pctGbif }}%)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm flex-shrink-0 bg-green-700"></span> Validated by community <strong class="text-gray-700 dark:text-gray-300 ml-1">{{ number_format($validatedOcc) }}</strong>&nbsp;({{ $pctValidated }}%)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm flex-shrink-0 bg-orange-400"></span> Pending review <strong class="text-gray-700 dark:text-gray-300 ml-1">{{ number_format($pendingOcc) }}</strong>&nbsp;({{ $pctPending }}%)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm flex-shrink-0 bg-gray-200 dark:bg-gray-600"></span> No coordinates <strong class="text-gray-700 dark:text-gray-300 ml-1">{{ number_format($needsGeoref) }}</strong>&nbsp;({{ $pctUngeoref }}%)</span>
            </div>
        </div>

        {{-- Per-country table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">By country</h2>
                <select id="sort-select" onchange="sortTable(this.value)" class="text-xs border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 cursor-pointer">
                    <option value="remaining">Most work remaining</option>
                    <option value="alpha">Alphabetical</option>
                    <option value="progress">Progress (low → high)</option>
                    <option value="total">Total occurrences</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table id="country-table" class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Country</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Total</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Remaining</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-48">Progress</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide"></th>
                        </tr>
                    </thead>
                    <tbody id="country-tbody" class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($byCountry as $row)
                        @php
                            $tot   = (int) $row->total_occ;
                            $ung   = (int) $row->ungeoref_occ;
                            $pen   = (int) $row->pending_occ;
                            $val   = (int) $row->validated_occ;
                            $geo   = $tot - $ung - $pen;
                            $pct   = $tot > 0 ? round($geo / $tot * 100) : 0;
                            $pctGbif = $tot > 0 ? number_format(max(0, $geo - $val) / $tot * 100, 2, '.', '') : 0;
                            $pctVal  = $tot > 0 ? number_format($val / $tot * 100, 2, '.', '') : 0;
                            $pctPen  = $tot > 0 ? number_format($pen / $tot * 100, 2, '.', '') : 0;
                            $cc      = strtoupper($row->country_code ?? '');
                            $name    = $cc ? (\Locale::getDisplayRegion('-'.$cc, 'en') ?: $cc) : '—';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            data-name="{{ $name }}"
                            data-remaining="{{ $ung }}"
                            data-progress="{{ $pct }}"
                            data-total="{{ $tot }}">
                            <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-200">
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-1.5 py-0.5 rounded mr-2">{{ $cc }}</span>
                                {{ $name }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 tabular-nums">{{ number_format($tot) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $ung > 0 ? 'text-orange-500 font-medium' : 'text-gray-400' }}">
                                {{ $ung > 0 ? number_format($ung) : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden flex">
                                        <div class="bg-green-700 h-2" style="width:{{ $pctVal }}%"></div>
                                        <div class="bg-green-500 h-2" style="width:{{ $pctGbif }}%"></div>
                                        <div class="bg-orange-400 h-2" style="width:{{ $pctPen }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400 w-8 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($ung > 0 || $pen > 0)
                                <a href="{{ route('georef.index') }}?country={{ $cc }}"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg px-3 py-1.5 whitespace-nowrap transition-colors">
                                    Georeference
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </a>
                                @else
                                <span class="text-xs text-green-600 font-medium">Complete</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
    function sortTable(by) {
        var tbody = document.getElementById('country-tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort(function(a, b) {
            if (by === 'alpha')     return a.dataset.name.localeCompare(b.dataset.name);
            if (by === 'remaining') return parseInt(b.dataset.remaining) - parseInt(a.dataset.remaining);
            if (by === 'progress')  return parseInt(a.dataset.progress)  - parseInt(b.dataset.progress);
            if (by === 'total')     return parseInt(b.dataset.total)     - parseInt(a.dataset.total);
        });
        rows.forEach(function(r) { tbody.appendChild(r); });
    }
    </script>

</x-layouts.app>
