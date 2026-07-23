<x-layouts.app>
<x-slot name="title">Projects — georeference.it</x-slot>

<div class="space-y-4 pb-16">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Projects') }}</h1>
        @auth
        <a href="{{ route('projects.create') }}" class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
            {{ __('Create Project') }}
        </a>
        @endauth
    </div>

    <p class="text-sm text-gray-500">
        {{ __('Thematic projects — a saved set of occurrences (by criteria or a pasted list) that you or others can georeference together.') }}
    </p>

    @if(session('status') === 'project-created')
        <p class="text-sm text-green-600 font-medium">{{ __('Project created.') }}</p>
    @elseif(session('status') === 'project-updated')
        <p class="text-sm text-green-600 font-medium">{{ __('Project updated.') }}</p>
    @elseif(session('status') === 'project-deleted')
        <p class="text-sm text-green-600 font-medium">{{ __('Project deleted.') }}</p>
    @endif

    <form method="GET" action="{{ route('projects') }}" class="flex flex-wrap gap-2 items-end">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="{{ __('Search title or tags...') }}"
                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-500">
        </div>
        <button type="submit" class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">{{ __('Search') }}</button>
        @if(request('q'))
        <a href="{{ route('projects') }}" class="text-sm text-gray-500 hover:text-gray-700 px-2 py-2">{{ __('Clear') }}</a>
        @endif
    </form>

    <p class="text-sm text-gray-500">
        {{ __('Showing') }} <strong>{{ $projects->firstItem() ?? 0 }}–{{ $projects->lastItem() ?? 0 }}</strong>
        {{ __('of') }} <strong>{{ number_format($projects->total()) }}</strong> {{ __('projects') }}
        @if(request('q')) {{ __('matching') }} "<em>{{ request('q') }}</em>"@endif
    </p>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Project') }}</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-24">{{ __('Specimens') }}</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-28">{{ __('Georeferenced') }}</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-24">{{ __('Validated') }}</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-24">{{ __('Missing') }}</th>
                        <th class="px-4 py-3 w-20 text-xs font-medium text-gray-500 uppercase tracking-wide">%</th>
                        <th class="px-4 py-3 w-40"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($projects as $project)
                    @php
                        $s = $stats[$project->id];
                        $isComputing = $computing[$project->id];
                        $pct = $s['total'] > 0 ? round($s['georeferenced'] / $s['total'] * 100) : 0;
                        $barColor = $pct >= 75 ? '#22c55e' : ($pct >= 40 ? '#fbbf24' : '#f87171');
                        $isOwner = $project->isOwnedBy(auth()->user());
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($project->image)
                                <img src="{{ $project->image }}" alt="" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex-shrink-0"></div>
                                @endif
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white leading-snug text-sm">
                                        <a href="{{ route('projects.show', $project->id) }}" class="hover:text-green-600">{{ $project->title }}</a>
                                        <span class="ml-1 text-xs font-normal px-1.5 py-0.5 rounded {{ $project->visibility === 'public' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $project->visibility === 'public' ? __('Public') : __('Private') }}
                                        </span>
                                    </div>
                                    @if($project->description)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ \Illuminate\Support\Str::limit($project->description, 100) }}</div>
                                    @endif
                                    <div class="text-xs text-gray-400 mt-1.5">{{ __('by') }} {{ $project->user->name ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        @if($isComputing)
                        <td colspan="5" class="px-4 py-3">
                            <div class="flex items-center gap-1.5 text-xs text-gray-400">
                                <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                {{ __('Preparing project…') }}
                            </div>
                        </td>
                        @else
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300 tabular-nums">
                            {{ number_format($s['total']) }}
                            <div class="text-xs text-gray-400 font-normal">{{ trans_choice('{1} :count locality|[2,*] :count localities', $s['locality_groups'] ?? 0, ['count' => number_format($s['locality_groups'] ?? 0)]) }}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300 tabular-nums">{{ number_format($s['georeferenced']) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-green-700 dark:text-green-400 tabular-nums">{{ number_format($s['validated']) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-red-500 tabular-nums">{{ number_format($s['total'] - $s['georeferenced']) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-8 text-right tabular-nums">{{ $pct }}%</span>
                            </div>
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                @if(!$isComputing && ($s['total'] - $s['georeferenced']) > 0)
                                <a href="{{ route('georef.index') }}?project={{ $project->id }}"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg px-3 py-1.5 whitespace-nowrap transition-colors">
                                    {{ __('Georeference') }}
                                </a>
                                @endif
                                @if($isOwner)
                                <a href="{{ route('projects.edit', $project->id) }}" class="text-xs text-gray-400 hover:text-gray-600 whitespace-nowrap">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('projects.destroy', $project->id) }}" onsubmit="return confirm('{{ __('Delete this project?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 whitespace-nowrap">{{ __('Delete') }}</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">{{ __('No projects yet.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $projects->links() }}
        </div>
        @endif
    </div>
</div>
</x-layouts.app>
