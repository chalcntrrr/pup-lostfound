<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Password recovery from the XAMPP Shell (e.g. when both Super Admins forgot their passwords):
 *   php artisan lf:reset-password admin@pup.edu.ph
 */
Artisan::command('lf:reset-password {email} {password?}', function (string $email, ?string $password = null) {
    $user = \App\Models\User::where('email', $email)->first();
    if (! $user) {
        $this->error("No account with the email {$email}.");
        return 1;
    }

    $password ??= $this->secret('New password (at least 6 characters)');
    if (strlen((string) $password) < 6) {
        $this->error('Password must be at least 6 characters.');
        return 1;
    }

    $user->update(['password' => $password]);
    \App\Models\ActivityLog::record('Reset password (command line)', "{$user->name} ({$user->email})", $user);
    $this->info("Password updated for {$user->name} ({$user->roleLabel()}).");
})->purpose('Set a new password for an account (recovery)');
