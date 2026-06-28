<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GeorefController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatasetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GeorefController::class, 'index'])->name('home');

Route::get('/about', fn() => view('about'))->name('about');
Route::get('/how-it-works', function () {
    $levels = \App\Models\UserLevel::orderBy('sort_order')->get();
    $validationThreshold = (int) \App\Models\PlatformSetting::get('validation_threshold', 60);
    return view('how-it-works', compact('levels', 'validationThreshold'));
})->name('how-it-works');
Route::get('/extension', fn() => view('extension'))->name('extension');
Route::get('/privacy', fn() => view('privacy'))->name('privacy');
Route::get('/terms', fn() => view('terms'))->name('terms');
Route::get('/cite', fn() => view('cite'))->name('cite');
Route::get('/georeferencing-guide', fn() => view('georeferencing-guide'))->name('georeferencing-guide');

Route::get('/api-docs', function () {
    return view('api-docs');
})->name('api-docs');

Route::get('/datasets', [DatasetController::class, 'index'])->name('datasets');
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
Route::get('/stats', [\App\Http\Controllers\StatsController::class, 'index'])->name('stats');
Route::get('/activity', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activity');

// Georeferencing (public)
Route::get('/georef', [GeorefController::class, 'index'])->name('georef.index');
Route::get('/georef/next', [GeorefController::class, 'next'])->name('georef.next');
Route::post('/georef/submit', [GeorefController::class, 'submit'])->name('georef.submit');
Route::post('/georef/validate/{suggestion}', [GeorefController::class, 'validate'])->name('georef.validate');
Route::post('/georef/agree-with/{suggestion}', [GeorefController::class, 'agreeWith'])->name('georef.agree-with');
Route::delete('/georef/suggestion/{suggestion}', [GeorefController::class, 'destroySuggestion'])->name('georef.suggestion.destroy')->middleware('auth');
Route::delete('/georef/validation/{validation}', [GeorefController::class, 'revokeValidation'])->name('georef.validation.revoke')->middleware('auth');
Route::post('/georef/comment', [GeorefController::class, 'comment'])->name('georef.comment')->middleware('auth');

Route::get('/georef/group/{id}', [GeorefController::class, 'group'])->name('georef.group');
Route::get('/georef/group/{id}/ungeoref-occurrences', [GeorefController::class, 'groupUngeorefOccurrences'])->name('georef.group.ungeoref');
Route::get('/georef/suggestion/{suggestion}/georef-occurrences', [GeorefController::class, 'suggestionGeorefOccurrences'])->name('georef.suggestion.georef');
Route::get('/georef/occurrences-by-ids', [GeorefController::class, 'occurrencesByIds'])->name('georef.occurrences-by-ids');

Route::get('/georef/detect-location', [GeorefController::class, 'detectLocation'])->name('georef.detect-location');
Route::get('/georef/search-locality', [GeorefController::class, 'searchLocality'])->name('georef.search-locality');
Route::get('/georef/occurrence/{key}', [GeorefController::class, 'findByGbifKey'])->name('georef.occurrence');

Route::post('/georef/sync', [GeorefController::class, 'sync'])->name('georef.sync');
Route::get('/georef/iiif-proxy', [GeorefController::class, 'iiifProxy'])->name('georef.iiif-proxy');

// Auth routes
Route::get('/auth/redirect/{provider}', [App\Http\Controllers\Auth\SocialiteController::class, 'redirect'])
    ->name('auth.social.redirect');
Route::get('/auth/callback/{provider}', [App\Http\Controllers\Auth\SocialiteController::class, 'callback'])
    ->name('auth.social.callback');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::patch('/dashboard/preferences', [DashboardController::class, 'updatePreferences'])->name('dashboard.preferences');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/orcid', [ProfileController::class, 'disconnectOrcid'])->name('profile.orcid.disconnect');

    Route::get('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('user-levels', App\Http\Controllers\Admin\UserLevelController::class);
    Route::resource('settings', App\Http\Controllers\Admin\PlatformSettingController::class);
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
});

// Social auth
Route::get('/auth/{provider}/redirect', [App\Http\Controllers\Auth\SocialiteController::class, 'redirect'])->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [App\Http\Controllers\Auth\SocialiteController::class, 'callback'])->name('socialite.callback');

require __DIR__.'/auth.php';