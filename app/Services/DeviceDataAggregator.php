<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceDailySummary;
use App\Models\DeviceDataHourly;
use App\Models\DeviceMonthlySummary;
use App\Models\DeviceTrendingData;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DeviceDataAggregator
{
    /**
     * Agregasi data mentah menjadi ringkasan per jam.
     * Logika ini meniru aggregateDailyData untuk akurasi.
     *
     * @param Carbon $date Tanggal yang akan diproses.
     * @return void
     */
    public function aggregateHourlyData(Carbon $date): void
    {
        $devices = Device::all();

        foreach ($devices as $device) {
            // Iterasi untuk setiap jam dalam satu hari (0-23)
            for ($hour = 0; $hour < 24; $hour++) {
                // Tentukan waktu mulai dan akhir untuk setiap jam
                $startOfHour = $date->copy()->startOfDay()->hour($hour);
                $endOfHour = $startOfHour->copy()->addHour();

                // 1. Ambil semua data mentah untuk perangkat dan jam ini
                $rawData = $device->data()
                    ->where('timestamp', '>=', $startOfHour)
                    ->where('timestamp', '<', $endOfHour)
                    ->orderBy('timestamp', 'asc')
                    ->get();

                // Jika tidak ada data atau kurang dari 2 titik, lewati jam ini
                if ($rawData->count() < 2) {
                    continue;
                }

                // 2. Lakukan perhitungan kWh yang akurat menggunakan metode yang sama dengan harian
                $kwh = $this->calculateKwhFromRawData($rawData);
                $cost = $kwh * ($device->tarif_per_kwh ?? 0); // Hitung biaya

                // 3. Simpan ringkasan ke database per jam
                DeviceDataHourly::updateOrCreate(
                    [
                        'device_id' => $device->id,
                        'hour_timestamp' => $startOfHour,
                    ],
                    [
                        'samples_count' => $rawData->count(),
                        'watt_avg' => $rawData->avg('watt'),
                        'voltage_avg' => $rawData->avg('voltage'),
                        'current_avg' => $rawData->avg('current'),
                        'temperature_avg' => $rawData->avg('temperature'),
                        'kwh_total' => $kwh,
                        'cost_total' => $cost,
                    ]
                );
            }
        }
    }


    public function aggregateDailyData(Carbon $date): void
    {
        // Ambil semua perangkat yang aktif atau relevan
        $devices = Device::all();

        foreach ($devices as $device) {
            // 1. Ambil semua data mentah untuk perangkat dan tanggal ini
            $rawData = $device->data()
                ->whereDate('timestamp', $date)
                ->orderBy('timestamp', 'asc')
                ->get();

            // Jika tidak ada data atau kurang dari 2 titik, lewati perangkat ini untuk tanggal ini
            if ($rawData->count() < 2) {
                continue;
            }

            // 2. Lakukan perhitungan akurat di sini
            $kwh = $this->calculateKwhFromRawData($rawData);

            // 3. Simpan ringkasan ke database
            DeviceDailySummary::updateOrCreate(
                [
                    'device_id' => $device->id,
                    'summary_date' => $date->format('Y-m-d')
                ],
                [
                    'samples_count' => $rawData->count(),
                    'avg_watt' => $rawData->avg('watt'),
                    'min_watt' => $rawData->min('watt'),
                    'max_watt' => $rawData->max('watt'),
                    'avg_temperature' => $rawData->avg('temperature'),
                    'avg_voltage' => $rawData->avg('voltage'),
                    'avg_current' => $rawData->avg('current'),
                    'total_kwh' => $kwh
                ]
            );

            // 4. Update data tren setelah data harian diperbarui
            $this->updateTrendingData($device);
        }
    }

    public function aggregateMonthlyData(int $year, int $month): void
    {
        $devices = Device::with([
            'dailySummaries' => function ($query) use ($year, $month) {
                $query->whereYear('summary_date', $year)
                    ->whereMonth('summary_date', $month);
            }
        ])->get();

        foreach ($devices as $device) {
            $summaries = $device->dailySummaries;

            if ($summaries->isEmpty())
                continue;

            DeviceMonthlySummary::updateOrCreate(
                [
                    'device_id' => $device->id,
                    'summary_year' => $year,
                    'summary_month' => $month
                ],
                [
                    'avg_watt' => $summaries->avg('avg_watt'),
                    'total_kwh' => $summaries->sum('total_kwh'),
                    'peak_watt' => $summaries->max('max_watt'),
                    'avg_temperature' => $summaries->avg('avg_temperature')
                ]
            );

            $this->updateTrendingData($device);
        }
    }

    protected function calculateKwhFromRawData(Collection $data): float
    {
        if ($data->count() < 2) {
            return 0;
        }

        $totalWattSeconds = 0;

        for ($i = 0; $i < $data->count() - 1; $i++) {
            $currentPoint = $data[$i];
            $nextPoint = $data[$i + 1];

            // Rata-rata watt antara dua titik
            $avgPowerInterval = ($currentPoint->watt + $nextPoint->watt) / 2;
            // Durasi dalam detik antara dua titik
            $durationSeconds = Carbon::parse($nextPoint->timestamp)->diffInSeconds(Carbon::parse($currentPoint->timestamp));

            // Batasi durasi untuk menghindari perhitungan aneh jika ada jeda data panjang
            // Misal, jika data dikirim setiap 30 detik, jeda > 60s dianggap anomali
            if ($durationSeconds > 60) {
                // Anda bisa menggunakan interval standar, atau mengabaikannya.
                // Menggunakan interval 30 detik adalah asumsi yang aman.
                $durationSeconds = 30;
            }

            $totalWattSeconds += $avgPowerInterval * $durationSeconds;
        }

        $totalWattHours = $totalWattSeconds / 3600;
        return $totalWattHours / 1000; // Konversi ke kWh
    }

    protected function calculateOperatingHours($data): float
    {
        $uniqueHours = $data->groupBy(function ($item) {
            return Carbon::parse($item->timestamp)->format('Y-m-d H');
        })->count();

        return $uniqueHours;
    }

    protected function updateTrendingData(Device $device): void
    {
        $summaryToday = $device->dailySummaries()
            ->whereDate('summary_date', today())
            ->first();

        $avgWatt24h = $summaryToday ? $summaryToday->avg_watt : $device->data()->last24Hours()->avg('watt');

        $efficiency = null;
        if ($device->btu > 0 && $avgWatt24h > 0) {
            $efficiency = $device->btu / $avgWatt24h;
        }

        $last7d_kwh = $device->dailySummaries()
            ->whereBetween('summary_date', [today()->subDays(6), today()])
            ->sum('total_kwh');

        $last30d_kwh = $device->dailySummaries()
            ->whereBetween('summary_date', [today()->subDays(29), today()])
            ->sum('total_kwh');

        DeviceTrendingData::updateOrCreate(
            ['device_id' => $device->id],
            [
                'last_24h_kwh' => optional($summaryToday)->total_kwh ?? 0,
                'last_7d_kwh' => $last7d_kwh,
                'last_30d_kwh' => $last30d_kwh,
                'current_efficiency' => $efficiency
            ]
        );
    }
}
