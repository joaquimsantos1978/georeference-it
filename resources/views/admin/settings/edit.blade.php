<x-layouts.app>
    <x-slot name="title">{{ __('Edit Setting') }} — georeference.it</x-slot>

    <div class="max-w-lg space-y-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Edit Setting') }}</h1>

        <form method="POST" action="{{ route('admin.settings.update', $setting) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Key') }}</label>
                <input type="text" value="{{ $setting->key }}" disabled class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 text-gray-500 font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Value') }}</label>
                <input type="text" name="value" value="{{ old('value', $setting->value) }}" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                @error('value')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Description') }}</label>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-green-700">{{ __('Save') }}</button>
                <a href="{{ route('admin.settings.index') }}" class="text-sm text-gray-500 px-5 py-2 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-layouts.app>
