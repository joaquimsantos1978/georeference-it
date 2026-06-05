<x-layouts.app>
    <x-slot name="title">{{ __('Edit User') }} — georeference.it</x-slot>

    <div class="max-w-lg space-y-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Edit User') }}: {{ $user->name }}</h1>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }}</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Role') }}</label>
                <select name="role" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="user" @selected(old('role', $user->role) === 'user')>{{ __('User') }}</option>
                    <option value="moderator" @selected(old('role', $user->role) === 'moderator')>{{ __('Moderator') }}</option>
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>{{ __('Admin') }}</option>
                </select>
                @error('role')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Level') }}</label>
                <select name="user_level_id" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">— {{ __('None') }} —</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}" @selected(old('user_level_id', $user->user_level_id) == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
                @error('user_level_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-green-700">{{ __('Save') }}</button>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 px-5 py-2 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-layouts.app>
