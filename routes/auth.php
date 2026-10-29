<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::post('login/debug', [AuthController::class, 'debugLogin'])->name('login.debug');

    Route::get('auth/login', [AuthController::class, 'redirectToProvider'])->name('auth.login');
    Route::get('auth/callback', [AuthController::class, 'callback'])->name('auth.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
