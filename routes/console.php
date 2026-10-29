<?php

use App\Http\Controllers\InstallController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('shifty:admin {email} {--name=}', function (string $email) {
    $entered = $this->secret('Password (leave empty to generate one)');
    $password = $entered ?: Str::password(16, symbols: false);

    $user = User::firstOrNew(['email' => $email]);
    $user->name = $this->option('name') ?: ($user->name ?: Str::before($email, '@'));
    $user->is_admin = true;
    $user->password = $password;
    $user->save();

    Setting::put(['shifty.password_login' => true, 'shifty.installed' => true]);

    $this->info("{$email} is an admin and password login is enabled.");
    if (! $entered) {
        $this->line("Password: {$password}");
    }
})->purpose('Create or promote an admin and set their password');

Artisan::command('shifty:setup-token', function () {
    Setting::apply();

    if (config('shifty.installed')) {
        return;
    }

    $this->line('Setup token: '.InstallController::token());
})->purpose('Print the token the web installer asks for');
