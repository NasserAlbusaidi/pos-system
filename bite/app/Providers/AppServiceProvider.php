<?php

namespace App\Providers;

use App\Models\Shop;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production') || env('FORCE_HTTPS')) {
            URL::forceScheme('https');
        }

        // Use Shop (not User) as the billable model for Laravel Cashier.
        // Shops are the subscription entity — each shop has its own plan.
        if (class_exists(\Laravel\Cashier\Cashier::class)) {
            \Laravel\Cashier\Cashier::useCustomerModel(Shop::class);
        }
    }
}
