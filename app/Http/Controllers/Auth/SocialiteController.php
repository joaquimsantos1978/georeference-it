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

        // Someone is logged in, connected a provider identity that turns out to already
        // belong to a DIFFERENT account — without this check they'd silently end up logged
        // into that other account at the end of this method (Auth::login($user) below).
        if ($user && Auth::check() && Auth::id() !== $user->id) {
            return redirect()->route('profile.edit')
                ->withErrors(['social' => 'This ' . ucfirst($provider) . ' account is already connected to a different georeference.it account.']);
        }

        // Already logged in and connecting a provider from the profile page (e.g. a user
        // who registered with email+password wants to add ORCID login): link to THIS
        // account rather than trying to match by email, which doesn't work at all when
        // the provider doesn't expose one (ORCID routinely doesn't) — that previously left
        // such users stuck with no way to connect ORCID to their existing account, only
        // ever hitting the "create a new account" path below.
        if (!$user && Auth::check()) {
            $user = Auth::user();
            $user->update([
                'provider'    => $provider,
                'provider_id' => $socialUser->getId(),
                'orcid'       => $provider === 'orcid' ? $socialUser->getId() : $user->orcid,
            ]);

            return redirect()->route('profile.edit')->with('status', 'profile-updated');
        }

        if (!$user) {
            // ORCID frequently doesn't expose a public email — getEmail() is then null, and
            // `where('email', null)` never matches anything in SQL (not even NULL rows), so
            // this always fell through to create() with 'email' => null, violating the
            // column's NOT NULL constraint and blocking registration entirely (observed
            // repeatedly failing for real users since 2026-06-30). Only look up by email when
            // one was actually provided; fall back to a stable placeholder derived from the
            // provider + provider_id when creating a new account without one, so the insert
            // always has a valid, unique value — the user can set a real email afterwards via
            // their profile.
            $email = $socialUser->getEmail();
            $user  = $email ? User::where('email', $email)->first() : null;

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
                    'email' => $email ?: "{$provider}-{$socialUser->getId()}@no-email.georeference.it",
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