<x-layouts.app>
    <x-slot name="title">{{ __('User Levels') }} — georeference.it</x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('User Levels') }}</h1>
            <a href="{{ route('admin.user-levels.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">{{ __('Add level') }}</a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Name') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Min. validated') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Vote weight') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Users') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($levels as $level)
                    <tr>
                        <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $level->name }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $level->min_validated }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $level->vote_weight }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $level->users()->count() }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.user-levels.edit', $level) }}" class="text-green-600 hover:underline mr-3">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.user-levels.destroy', $level) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:underline">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
