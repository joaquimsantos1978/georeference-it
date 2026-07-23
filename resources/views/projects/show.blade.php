<x-layouts.app :title="$project->title" :description="$project->description ? \Illuminate\Support\Str::limit($project->description, 160) : null">

    @php
        $pct = $stats['total'] > 0 ? round($stats['georeferenced'] / $stats['total'] * 100) : 0;
        $isOwner = $project->isOwnedBy(auth()->user());

        // Same four-way breakdown as the global Stats page's progress bar.
        $total        = $stats['total'] ?? 0;
        $gbifCount    = $stats['gbif'] ?? 0;
        $validCount   = $stats['validated'] ?? 0;
        $pendingCount = $stats['pending'] ?? 0;
        $noCoordCount = $stats['ungeoreferenced'] ?? 0;
        $pctGbif      = $total > 0 ? number_format($gbifCount    / $total * 100, 2, '.', '') : '0';
        $pctValidated = $total > 0 ? number_format($validCount   / $total * 100, 2, '.', '') : '0';
        $pctPending   = $total > 0 ? number_format($pendingCount / $total * 100, 2, '.', '') : '0';
        $pctNoCoord   = $total > 0 ? number_format($noCoordCount / $total * 100, 1, '.', '') : '0';
        $tagList = $project->tags ? array_filter(array_map('trim', explode(',', $project->tags))) : [];
    @endphp

    <div class="max-w-4xl mx-auto space-y-6 pb-16">

        <a href="{{ route('projects') }}" class="text-sm text-gray-500 hover:text-gray-700">← {{ __('Projects') }}</a>

        <div class="flex items-start gap-4">
            @if($project->image)
            <img src="{{ $project->image }}" alt="" class="w-20 h-20 rounded-xl object-cover flex-shrink-0">
            @else
            <div class="w-20 h-20 rounded-xl bg-gray-100 dark:bg-gray-700 flex-shrink-0"></div>
            @endif
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $project->title }}</h1>
                    <span class="text-xs font-normal px-1.5 py-0.5 rounded {{ $project->visibility === 'public' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $project->visibility === 'public' ? __('Public') : __('Private') }}
                    </span>
                </div>
            </div>
            @if($isOwner)
            <div class="flex items-center gap-3 flex-shrink-0">
                <a href="{{ route('projects.edit', $project->id) }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('projects.destroy', $project->id) }}" onsubmit="return confirm('{{ __('Delete this project?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-400 hover:text-red-600">{{ __('Delete') }}</button>
                </form>
            </div>
            @endif
        </div>

        @if($project->description)
        <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $project->description }}</p>
        @endif

        <div class="space-y-2">
            @if(!empty($tagList))
            <div class="flex flex-wrap gap-1.5">
                @foreach($tagList as $tag)
                <a href="{{ route('projects') }}?q={{ urlencode($tag) }}"
                   class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-full px-2.5 py-1">
                    {{ $tag }}
                </a>
                @endforeach
            </div>
            @endif
            <p class="text-xs text-gray-400">{{ __('by') }} {{ $project->user->name ?? '' }}</p>
        </div>

        {{-- Stats --}}
        <div>
            @if($statsComputing)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 flex items-center justify-center gap-2 text-sm text-gray-400">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                {{ __('Preparing project…') }}
            </div>
            @else
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ __('Specimens') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-green-600">{{ number_format($stats['georeferenced']) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ __('Georeferenced') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-green-700 dark:text-green-400">{{ number_format($stats['validated']) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ __('Validated') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-red-500">{{ number_format($stats['total'] - $stats['georeferenced']) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ __('Missing') }}</div>
                </div>
            </div>
            <div class="w-full rounded-full h-4 overflow-hidden flex mt-3" style="background:#e5e7eb">
                <div style="width:{{ $pctGbif }}%;background:#22c55e;height:100%"></div>
                <div style="width:{{ $pctValidated }}%;background:#15803d;height:100%"></div>
                <div style="width:{{ $pctPending }}%;background:#fb923c;height:100%"></div>
            </div>
            <div class="flex flex-wrap items-center gap-y-2 mt-3 text-xs text-gray-500" style="gap-x:0">
                <span class="flex items-center gap-1.5" style="padding-right:20px"><span class="inline-block w-2.5 h-2.5 rounded-sm flex-shrink-0" style="background:#22c55e"></span> {{ __('Coordinates from GBIF') }} <strong class="text-gray-700 dark:text-gray-300 ml-1">{{ number_format($gbifCount) }}</strong>&nbsp;({{ $pctGbif }}%)</span>
                <span style="border-left:1px solid #d1d5db;height:14px;margin-right:20px"></span>
                <span class="flex items-center gap-1.5" style="padding-right:20px"><span class="inline-block w-2.5 h-2.5 rounded-sm flex-shrink-0" style="background:#15803d"></span> {{ __('Validated by community') }} <strong class="text-gray-700 dark:text-gray-300 ml-1">{{ number_format($validCount) }}</strong>&nbsp;({{ $pctValidated }}%)</span>
                <span style="border-left:1px solid #d1d5db;height:14px;margin-right:20px"></span>
                <span class="flex items-center gap-1.5" style="padding-right:20px"><span class="inline-block w-2.5 h-2.5 rounded-sm flex-shrink-0" style="background:#fb923c"></span> {{ __('Pending review') }} <strong class="text-gray-700 dark:text-gray-300 ml-1">{{ number_format($pendingCount) }}</strong>&nbsp;({{ $pctPending }}%)</span>
                <span style="border-left:1px solid #d1d5db;height:14px;margin-right:20px"></span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm flex-shrink-0 border border-gray-300" style="background:#e5e7eb"></span> {{ __('No coordinates') }} <strong class="text-gray-700 dark:text-gray-300 ml-1">{{ number_format($noCoordCount) }}</strong>&nbsp;({{ $pctNoCoord }}%)</span>
            </div>
            <div class="text-xs text-gray-400 mt-2">
                {{ trans_choice('{1} :count locality|[2,*] :count localities', $stats['locality_groups'] ?? 0, ['count' => number_format($stats['locality_groups'] ?? 0)]) }}
                @if(($stats['locality_groups_missing'] ?? 0) > 0)
                    — {{ trans_choice('{1} :count still needs georeferencing|[2,*] :count still need georeferencing', $stats['locality_groups_missing'], ['count' => number_format($stats['locality_groups_missing'])]) }}
                @endif
            </div>
            @if(($stats['total'] - $stats['georeferenced']) > 0)
            <a href="{{ route('georef.index') }}?project={{ $project->id }}"
               class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg px-4 py-2">
                {{ __('Georeference') }}
            </a>
            @endif
            @endif
        </div>

        {{-- Contributors --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ __('Contributors') }}</h2>

            @if($contributorsComputing)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 flex items-center justify-center gap-2 text-sm text-gray-400">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                    {{ __('Preparing project…') }}
                </div>
            @elseif(empty($contributors))
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center text-sm text-gray-400">
                    {{ __('No contributions yet.') }}
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 uppercase tracking-wide w-10">#</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('User') }}</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Georeferences') }}</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Last contribution') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($contributors as $i => $c)
                                @php $isPublic = $c['public_name'] || (auth()->check() && auth()->id() === $c['id']); @endphp
                                <tr>
                                    <td class="px-4 py-2.5 text-gray-400 font-mono">
                                        @if($i === 0) 🥇
                                        @elseif($i === 1) 🥈
                                        @elseif($i === 2) 🥉
                                        @else {{ $i + 1 }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if($isPublic)
                                            <a href="{{ route('user.profile', $c['id']) }}" class="font-medium text-gray-900 dark:text-white hover:text-green-600">{{ $c['name'] }}</a>
                                        @else
                                            <span class="text-gray-400 italic">{{ __('Hidden contributor') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-gray-600 dark:text-gray-300 tabular-nums">{{ number_format($c['georef_count']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-xs text-gray-400" title="{{ \Carbon\Carbon::parse($c['last_contribution'])->format('Y-m-d H:i') }}">
                                        {{ \Carbon\Carbon::parse($c['last_contribution'])->diffForHumans() }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
