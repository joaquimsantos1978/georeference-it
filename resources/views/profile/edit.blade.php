<x-layouts.app>
    <x-slot name="title">{{ __('Profile') }} — georeference.it</x-slot>

    <div class="max-w-2xl space-y-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Profile') }}</h1>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Profile information') }}</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf @method('PATCH')
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
                @if($user->orcid)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('ORCID') }}</label>
                    <a href="https://orcid.org/{{ $user->orcid }}" target="_blank" class="text-sm text-green-600 hover:underline">{{ $user->orcid }}</a>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Bio') }}</label>
                    <textarea name="bio" rows="3" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('bio', $user->bio) }}</textarea>
                </div>
                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-green-700">{{ __('Save') }}</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Change password') }}</h2>
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Current password') }}</label>
                    <input type="password" name="current_password" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                    @error('current_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('New password') }}</label>
                    <input type="password" name="password" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Confirm password') }}</label>
                    <input type="password" name="password_confirmation" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-green-700">{{ __('Update password') }}</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-800 p-6">
            <h2 class="font-semibold text-red-600 mb-4">{{ __('Delete account') }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ __('Once deleted, all your data will be permanently removed.') }}</p>
            <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                @csrf @method('DELETE')
                <input type="password" name="password" class="border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-red-500 mr-3" placeholder="{{ __('Confirm your password') }}">
                <button type="submit" class="bg-red-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-red-700">{{ __('Delete account') }}</button>
            </form>
        </div>
    </div>
</x-layouts.app>
