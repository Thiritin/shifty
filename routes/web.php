<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\BrandAssetController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::get('brand/{name}', BrandAssetController::class)->name('brand.asset');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ShiftController::class, 'dashboard'])->name('dashboard');

    // Shift routes for regular users
    Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('shifts/{shift}/assign', [ShiftController::class, 'assign'])->name('shifts.assign');
    Route::delete('shifts/{shift}/unassign', [ShiftController::class, 'unassign'])->name('shifts.unassign');
    Route::get('shifts/print', [ShiftController::class, 'print'])->name('shifts.print');
    Route::get('shifts/calendar', [ShiftController::class, 'calendar'])->name('shifts.calendar');

    // Admin routes (integrated into main shifts controller for inline editing)
    Route::middleware(['admin'])->group(function () {
        Route::post('shifts', [ShiftController::class, 'store'])->name('admin.shifts.store');
        Route::put('shifts/{shift}', [ShiftController::class, 'update'])->name('admin.shifts.update');
        Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->name('admin.shifts.destroy');
        Route::post('shifts/{shift}/assign/{user}', [ShiftController::class, 'adminAssign'])->name('admin.shifts.assign');
        Route::delete('shifts/{shift}/unassign/{user}', [ShiftController::class, 'adminUnassign'])->name('admin.shifts.unassign');

        Route::get('admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::put('admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');

        Route::get('admin/settings', [SettingsController::class, 'edit'])->name('admin.settings.edit');
        Route::post('admin/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
        Route::post('admin/settings/test-mail', [SettingsController::class, 'testMail'])->name('admin.settings.test-mail');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
