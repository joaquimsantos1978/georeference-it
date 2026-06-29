<nav class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 h-12 flex items-center z-30 sticky top-0">
    <div class="w-full px-4 flex items-center justify-between h-full">
        <div class="flex items-center gap-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 hover:opacity-80">
                <img src="{{ asset('images/logo.png') }}" alt="georeference.it" class="h-7 w-auto">
                <span class="text-base font-bold text-green-600 dark:text-green-400 tracking-tight">georeference.it</span>
            </a>
            <div class="hidden sm:flex items-center gap-5">
                {{-- App --}}
                <a href="{{ route('georef.index') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">{{ __('Georeference') }}</a>
                <a href="{{ route('explore') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">{{ __('Explore') }}</a>
                <a href="{{ route('datasets') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">{{ __('Datasets') }}</a>
                {{-- Community --}}
                <span class="text-gray-200 dark:text-gray-700 select-none">|</span>
                <a href="{{ route('stats') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">{{ __('Stats') }}</a>
                <a href="{{ route('impact') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">{{ __('Impact') }}</a>
                <a href="{{ route('leaderboard') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">{{ __('Leaderboard') }}</a>
                <a href="{{ route('activity') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">{{ __('Activity') }}</a>
                {{-- Info --}}
                <span class="text-gray-200 dark:text-gray-700 select-none">|</span>
                <a href="{{ route('about') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">{{ __('About') }}</a>
                <a href="{{ route('api-docs') }}" class="text-sm text-gray-400 dark:text-gray-500 hover:text-green-600 dark:hover:text-green-400 font-mono">API</a>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @auth
                {{-- Notifications --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="relative p-1 text-gray-500 dark:text-gray-400 hover:text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if(auth()->user()->unreadNotifications()->count() > 0)
                            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500"></span>
                        @endif
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <span class="text-sm font-medium">{{ __('Notifications') }}</span>
                            <a href="{{ route('notifications.read-all') }}" class="text-xs text-green-600 hover:underline">{{ __('Mark all as read') }}</a>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            @forelse(auth()->user()->unreadNotifications()->latest()->take(10)->get() as $notification)
                                <div class="p-3 border-b border-gray-100 dark:border-gray-700 bg-green-50 dark:bg-green-900/20">
                                    <p class="text-sm">{{ $notification->data['message'] ?? '' }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            @empty
                                <div class="p-4 text-sm text-gray-400 text-center">{{ __('No new notifications') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- User menu --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-green-600">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" class="h-7 w-7 rounded-full">
                        @else
                            <div class="h-7 w-7 rounded-full bg-green-600 flex items-center justify-center text-white text-xs font-bold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                        <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Dashboard') }}</a>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Profile') }}</a>
                        @if(auth()->user()->isAdmin())
                            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Admin') }}</a>
                            <a href="{{ route('admin.user-levels.index') }}" class="block px-4 py-2 text-sm pl-6 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('User Levels') }}</a>
                            <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm pl-6 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Settings') }}</a>
                            <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm pl-6 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Users') }}</a>
                        @endif
                        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Logout') }}</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-green-600">{{ __('Login') }}</a>
                <a href="{{ route('register') }}" class="text-sm bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700">{{ __('Register') }}</a>
            @endauth

            {{-- Mobile menu toggle --}}
            <div class="sm:hidden" x-data="{ open: false }">
                <button @click="open = !open" class="p-1 text-gray-500 hover:text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak
                     class="absolute right-0 top-12 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                    <a href="{{ route('georef.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Georeference') }}</a>
                    <a href="{{ route('explore') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Explore') }}</a>
                    <a href="{{ route('datasets') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Datasets') }}</a>
                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                    <a href="{{ route('stats') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Stats') }}</a>
                    <a href="{{ route('impact') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Impact') }}</a>
                    <a href="{{ route('leaderboard') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Leaderboard') }}</a>
                    <a href="{{ route('activity') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Activity') }}</a>
                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                    <a href="{{ route('about') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('About') }}</a>
                    <a href="{{ route('api-docs') }}" class="block px-4 py-2 text-sm font-mono text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">API</a>
                </div>
            </div>
        </div>
    </div>
</nav>
