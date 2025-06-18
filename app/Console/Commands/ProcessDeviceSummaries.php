<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DeviceDataAggregator; // Import service Anda
use Carbon\Carbon;

class ProcessDeviceSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Update signature untuk menerima opsi bulan dan tahun
    protected $signature = 'app:process-summaries {--date=} {--month=} {--year=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process raw device data into daily and monthly summaries';

    /**
     * Execute the console command.
     */
    public function handle(DeviceDataAggregator $aggregator)
    {
        $this->info('Starting data summary processing...');

        $dateOption = $this->option('date');
        $monthOption = $this->option('month');
        $yearOption = $this->option('year');

        // --- Logika Baru untuk Menangani Opsi yang Berbeda ---

        // Kasus 1: Jika --month dan --year diberikan (untuk tes bulanan)
        if ($monthOption && $yearOption) {
            $this->info("Processing monthly summary for: $yearOption-$monthOption");
            $aggregator->aggregateMonthlyData((int) $yearOption, (int) $monthOption);

            // Kasus 2: Jika --date diberikan (untuk tes harian)
        } elseif ($dateOption) {
            $dateToProcess = Carbon::parse($dateOption);
            $this->info("Processing daily summary for: " . $dateToProcess->toDateString());
            $aggregator->aggregateDailyData($dateToProcess);

            // Kasus 3: Default (jika tidak ada opsi, untuk scheduler)
        } else {
            $this->info("Running default scheduled task...");
            // Proses ringkasan untuk hari kemarin
            $yesterday = now()->subDay();
            $this->info("Processing daily summary for: " . $yesterday->toDateString());
            $aggregator->aggregateDailyData($yesterday);

            // Jika hari ini tanggal 1, proses juga ringkasan bulan lalu
            if (today()->day == 1) {
                $lastMonth = now()->subMonth();
                $this->info("Processing monthly summary for: " . $lastMonth->format('Y-m'));
                $aggregator->aggregateMonthlyData($lastMonth->year, $lastMonth->month);
            }
        }

        $this->info('Data summary processing completed!');
        return 0;
    }
}