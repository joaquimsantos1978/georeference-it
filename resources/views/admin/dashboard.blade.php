<x-layouts.app>
    <x-slot name="title">{{ __('Admin Dashboard') }} — georeference.it</x-slot>

    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Admin Dashboard') }}</h1>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total occurrences') }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_occurrences']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Ungeoreferenced') }}</p>
                <p class="text-3xl font-bold text-yellow-600 mt-1">{{ number_format($stats['ungeoreferenced']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Has suggestion') }}</p>
                <p class="text-3xl font-bold text-blue-600 mt-1">{{ number_format($stats['has_suggestion']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Validated') }}</p>
                <p class="text-3xl font-bold text-green-600 mt-1">{{ number_format($stats['validated']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Users') }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_users']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Suggestions') }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_suggestions']) }}</p>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <a href="{{ route('admin.user-levels.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-green-400 transition-colors">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('User Levels') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('Manage experience levels and vote weights') }}</p>
            </a>
            <a href="{{ route('admin.settings.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-green-400 transition-colors">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Platform Settings') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('Validation threshold, sync settings, etc.') }}</p>
            </a>
            <a href="{{ route('admin.users.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-green-400 transition-colors">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Users') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('Manage user accounts and roles') }}</p>
            </a>
        </div>
    </div>
</x-layouts.app>
