<x-layouts.app>
    <x-slot name="title">{{ __('Dashboard') }} — georeference.it</x-slot>

    <div class="space-y-8">
        {{-- Welcome + level --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Welcome back,') }} {{ auth()->user()->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Level') }}: <span class="font-medium text-green-600">{{ auth()->user()->userLevel?->name ?? __('Beginner') }}</span>
                    · {{ auth()->user()->total_validated }} {{ __('validated contributions') }}
                </p>
            </div>
            <a href="{{ route('georef.index') }}" class="bg-green-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700">
                {{ __('Start georeferencing') }}
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Suggestions') }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ auth()->user()->suggestions()->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Validated') }}</p>
                <p class="text-3xl font-bold text-green-600 mt-1">{{ auth()->user()->total_validated }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Validations cast') }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ auth()->user()->validations()->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Vote weight') }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ auth()->user()->getVoteWeight() }}</p>
            </div>
        </div>

        {{-- Level progress --}}
        @if(auth()->user()->userLevel)
        @php
            $currentLevel = auth()->user()->userLevel;
            $nextLevel = \App\Models\UserLevel::where('min_validated', '>', $currentLevel->min_validated)->orderBy('min_validated')->first();
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $currentLevel->name }}</span>
                @if($nextLevel)
                    <span class="text-sm text-gray-500">{{ __('Next') }}: {{ $nextLevel->name }} ({{ $nextLevel->min_validated }} {{ __('validated') }})</span>
                @else
                    <span class="text-sm text-green-600">{{ __('Maximum level reached!') }}</span>
                @endif
            </div>
            @if($nextLevel)
            @php
                $progress = $nextLevel->min_validated > $currentLevel->min_validated
                    ? min(100, (auth()->user()->total_validated - $currentLevel->min_validated) / ($nextLevel->min_validated - $currentLevel->min_validated) * 100)
                    : 100;
            @endphp
            <div class="bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all" style="width:{{ $progress }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ auth()->user()->total_validated }} / {{ $nextLevel->min_validated }}</p>
            @endif
        </div>
        @endif

        <div class="grid md:grid-cols-2 gap-6">
            {{-- Recent suggestions --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('Recent suggestions') }}</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse(auth()->user()->suggestions()->with('occurrence')->latest()->take(5)->get() as $suggestion)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $suggestion->occurrence?->verbatim_locality ?? __('Unknown locality') }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $suggestion->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full
                            @if($suggestion->status === 'validated') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                            @elseif($suggestion->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @endif">
                            {{ $suggestion->status }}
                        </span>
                    </div>
                    @empty
                    <div class="p-5 text-sm text-gray-400 text-center">{{ __('No suggestions yet.') }} <a href="{{ route('georef.index') }}" class="text-green-600 hover:underline">{{ __('Start now!') }}</a></div>
                    @endforelse
                </div>
            </div>

            {{-- Recent validations --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('Recent validations') }}</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse(auth()->user()->validations()->with('suggestion.occurrence')->latest()->take(5)->get() as $validation)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $validation->suggestion?->occurrence?->verbatim_locality ?? __('Unknown locality') }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $validation->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full
                            @if($validation->vote === 'agree') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @endif">
                            {{ $validation->vote }}
                        </span>
                    </div>
                    @empty
                    <div class="p-5 text-sm text-gray-400 text-center">{{ __('No validations yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Preferences --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Preferences') }}</h2>
            <form action="{{ route('dashboard.preferences') }}" method="POST" class="flex flex-wrap gap-6 items-end">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Preferred task') }}</label>
                    <select name="preferred_task" class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="both" @selected(auth()->user()->preferred_task === 'both')>{{ __('Both (platform decides)') }}</option>
                        <option value="georef" @selected(auth()->user()->preferred_task === 'georef')>{{ __('Georeference only') }}</option>
                        <option value="validate" @selected(auth()->user()->preferred_task === 'validate')>{{ __('Validate only') }}</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="email_notifications" id="email_notifications" value="1" @checked(auth()->user()->email_notifications) class="rounded border-gray-300">
                    <label for="email_notifications" class="text-sm text-gray-600 dark:text-gray-400">{{ __('Email notifications') }}</label>
                </div>
                <button type="submit" class="text-sm bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">{{ __('Save') }}</button>
            </form>
        </div>
    </div>
</x-layouts.app>
