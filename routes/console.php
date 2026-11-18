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

/*
 * Copy EVERYTHING that is already in MySQL (items, claims, admin accounts) to the Firebase Realtime Database.
 * Run it once after setting up Firebase (new changes are copied automatically), or any time Firebase got out of sync:
 *   php artisan firebase:sync            add / update everything
 *   php artisan firebase:sync --clear    empty items, claims and users in Firebase first, then copy
 */
Artisan::command('firebase:sync {--clear : Empty items, claims and users in Firebase first}', function () {
    if (! \App\Services\FirebaseSync::enabled()) {
        $this->error('Firebase sync is off. Check FIREBASE_DATABASE_URL in .env (and that FIREBASE_SYNC is not false), then run: php artisan config:clear');
        return 1;
    }

    if ($this->option('clear')) {
        foreach (['items', 'claims', 'users'] as $node) {
            \App\Services\FirebaseSync::put($node, null);
        }
        $this->line('Cleared items, claims and users in Firebase.');
    }

    $failed = 0;
    $groups = [
        'items' => \App\Models\Item::with('category')->cursor(),
        'claims' => \App\Models\Claim::with('user')->cursor(),
        'users' => \App\Models\User::cursor(),
    ];

    foreach ($groups as $name => $records) {
        $count = 0;
        foreach ($records as $record) {
            \App\Services\FirebaseSync::sync($record) ? $count++ : $failed++;
        }
        $this->info(ucfirst($name) . ": {$count} synced");
    }

    if ($failed) {
        $this->error("{$failed} record(s) could not be sent. See storage/logs/laravel.log");
        return 1;
    }

    $this->info('Done. Open Firebase console > Realtime Database > Data.');
})->purpose('Copy items, claims and admin accounts to the Firebase Realtime Database');
