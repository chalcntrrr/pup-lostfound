<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use our own simple page links instead of Laravel's Tailwind ones.
        \Illuminate\Pagination\Paginator::defaultView('partials.pagination');

        // Keep a copy of items, claims and admin accounts in the Firebase Realtime Database.
        // Does nothing until FIREBASE_DATABASE_URL is set in .env (see FirebaseSync).
        \App\Models\Item::observe(\App\Observers\FirebaseObserver::class);
        \App\Models\Claim::observe(\App\Observers\FirebaseObserver::class);
        \App\Models\User::observe(\App\Observers\FirebaseObserver::class);
    }
}
