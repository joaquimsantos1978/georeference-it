<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GeorefController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GeorefController::class, 'index'])->name('home');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

// Georeferencing (public)
Route::get('/georef', [GeorefController::class, 'index'])->name('georef.index');
Route::get('/georef/next', [GeorefController::class, 'next'])->name('georef.next');
Route::post('/georef/submit', [GeorefController::class, 'submit'])->name('georef.submit');
Route::post('/georef/validate/{suggestion}', [GeorefController::class, 'validate'])->name('georef.validate');
Route::post('/georef/agree-with/{suggestion}', [GeorefController::class, 'agreeWith'])->name('georef.agree-with');
Route::post('/georef/comment', [GeorefController::class, 'comment'])->name('georef.comment')->middleware('auth');

Route::get('/georef/group/{id}', [GeorefController::class, 'group'])->name('georef.group');

Route::get('/georef/detect-location', [GeorefController::class, 'detectLocation'])->name('georef.detect-location');
Route::get('/georef/search-locality', [GeorefController::class, 'searchLocality'])->name('georef.search-locality');

Route::post('/georef/sync', [GeorefController::class, 'sync'])->name('georef.sync');

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

    Route::get('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('user-levels', App\Http\Controllers\Admin\UserLevelController::class);
    Route::resource('settings', App\Http\Controllers\Admin\PlatformSettingController::class);
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
});

require __DIR__.'/auth.php';