<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->loadCount('suggestions');
        $reviewsCount = \Illuminate\Support\Facades\DB::table('georef_validations as gv')
            ->join('georef_suggestions as gs', 'gs.id', '=', 'gv.suggestion_id')
            ->where('gv.user_id', $user->id)
            ->where(fn($q) => $q->whereNull('gs.user_id')->orWhereColumn('gs.user_id', '!=', 'gv.user_id'))
            ->count();
        return view('profile.edit', compact('user', 'reviewsCount'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill(collect($request->validated())->except('avatar')->all());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }


        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048']]);

        $user = $request->user();

        if ($user->avatar && str_starts_with($user->avatar, '/storage/avatars/')) {
            Storage::disk('public')->delete('avatars/' . basename($user->avatar));
        }

        $file   = $request->file('avatar');
        $source = imagecreatefromstring(file_get_contents($file->getRealPath()));
        [$w, $h] = getimagesize($file->getRealPath());

        $size   = min($w, $h);
        $x      = (int)(($w - $size) / 2);
        $y      = (int)(($h - $size) / 2);
        $canvas = imagecreatetruecolor(200, 200);
        imagecopyresampled($canvas, $source, 0, 0, $x, $y, 200, 200, $size, $size);
        imagedestroy($source);

        $filename = 'avatars/' . $user->id . '_' . time() . '.jpg';
        ob_start();
        imagejpeg($canvas, null, 85);
        $data = ob_get_clean();
        imagedestroy($canvas);

        Storage::disk('public')->put($filename, $data);
        $user->avatar = '/storage/' . $filename;
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->avatar && str_starts_with($user->avatar, '/storage/avatars/')) {
            Storage::disk('public')->delete('avatars/' . basename($user->avatar));
        }
        $user->avatar = null;
        $user->save();
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function disconnectOrcid(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Only disconnect OAuth link — keep orcid field so badge still shows
        $user->provider    = null;
        $user->provider_id = null;
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
