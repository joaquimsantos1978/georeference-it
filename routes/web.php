<?php
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/auth/redirect/{provider}', [App\Http\Controllers\Auth\SocialiteController::class, 'redirect'])
    ->name('auth.social.redirect');

Route::get('/auth/callback/{provider}', [App\Http\Controllers\Auth\SocialiteController::class, 'callback'])
    ->name('auth.social.callback');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('user-levels', App\Http\Controllers\Admin\UserLevelController::class);
    Route::resource('settings', App\Http\Controllers\Admin\PlatformSettingController::class);
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
});

require __DIR__.'/auth.php';