<?php

use App\Http\Controllers\Settings\AvailabilityController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/availability');

    Route::get('settings/availability', [AvailabilityController::class, 'edit'])->name('availability.edit');
    Route::patch('settings/availability', [AvailabilityController::class, 'update'])->name('availability.update');
    Route::post('settings/availability/intro', [AvailabilityController::class, 'completeIntro'])->name('availability.intro');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
});
