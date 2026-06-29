<x-layouts.app :title="$user->name" :description="$user->name . ' — georeferencing contributor on georeference.it'">

    <div class="space-y-6 max-w-3xl mx-auto">

        {{-- Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-6 py-5 flex items-center gap-5">
            @if($user->avatar)
                <img src="{{ $user->avatar }}" class="w-16 h-16 rounded-full flex-shrink-0" alt="">
            @else
                <div class="w-16 h-16 rounded-full bg-green-600 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                @if($user->userLevel)
                    <div class="text-sm text-gray-500 mt-0.5">{{ $user->userLevel->name }}</div>
                @endif
                @if($user->orcid)
                    <a href="https://orcid.org/{{ $user->orcid }}" target="_blank"
                       class="inline-flex items-center gap-1 text-xs text-green-600 hover:underline mt-1">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.372 0 0 5.372 0 12s5.372 12 12 12 12-5.372 12-12S18.628 0 12 0zM7.369 4.378c.525 0 .947.431.947.947s-.422.947-.947.947-.947-.431-.947-.947.422-.947.947-.947zm-.722 3.038h1.444v10.041H6.647V7.416zm3.562 0h3.9c3.712 0 5.344 2.653 5.344 5.025 0 2.578-2.016 5.025-5.325 5.025h-3.919V7.416zm1.444 1.303v7.444h2.297c2.359 0 3.9-1.459 3.9-3.722s-1.541-3.722-3.9-3.722h-2.297z"/></svg>
                        ORCID: {{ $user->orcid }}
                    </a>
                @endif
            </div>
            {{-- Stats --}}
            <div class="flex gap-6 text-center flex-shrink-0">
                <div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['validated']) }}</div>
                    <div class="text-xs text-gray-500">Validated</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['georefs']) }}</div>
                    <div class="text-xs text-gray-500">Georefs</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['reviews']) }}</div>
                    <div class="text-xs text-gray-500">Reviews</div>
                </div>
            </div>
        </div>

        {{-- Activity --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Activity</h2>
            </div>

            @if($activities->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-gray-400">No activity yet.</div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($activities as $row)
                    @php $ago = \Carbon\Carbon::parse($row->created_at)->diffForHumans(); @endphp
                    <div class="px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors text-sm text-gray-800 dark:text-gray-200">
                        @if($row->type === 'georef')
                            <span class="text-green-600 font-medium">Georeferenced</span>
                            <a href="{{ route('georef.index') }}?group={{ $row->locality_group_id }}"
                               class="text-green-700 dark:text-green-400 hover:underline font-medium ml-1">{{ $row->location_label ?: 'unknown locality' }}</a>
                            @if($row->country_code)
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-1.5 py-0.5 rounded ml-1">{{ strtoupper($row->country_code) }}</span>
                            @endif
                            <div class="text-xs text-gray-400 mt-0.5 flex gap-3">
                                <span>{{ $ago }}</span>
                                @if($row->occ_count)
                                    <span>{{ number_format($row->occ_count) }} {{ Str::plural('specimen', $row->occ_count) }}</span>
                                @endif
                                @if($row->uncertainty_m)
                                    <span>±{{ $row->uncertainty_m >= 1000 ? round($row->uncertainty_m/1000).'km' : $row->uncertainty_m.'m' }}</span>
                                @endif
                            </div>
                        @elseif($row->type === 'validation_agree')
                            <span class="text-green-600 font-medium">Agreed with</span>
                            @if($row->lat !== null)
                                <span class="font-mono text-xs text-gray-500">{{ number_format((float)$row->lat,5) }}, {{ number_format((float)$row->lng,5) }}{{ $row->uncertainty_m ? ' ±'.number_format($row->uncertainty_m).'m' : '' }}</span>
                            @endif
                            @if($row->suggestion_user_id !== null)
                                <span class="text-gray-400">by</span>
                                @if($row->suggestion_author_name)
                                    <a href="{{ route('user.profile', $row->suggestion_author_id) }}" class="text-gray-500 hover:text-green-600">{{ $row->suggestion_author_name }}</a>
                                @else
                                    <span class="text-gray-400">Anonymous</span>
                                @endif
                            @endif
                            as georef of
                            <a href="{{ route('georef.index') }}?group={{ $row->locality_group_id }}"
                               class="text-green-700 dark:text-green-400 hover:underline font-medium">{{ $row->location_label ?: 'unknown locality' }}</a>
                            @if($row->country_code)
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-1.5 py-0.5 rounded ml-1">{{ strtoupper($row->country_code) }}</span>
                            @endif
                            <div class="text-xs text-gray-400 mt-0.5">{{ $ago }}</div>
                        @elseif($row->type === 'validation_disagree')
                            <span class="text-red-500 font-medium">Disagreed with</span>
                            @if($row->lat !== null)
                                <span class="font-mono text-xs text-gray-500">{{ number_format((float)$row->lat,5) }}, {{ number_format((float)$row->lng,5) }}{{ $row->uncertainty_m ? ' ±'.number_format($row->uncertainty_m).'m' : '' }}</span>
                            @endif
                            @if($row->suggestion_user_id !== null)
                                <span class="text-gray-400">by</span>
                                @if($row->suggestion_author_name)
                                    <a href="{{ route('user.profile', $row->suggestion_author_id) }}" class="text-gray-500 hover:text-green-600">{{ $row->suggestion_author_name }}</a>
                                @else
                                    <span class="text-gray-400">Anonymous</span>
                                @endif
                            @endif
                            as georef of
                            <a href="{{ route('georef.index') }}?group={{ $row->locality_group_id }}"
                               class="text-green-700 dark:text-green-400 hover:underline font-medium">{{ $row->location_label ?: 'unknown locality' }}</a>
                            @if($row->country_code)
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-1.5 py-0.5 rounded ml-1">{{ strtoupper($row->country_code) }}</span>
                            @endif
                            <div class="text-xs text-gray-400 mt-0.5">{{ $ago }}</div>
                        @else
                            <span class="text-gray-400">Abstained on</span>
                            <a href="{{ route('georef.index') }}?group={{ $row->locality_group_id }}"
                               class="text-green-700 dark:text-green-400 hover:underline font-medium">{{ $row->location_label ?: 'unknown locality' }}</a>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $ago }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>

                @if($activities->hasMorePages() || $activities->currentPage() > 1)
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $activities->links() }}
                </div>
                @endif
            @endif
        </div>

    </div>

</x-layouts.app>
