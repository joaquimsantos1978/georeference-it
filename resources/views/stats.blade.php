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
            $pctValidated = $totalOcc > 0 ? number_format($validatedOcc / $totalOcc * 100, 1, '.', '') : 0;
            $pctPending   = $totalOcc > 0 ? number_format($pendingOcc   / $totalOcc * 100, 1, '.', '') : 0;
            $pctGbif      = $totalOcc > 0 ? number_format($gbifOcc      / $totalOcc * 100, 1, '.', '') : 0;
            $pctUngeoref  = $totalOcc > 0 ? number_format($needsGeoref  / $totalOcc * 100, 1, '.', '') : 0;
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="text-xs text-gray-500 mb-2">{{ number_format($pendingGroups) }} locality groups with work remaining</div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                <div class="bg-green-500 h-4 rounded-full" style="width:{{ number_format($hasCoords / $totalOcc * 100, 2, '.', '') }}%"></div>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-1 mt-3 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-green-500"></span> Coordinates from GBIF <strong class="text-gray-700 dark:text-gray-300">{{ number_format($gbifOcc) }}</strong> ({{ $pctGbif }}%)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-green-700"></span> Validated by community <strong class="text-gray-700 dark:text-gray-300">{{ number_format($validatedOcc) }}</strong> ({{ $pctValidated }}%)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-orange-400"></span> Pending review <strong class="text-gray-700 dark:text-gray-300">{{ number_format($pendingOcc) }}</strong> ({{ $pctPending }}%)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-gray-200"></span> No coordinates <strong class="text-gray-700 dark:text-gray-300">{{ number_format($needsGeoref) }}</strong> ({{ $pctUngeoref }}%)</span>
            </div>
        </div>

        {{-- Per-country table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">By country</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Country</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Total</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Remaining</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-48">Progress</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($byCountry as $row)
                        @php
                            $tot   = (int) $row->total_occ;
                            $ung   = (int) $row->ungeoref_occ;
                            $pen   = (int) $row->pending_occ;
                            $val   = (int) $row->validated_occ;
                            $geo   = $tot - $ung - $pen;
                            $pct   = $tot > 0 ? round($geo / $tot * 100) : 0;
                            $pctV  = $tot > 0 ? number_format($val / $tot * 100, 2, '.', '') : 0;
                            $pctG  = $tot > 0 ? number_format(max(0, ($geo - $val) / $tot * 100), 2, '.', '') : 0;
                            $pctP  = $tot > 0 ? number_format($pen / $tot * 100, 2, '.', '') : 0;
                            $cc    = $row->country_code ? strtolower($row->country_code) : '';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-200">
                                @if($row->country_code)
                                    <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded mr-2">{{ strtoupper($row->country_code) }}</span>
                                @endif
                                {{ $row->country_code ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 tabular-nums">{{ number_format($tot) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $ung > 0 ? 'text-orange-500 font-medium' : 'text-gray-400' }}">
                                {{ $ung > 0 ? number_format($ung) : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden flex">
                                        <div class="bg-green-600 h-2" style="width:{{ $pctV }}%"></div>
                                        <div class="bg-green-300 h-2" style="width:{{ $pctG }}%"></div>
                                        <div class="bg-orange-300 h-2" style="width:{{ $pctP }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400 w-8 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($ung > 0 || $pen > 0)
                                <a href="{{ route('georef.index') }}?country={{ strtoupper($row->country_code) }}"
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

</x-layouts.app>
