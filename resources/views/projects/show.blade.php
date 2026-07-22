<x-layouts.app :title="$project->title" :description="$project->description ? \Illuminate\Support\Str::limit($project->description, 160) : null">

    @php
        $pct = $stats['total'] > 0 ? round($stats['georeferenced'] / $stats['total'] * 100) : 0;
        $barColor = $pct >= 75 ? '#22c55e' : ($pct >= 40 ? '#fbbf24' : '#f87171');
        $isOwner = $project->isOwnedBy(auth()->user());
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
                <p class="text-sm text-gray-500 mt-0.5">{{ $project->user->name ?? '' }}</p>
                @if($project->tags)
                <p class="text-xs text-gray-400 mt-1">{{ $project->tags }}</p>
                @endif
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

        {{-- Stats --}}
        <div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ __('Total') }}</div>
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
            <div class="flex items-center gap-2 mt-3">
                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
                </div>
                <span class="text-sm text-gray-500 tabular-nums">{{ $pct }}%</span>
            </div>
            @if(($stats['total'] - $stats['georeferenced']) > 0)
            <a href="{{ route('georef.index') }}?project={{ $project->id }}"
               class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg px-4 py-2">
                {{ __('Georeference') }}
            </a>
            @endif
        </div>

        {{-- Contributors --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ __('Contributors') }}</h2>

            @if(empty($contributors))
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
