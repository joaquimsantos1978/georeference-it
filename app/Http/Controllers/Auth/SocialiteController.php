<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
public function redirect(string $provider)
{
    if ($provider === 'orcid') {
        return Socialite::driver($provider)
            ->setScopes(['/authenticate'])
            ->redirect();
    }

    return Socialite::driver($provider)->redirect();
}

    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['social' => 'Authentication failed. Please try again.']);
        }

        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (!$user) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                $user->update([
        'provider' => $provider,
        'provider_id' => $socialUser->getId(),
        'avatar' => $socialUser->getAvatar(),
        'orcid' => $provider === 'orcid' ? $socialUser->getId() : null,
        'user_level_id' => $user->user_level_id ?? UserLevel::orderBy('min_validated', 'asc')->first()?->id,
                ]);
            } else {
                $beginnerLevel = UserLevel::orderBy('min_validated', 'asc')->first();

                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                    'email' => $socialUser->getEmail(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'user_level_id' => $beginnerLevel?->id,
                    'orcid' => $provider === 'orcid' ? $socialUser->getId() : null,
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                ]);
            }
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}