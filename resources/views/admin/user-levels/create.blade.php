<x-layouts.app>
    <x-slot name="title">{{ __('Create Level') }} — georeference.it</x-slot>

    <div class="max-w-lg space-y-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Create Level') }}</h1>

        <form method="POST" action="{{ route('admin.user-levels.store') }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }}</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Minimum validated contributions') }}</label>
                <input type="number" name="min_validated" value="{{ old('min_validated', 0) }}" min="0" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                @error('min_validated')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Vote weight') }}</label>
                <input type="number" name="vote_weight" value="{{ old('vote_weight', 10) }}" min="1" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                @error('vote_weight')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Sort order') }}</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                @error('sort_order')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-green-700">{{ __('Create') }}</button>
                <a href="{{ route('admin.user-levels.index') }}" class="text-sm text-gray-500 px-5 py-2 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-layouts.app>
