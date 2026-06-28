<x-layouts.app>
    <x-slot name="title">{{ __('Profile') }} — georeference.it</x-slot>

    <div class="max-w-3xl space-y-6">

        {{-- Header --}}
        <div class="flex items-center gap-4">
            <label for="avatar-upload" class="relative cursor-pointer group flex-shrink-0">
                <div class="w-16 h-16 rounded-full bg-green-600 flex items-center justify-center text-white text-2xl font-bold overflow-hidden">
                    @if($user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-16 h-16 object-cover">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                </div>
                <div class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                    <span class="text-white text-xs font-medium">Change</span>
                </div>
            </label>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                <div class="flex items-center gap-3 mt-1">
                    @if($user->userLevel)
                        <span class="text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-0.5 rounded-full">
                            {{ $user->userLevel->name }}
                        </span>
                    @endif
                    @if($user->orcid)
                        <a href="https://orcid.org/{{ $user->orcid }}" target="_blank" class="flex items-center gap-1 text-xs text-gray-500 hover:text-green-600">
                            <img src="https://orcid.org/sites/default/files/images/orcid_16x16.png" alt="ORCID" class="w-3 h-3">
                            {{ $user->orcid }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ number_format($user->suggestions_count) }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ __('Georefs') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ number_format($user->total_validated) }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ __('Validated') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ number_format($reviewsCount) }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ __('Reviews') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $user->userLevel?->vote_weight ?? 10 }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ __('Vote weight') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $user->created_at->format('Y') }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ __('Member since') }}</div>
            </div>
        </div>

        {{-- Status messages --}}
        @if(session('status') === 'profile-updated')
            <p class="text-sm text-green-600 font-medium">{{ __('Profile saved.') }}</p>
        @endif

        {{-- Profile information --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Profile information') }}</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4" enctype="multipart/form-data">
                @csrf @method('PATCH')

                {{-- Hidden avatar upload (triggered by clicking the avatar in the header) --}}
                <input type="file" id="avatar-upload" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden"
                    onchange="this.form.submit()">
                @error('avatar')<p class="text-red-500 text-xs mb-2">{{ $message }}</p>@enderror

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Bio') }}</label>
                    <textarea name="bio" rows="3" maxlength="500"
                        class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- ORCID: read-only if connected via OAuth, editable otherwise --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ORCID iD
                        <span class="text-xs font-normal text-gray-400">(formato: 0000-0000-0000-0000)</span>
                    </label>
                    @if($user->provider === 'orcid')
                        <div class="flex items-center gap-3">
                            <img src="https://orcid.org/sites/default/files/images/orcid_16x16.png" alt="ORCID" class="w-4 h-4">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $user->orcid }}</span>
                            <span class="text-xs text-gray-400">({{ __('connected via ORCID OAuth') }})</span>
                            <form method="POST" action="{{ route('profile.orcid.disconnect') }}"
                                onsubmit="return confirm('{{ __('Disconnect ORCID login? You can reconnect at any time, or use password recovery to set a password.') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 hover:underline">{{ __('Disconnect') }}</button>
                            </form>
                        </div>
                    @else
                        <input type="text" name="orcid" value="{{ old('orcid', $user->orcid) }}"
                            placeholder="0000-0000-0000-0000"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                        @error('orcid')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    @endif
                </div>

                {{-- Preferences --}}
                <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Preferences') }}</h3>

                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">{{ __('Preferred task') }}</label>
                        <select name="preferred_task"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="both"    @selected(old('preferred_task', $user->preferred_task) === 'both')>{{ __('Georeference & validate') }}</option>
                            <option value="georef"  @selected(old('preferred_task', $user->preferred_task) === 'georef')>{{ __('Georeference only') }}</option>
                            <option value="validate" @selected(old('preferred_task', $user->preferred_task) === 'validate')>{{ __('Validate only') }}</option>
                        </select>
                    </div>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="email_notifications" value="0">
                        <input type="checkbox" name="email_notifications" value="1"
                            @checked(old('email_notifications', $user->email_notifications))
                            class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Receive email notifications') }}</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="public_name" value="0">
                        <input type="checkbox" name="public_name" value="1"
                            @checked(old('public_name', $user->public_name))
                            class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Show my name publicly (leaderboard & activity feed)') }}</span>
                    </label>
                </div>

                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-green-700">
                    {{ __('Save') }}
                </button>
            </form>
        </div>

        {{-- Change password --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Change password') }}</h2>
            @if(session('status') === 'password-updated')
                <p class="text-sm text-green-600 font-medium mb-4">{{ __('Password updated.') }}</p>
            @endif
            @if($user->provider === 'orcid' && !$user->password)
                <p class="text-sm text-gray-500">{{ __('Your account uses ORCID login — no password is set.') }}</p>
            @else
                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Current password') }}</label>
                        <input type="password" name="current_password"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                        @error('current_password', 'updatePassword')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('New password') }}</label>
                        <input type="password" name="password"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                        @error('password', 'updatePassword')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Confirm password') }}</label>
                        <input type="password" name="password_confirmation"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-green-700">{{ __('Update password') }}</button>
                </form>
            @endif
        </div>

        {{-- Delete account --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-800 p-6">
            <h2 class="font-semibold text-red-600 mb-2">{{ __('Delete account') }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ __('Once deleted, all your data will be permanently removed.') }}</p>
            <form method="POST" action="{{ route('profile.destroy') }}"
                onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                @csrf @method('DELETE')
                <div class="flex items-center gap-3">
                    <input type="password" name="password"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="{{ __('Confirm your password') }}">
                    <button type="submit" class="bg-red-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-red-700">{{ __('Delete account') }}</button>
                </div>
                @error('password', 'userDeletion')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </form>
        </div>

    </div>
</x-layouts.app>
