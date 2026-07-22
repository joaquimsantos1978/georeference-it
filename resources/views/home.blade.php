<x-layouts.app description="georeference.it is a crowdsourced platform to add coordinates to ungeoreferenced natural history specimens from GBIF. Help map biodiversity collections worldwide.">

    @php
        $totalOcc    = (int) $global->total_occ;
        $ungeorefOcc = (int) $global->ungeoref_occ;
        $hasCoords   = $totalOcc - $ungeorefOcc;
        $pctDone     = $totalOcc > 0 ? round($hasCoords / $totalOcc * 100, 1) : 0;
    @endphp

    <div class="space-y-10 pb-16">

        {{-- Hero --}}
        <div class="text-center max-w-2xl mx-auto space-y-4 pt-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">georeference.it</h1>
            <p class="text-gray-500 dark:text-gray-400">
                {{ __('georeference.it is an open-source crowdsourcing platform for georeferencing natural history collection specimens from GBIF.') }}
            </p>
            <div class="flex gap-3 justify-center flex-wrap">
                <a href="{{ route('georef.index') }}" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium">
                    {{ __('Start georeferencing') }}
                </a>
                <a href="{{ route('projects') }}" class="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800">
                    {{ __('Browse projects') }}
                </a>
            </div>
        </div>

        {{-- Stats summary --}}
        <div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalOcc) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ __('Total occurrences') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-green-600">{{ number_format($hasCoords) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ __('Have coordinates') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-orange-500">{{ number_format($ungeorefOcc) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ __('Need georeferencing') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $pctDone }}%</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ __('Have coordinates') }}</div>
                </div>
            </div>
            <div class="text-right mt-2">
                <a href="{{ route('stats') }}" class="text-xs text-green-600 hover:underline">{{ __('Full stats') }} →</a>
            </div>
        </div>

        {{-- Projects showcase --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Projects') }}</h2>
                <div class="flex items-center gap-3">
                    @auth
                    <a href="{{ route('projects.create') }}" class="text-sm text-green-600 hover:underline">{{ __('Create Project') }}</a>
                    @endauth
                    <a href="{{ route('projects') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('See all') }} →</a>
                </div>
            </div>

            @if($projects->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center text-sm text-gray-400">
                    {{ __('No projects yet.') }}
                </div>
            @else
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($projects as $project)
                        @php
                            $s = $projectStats[$project->id];
                            $pct = $s['total'] > 0 ? round($s['georeferenced'] / $s['total'] * 100) : 0;
                            $barColor = $pct >= 75 ? '#22c55e' : ($pct >= 40 ? '#fbbf24' : '#f87171');
                        @endphp
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center gap-3">
                                @if($project->image)
                                <img src="{{ $project->image }}" alt="" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex-shrink-0"></div>
                                @endif
                                <div class="min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $project->title }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $project->user->name ?? '' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 mt-3">
                                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-8 text-right tabular-nums">{{ $pct }}%</span>
                            </div>
                            @if(($s['total'] - $s['georeferenced']) > 0)
                            <a href="{{ route('georef.index') }}?project={{ $project->id }}"
                               class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg px-3 py-1.5">
                                {{ __('Georeference') }}
                            </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Recent activity --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Recent activity') }}</h2>
                <a href="{{ route('activity') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('See all') }} →</a>
            </div>

            @if($recentActivity->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center text-sm text-gray-400">
                    {{ __('No activity yet.') }}
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recentActivity as $event)
                        <div class="px-4 py-2.5 flex items-center justify-between text-sm">
                            <div class="min-w-0 flex-1">
                                <span class="text-gray-700 dark:text-gray-200">{{ $event->user_name ?? __('Hidden contributor') }}</span>
                                <span class="text-gray-400"> — </span>
                                <span class="text-gray-500 truncate">{{ $event->location_label ?: $event->country_code }}</span>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0 ml-3">{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- About blurb --}}
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6 border border-green-200 dark:border-green-700 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                {{ __('Millions of biodiversity records in GBIF lack geographic coordinates. Volunteers place these specimens on the map using a consensus-based validation system.') }}
            </p>
            <a href="{{ route('about') }}" class="text-sm text-green-600 hover:underline">{{ __('Learn more') }} →</a>
        </div>
    </div>
</x-layouts.app>
