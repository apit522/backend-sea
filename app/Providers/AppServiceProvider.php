<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\DeviceData;
use App\Observers\DeviceDataObserver;

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
        DeviceData::observe(DeviceDataObserver::class);
    }
}
