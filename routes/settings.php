<?php

use App\Http\Controllers\Settings\AvailabilityController;
use App\Http\Controllers\Settings\PasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/availability');

    Route::get('settings/availability', [AvailabilityController::class, 'edit'])->name('availability.edit');
    Route::patch('settings/availability', [AvailabilityController::class, 'update'])->name('availability.update');
    Route::put('settings/availability/hours', [AvailabilityController::class, 'updateHours'])->name('availability.hours');
    Route::post('settings/availability/intro', [AvailabilityController::class, 'completeIntro'])->name('availability.intro');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});
