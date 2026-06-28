<x-layouts.app title="Leaderboard" description="See the top contributors helping to georeference natural history specimens on georeference.it. Join the community and make biodiversity data more useful.">

    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Leaderboard') }}</h1>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-12">#</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('User') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Level') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Validated georefs') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Suggestions') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Reviews') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($users as $i => $user)
                    <tr class="{{ auth()->check() && auth()->id() === $user->id ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                        <td class="px-5 py-3 text-gray-400 font-mono">
                            @if($i === 0) 🥇
                            @elseif($i === 1) 🥈
                            @elseif($i === 2) 🥉
                            @else {{ $i + 1 }}
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                @php $isPublic = $user->public_name || (auth()->check() && auth()->id() === $user->id); @endphp
                                @if($isPublic && $user->avatar)
                                    <img src="{{ $user->avatar }}" class="h-7 w-7 rounded-full">
                                @else
                                    <div class="h-7 w-7 rounded-full {{ $isPublic ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }} flex items-center justify-center text-white text-xs font-bold">
                                        {{ $isPublic ? strtoupper(substr($user->name, 0, 1)) : '?' }}
                                    </div>
                                @endif
                                <span class="font-medium {{ $isPublic ? 'text-gray-900 dark:text-white' : 'text-gray-400 italic' }}">
                                    {{ $isPublic ? $user->name : 'Anonymous' }}
                                </span>
                                @if($isPublic && $user->orcid)
                                    <a href="https://orcid.org/{{ $user->orcid }}" target="_blank" class="text-xs text-green-600 hover:underline">ORCID</a>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $user->userLevel?->name ?? '—' }}</td>
                        <td class="px-5 py-3 font-semibold text-gray-900 dark:text-white">{{ number_format($user->total_validated) }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ number_format($user->suggestions_count) }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ number_format($user->reviews_count) }}</td>
                        <td class="px-5 py-3">
                            @if($user->public_name || (auth()->check() && auth()->id() === $user->id))
                            <a href="{{ route('activity') }}?user={{ $user->id }}" class="text-xs text-green-600 hover:underline">Activity</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-gray-400">{{ __('No contributors yet.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-layouts.app>
