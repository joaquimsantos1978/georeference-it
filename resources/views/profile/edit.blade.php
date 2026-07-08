<x-layouts.app>
    <x-slot name="title">{{ __('Profile') }} — georeference.it</x-slot>

    <div class="max-w-3xl space-y-6">

        {{-- Header --}}
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 flex flex-col items-center gap-1.5">
                <div class="w-16 h-16 rounded-full bg-green-600 flex items-center justify-center text-white text-2xl font-bold overflow-hidden">
                    @if($user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-16 h-16 object-cover">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                </div>
                {{-- Dedicated avatar upload form --}}
                <form id="avatar-form" method="POST" action="{{ route('profile.avatar.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" id="avatar-upload" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden"
                        onchange="document.getElementById('avatar-form').submit()">
                    <label for="avatar-upload" class="text-xs text-green-600 hover:text-green-700 cursor-pointer font-medium">
                        {{ $user->avatar ? __('Change photo') : __('Upload photo') }}
                    </label>
                </form>
                @error('avatar')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
                @if($user->avatar)
                    <form method="POST" action="{{ route('profile.avatar.remove') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-gray-400 hover:text-red-500">{{ __('Remove') }}</button>
                    </form>
                @endif
            </div>
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
        @error('social')<p class="text-sm text-red-600 font-medium">{{ $message }}</p>@enderror

        {{-- Placeholder email: assigned automatically when someone registers via ORCID and
             their ORCID doesn't expose a public email (SocialiteController) — not usable for
             notifications or password recovery, so keep nudging until they set a real one. --}}
        @if(str_ends_with($user->email, '@no-email.georeference.it'))
            <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                {{ __('Your ORCID account didn\'t provide an email, so a placeholder was used. Please set a real email below so you can receive notifications and recover your account if needed.') }}
            </div>
        @endif

        {{-- Profile information --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Profile information') }}</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4" enctype="multipart/form-data">
                @csrf @method('PATCH')


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

                {{-- ORCID: only ever set via the real OAuth connection, never free text — a
                     typed-in value would be an unverified claim with no proof of ownership,
                     defeating the point of showing it as a "verified researcher" badge. --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ORCID iD
                    </label>
                    @if($user->provider === 'orcid')
                        <div class="flex items-center gap-3">
                            <img src="https://orcid.org/sites/default/files/images/orcid_16x16.png" alt="ORCID" class="w-4 h-4">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $user->orcid }}</span>
                            <span class="text-xs text-gray-400">({{ __('connected via ORCID OAuth') }})</span>
                            {{-- Deliberately NOT a <form> here: this sits inside the "Profile information"
                                 form above, and nested <form> elements are invalid HTML — the browser's
                                 parser silently drops the inner <form> tag (keeping its child inputs/button,
                                 but with no form of its own), so the button ends up submitting the OUTER
                                 profile-update form instead. Confirmed live: no request to /profile/orcid
                                 ever reached the server, no console error, because there was no bug in the
                                 JS — there was simply no inner form to submit. Built dynamically instead,
                                 appended to <body> (a valid, non-nested location) at click time. --}}
                            <button type="button" id="orcid-disconnect-btn"
                                data-disconnect-url="{{ route('profile.orcid.disconnect') }}"
                                data-csrf-token="{{ csrf_token() }}"
                                class="text-xs text-red-500 hover:text-red-700 hover:underline">{{ __('Disconnect') }}</button>
                        </div>
                    @else
                        <a href="{{ route('auth.social.redirect', 'orcid') }}" class="inline-flex items-center gap-1 text-xs text-green-600 hover:text-green-700 hover:underline">
                            <img src="https://orcid.org/sites/default/files/images/orcid_16x16.png" alt="ORCID" class="w-3 h-3">
                            {{ __('Connect your ORCID account') }}
                        </a>
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

    @push('scripts')
    <script>
        // Not using a native confirm() here — same-page two-click confirmation instead
        // (also sidesteps a separate issue where some environment was silently swallowing
        // confirm() with no dialog, no error). The button itself is deliberately not inside
        // a <form> — see the HTML comment above it for why a nested one never worked.
        (function () {
            var btn = document.getElementById('orcid-disconnect-btn');
            if (!btn) return;
            var originalText = btn.textContent;
            var confirming = false;
            var resetTimer = null;

            btn.addEventListener('click', function () {
                if (!confirming) {
                    confirming = true;
                    btn.textContent = {!! json_encode(__('Click again to confirm')) !!};
                    resetTimer = setTimeout(function () {
                        confirming = false;
                        btn.textContent = originalText;
                    }, 4000);
                    return;
                }
                clearTimeout(resetTimer);

                var form = document.createElement('form');
                form.method = 'POST';
                form.action = btn.dataset.disconnectUrl;
                form.style.display = 'none';

                var tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = btn.dataset.csrfToken;
                form.appendChild(tokenInput);

                var methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                document.body.appendChild(form);
                form.submit();
            });
        })();
    </script>
    @endpush
</x-layouts.app>
