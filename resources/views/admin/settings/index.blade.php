<x-layouts.app>
    <x-slot name="title">{{ __('Platform Settings') }} — georeference.it</x-slot>

    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Platform Settings') }}</h1>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Key') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Value') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Description') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($settings as $setting)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $setting->key }}</td>
                        <td class="px-5 py-3 text-gray-900 dark:text-white font-medium">{{ $setting->value }}</td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $setting->description }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.settings.edit', $setting) }}" class="text-green-600 hover:underline">{{ __('Edit') }}</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
