<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\SocialLoginController;
use Illuminate\Support\Facades\Route;

Auth::routes(['verify' => true]);

Route::get('/login/google', [SocialLoginController::class, 'login'])->defaults('provider', 'google')->middleware(['guest', 'throttle:20,1'])->name('login.google');

Route::get('/login/{provider}', [SocialLoginController::class, 'login'])->whereIn('provider', ['linkedin', 'github'])->middleware(['guest', 'throttle:20,1'])->name('login.social');

Route::get('/login/{provider}/callback', [SocialLoginController::class, 'callback'])->whereIn('provider', ['google', 'linkedin', 'github'])->middleware('throttle:20,1')->name('login.social.callback');

Route::post('/account/connections/{provider}', [SocialLoginController::class, 'connect'])->whereIn('provider', ['google', 'linkedin', 'github'])->middleware(['auth', 'throttle:10,1'])->name('account.connections');

Route::middleware('auth')->group(function () {
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::patch('/account/settings', [AccountController::class, 'update'])->middleware('throttle:10,1,account-details')->name('account.update');
    Route::put('/account/password', [AccountController::class, 'password'])->middleware('throttle:5,1,account-password')->name('account.password');
});
