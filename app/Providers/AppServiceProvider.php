<?php

namespace App\Providers;

use App\Contracts\BiwengerApiInterface;
use App\Services\BiwengerApiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BiwengerApiInterface::class, BiwengerApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
