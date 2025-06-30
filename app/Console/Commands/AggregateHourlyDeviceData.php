<?php

namespace App\Console\Commands;

use App\Services\DeviceDataAggregator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateHourlyDeviceData extends Command
{
    /**
     * Tanda tangan dan nama command konsol.
     * Saya menambahkan argumen {date?} agar Anda bisa menjalankan agregasi untuk tanggal tertentu.
     * Contoh: php artisan app:aggregate-hourly-data 2023-10-27
     * Jika tanggal tidak diberikan, akan menggunakan tanggal hari ini.
     *
     * @var string
     */
    protected $signature = 'app:aggregate-hourly-data {date?}';

    /**
     * Deskripsi dari command konsol.
     *
     * @var string
     */
    protected $description = 'Aggregates raw device data into hourly summaries using the accurate calculation method.';

    /**
     * Instance dari service aggregator.
     *
     * @var DeviceDataAggregator
     */
    protected $aggregator;

    /**
     * Buat instance command baru.
     * Laravel akan otomatis meng-inject DeviceDataAggregator berkat service container.
     *
     * @param DeviceDataAggregator $aggregator
     */
    public function __construct(DeviceDataAggregator $aggregator)
    {
        parent::__construct();
        $this->aggregator = $aggregator;
    }

    /**
     * Jalankan logic command.
     *
     * @return int
     */
    public function handle()
    {
        // Ambil argumen tanggal dari command, jika tidak ada, gunakan hari ini
        $dateArgument = $this->argument('date');
        $dateToProcess = $dateArgument ? Carbon::parse($dateArgument) : Carbon::today();

        $this->info('Starting hourly device data aggregation for: ' . $dateToProcess->toDateString());

        // Panggil metode baru di service untuk melakukan pekerjaan berat
        $this->aggregator->aggregateHourlyData($dateToProcess);

        $this->info('Hourly device data aggregation completed successfully!');

        return self::SUCCESS;
    }
}
