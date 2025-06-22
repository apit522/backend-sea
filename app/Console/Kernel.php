<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Services\DeviceDataAggregator;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('app:aggregate-hourly-data')->hourly();

        $schedule->command('app:process-summaries')->dailyAt('01:00');

        // Agregasi harian setiap jam 1 pagi untuk hari sebelumnya
        $schedule->call(function () {
            app(DeviceDataAggregator::class)->aggregateDailyData(now()->subDay());
        })->dailyAt('01:00');

        // Agregasi bulanan pada hari pertama setiap bulan
        $schedule->call(function () {
            $date = now()->subMonth();
            app(DeviceDataAggregator::class)->aggregateMonthlyData(
                $date->year,
                $date->month
            );
        })->monthlyOn(1, '02:00');
    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
