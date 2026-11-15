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
    }
}
