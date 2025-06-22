<?php

namespace App\Providers;
use App\Models\DeviceData;
use App\Observers\DeviceDataObserver;

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
        DeviceData::observe(DeviceDataObserver::class);
    }

}
