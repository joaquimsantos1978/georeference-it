<x-layouts.app title="Activity" description="Recent georeferencing activity on georeference.it.">

    <div class="space-y-6 max-w-5xl mx-auto">

        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Activity</h1>

            {{-- Filters --}}
            <form method="GET" action="{{ route('activity') }}" class="flex items-center gap-2 flex-wrap">
                <select name="user" onchange="this.form.submit()"
                    class="text-xs border border-gray-200 dark:border-gray-600 rounded-lg pl-2 pr-7 py-1.5 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 cursor-pointer">
                    <option value="">All contributors</option>
                    @foreach($publicUsers as $u)
                        <option value="{{ $u->id }}" {{ request('user') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="country" value="{{ request('country') }}" placeholder="Country (e.g. PT)"
                    maxlength="2"
                    class="text-xs border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 w-28 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase placeholder:normal-case placeholder:text-gray-400">
                <button type="submit" class="text-xs bg-green-600 hover:bg-green-700 text-white rounded-lg px-3 py-1.5 transition-colors">Filter</button>
                @if(request('user') || request('country'))
                    <a href="{{ route('activity') }}" class="text-xs text-gray-400 hover:text-gray-600 py-1.5">Clear</a>
                @endif
            </form>
        </div>

        @if($filterUser)
        <div class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-5 py-4">
            @if($filterUser->avatar)
                <img src="{{ $filterUser->avatar }}" class="w-10 h-10 rounded-full" alt="">
            @else
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center text-green-700 dark:text-green-300 font-bold text-sm">{{ substr($filterUser->name, 0, 1) }}</div>
            @endif
            <div>
                <div class="font-semibold text-gray-900 dark:text-white">{{ $filterUser->name }}</div>
                <div class="text-xs text-gray-500">{{ number_format($activities->total()) }} georeferencing events</div>
            </div>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

            @if($activities->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-gray-400">No activity found.</div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($activities as $row)
                    @php
                        $locationParts = array_filter([$row->verbatim_locality, $row->municipality, $row->county, $row->state_province]);
                        $location = implode(', ', array_slice($locationParts, 0, 2));
                        $ago = \Carbon\Carbon::parse($row->submitted_at)->diffForHumans();
                    @endphp
                    <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">

                        {{-- Avatar --}}
                        <div class="flex-shrink-0 mt-0.5">
                            @if($row->user_avatar)
                                <img src="{{ $row->user_avatar }}" class="w-8 h-8 rounded-full" alt="">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-300 text-xs font-bold">
                                    {{ $row->user_name ? strtoupper(substr($row->user_name, 0, 1)) : '?' }}
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-gray-800 dark:text-gray-200">
                                @if($row->user_name)
                                    <a href="{{ route('activity') }}?user={{ $row->public_user_id }}" class="font-semibold hover:text-green-600">{{ $row->user_name }}</a>
                                @else
                                    <span class="font-semibold text-gray-500">Anonymous</span>
                                @endif
                                @if($row->type === 'georef')
                                    georeferenced
                                @elseif($row->type === 'validation_agree')
                                    <span class="text-green-600">agreed with</span> a georef of
                                @elseif($row->type === 'validation_disagree')
                                    <span class="text-red-500">disagreed with</span> a georef of
                                @else
                                    abstained on a georef of
                                @endif
                                <a href="{{ route('georef.index') }}?group={{ $row->locality_group_id }}"
                                   class="text-green-700 dark:text-green-400 hover:underline font-medium">{{ $location ?: 'unknown locality' }}</a>
                                @if($row->country_code)
                                    <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-1.5 py-0.5 rounded ml-1">{{ strtoupper($row->country_code) }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                                <span>{{ $ago }}</span>
                                @if($row->type === 'georef')
                                    <span>{{ number_format($row->occ_count) }} {{ Str::plural('specimen', $row->occ_count) }}</span>
                                    @if($row->uncertainty_m)
                                        <span>±{{ $row->uncertainty_m >= 1000 ? round($row->uncertainty_m/1000).'km' : $row->uncertainty_m.'m' }}</span>
                                    @endif
                                    @if($row->status === 'validated')
                                        <span class="text-green-600 font-medium">Validated</span>
                                    @elseif($row->status === 'pending')
                                        <span class="text-orange-500">Pending review</span>
                                    @endif
                                    @if($row->remarks)
                                        <span class="italic truncate max-w-xs" title="{{ $row->remarks }}">"{{ Str::limit($row->remarks, 60) }}"</span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Link --}}
                        <a href="{{ route('georef.index') }}?group={{ $row->locality_group_id }}"
                           class="flex-shrink-0 text-xs text-gray-400 hover:text-green-600 transition-colors mt-1" title="Open in georef">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>

                    </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($activities->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $activities->links() }}
                </div>
                @endif
            @endif
        </div>

    </div>

</x-layouts.app>
