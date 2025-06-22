<?php
// app/Console/Commands/AggregateHourlyDeviceData.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AggregateHourlyDeviceData extends Command
{
    protected $signature = 'app:aggregate-hourly-data';
    protected $description = 'Aggregates raw device data into hourly summaries including cost.';

    public function handle()
    {
        $this->info('Starting hourly device data aggregation...');

        $startTime = Carbon::now()->startOfDay();
        $endTime = Carbon::now();
        // $endTime = Carbon::now()->startOfHour();
        // $startTime = $endTime->copy()->subHour();

        // Query yang diupdate dengan JOIN ke tabel devices
        $query = "
            INSERT INTO device_data_hourly (
                device_id, hour_timestamp, 
                watt_avg, voltage_avg, current_avg, temperature_avg, kwh_total, cost_total
            )
            SELECT 
                dd.device_id,
                ? as hour_timestamp,
                AVG(dd.watt) as watt_avg,
                AVG(dd.voltage) as voltage_avg,
                AVG(dd.current) as current_avg,
                AVG(dd.temperature) as temperature_avg,
                -- Menghitung total kWh
                (AVG(dd.watt) * 1) / 1000 as kwh_total,
                -- Menghitung total biaya: (total kWh * tarif per kWh dari device)
                ((AVG(dd.watt) * 1) / 1000) * d.tarif_per_kwh as cost_total
            FROM 
                device_data as dd
            JOIN 
                devices as d ON dd.device_id = d.id
            WHERE 
                dd.timestamp >= ? AND dd.timestamp < ?
            GROUP BY 
                dd.device_id, d.tarif_per_kwh
            ON DUPLICATE KEY UPDATE
                watt_avg = VALUES(watt_avg),
                voltage_avg = VALUES(voltage_avg),
                current_avg = VALUES(current_avg),
                temperature_avg = VALUES(temperature_avg),
                kwh_total = VALUES(kwh_total),
                cost_total = VALUES(cost_total)
        ";

        DB::statement($query, [$startTime, $startTime, $endTime]);

        $this->info('Hourly device data aggregation completed successfully!');
        return 0;
    }
}