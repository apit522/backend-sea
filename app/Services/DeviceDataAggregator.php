<?php
// app/Services/DeviceDataAggregator.php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceDailySummary;
use App\Models\DeviceMonthlySummary;
use App\Models\DeviceTrendingData;
use Carbon\Carbon;

class DeviceDataAggregator
{
    public function aggregateDailyData(Carbon $date): void
    {
        $devices = Device::with([
            'data' => function ($query) use ($date) {
                $query->whereDate('timestamp', $date);
            }
        ])->get();

        foreach ($devices as $device) {
            $data = $device->data;

            if ($data->isEmpty())
                continue;

            $kwh = $this->calculateKwh($data);
            $operatingHours = $this->calculateOperatingHours($data);

            DeviceDailySummary::updateOrCreate(
                [
                    'device_id' => $device->id,
                    'summary_date' => $date->format('Y-m-d')
                ],
                [
                    'samples_count' => $data->count(),
                    'avg_watt' => $data->avg('watt'),
                    'min_watt' => $data->min('watt'),
                    'max_watt' => $data->max('watt'),
                    'avg_temperature' => $data->avg('temperature'),
                    'avg_voltage' => $data->avg('voltage'),
                    'avg_current' => $data->avg('current'),
                    'total_kwh' => $kwh,
                    'operating_hours' => $operatingHours
                ]
            );

            $this->updateTrendingData($device);
        }
    }

    public function aggregateMonthlyData(int $year, int $month): void
    {
        $date = Carbon::create($year, $month, 1);
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
                    'operating_hours' => $summaries->sum('operating_hours'),
                    'peak_watt' => $summaries->max('max_watt'),
                    'avg_temperature' => $summaries->avg('avg_temperature')
                ]
            );

            $this->updateTrendingData($device);
        }
    }

    protected function calculateKwh($data): float
    {
        // Implementasi perhitungan kWh berdasarkan data watt dan interval waktu
        $totalWh = 0;
        $prev = null;

        foreach ($data->sortBy('timestamp') as $item) {
            if ($prev) {
                $hours = $prev->timestamp->diffInSeconds($item->timestamp) / 3600;
                $totalWh += ($prev->watt * $hours);
            }
            $prev = $item;
        }

        return $totalWh / 1000; // Convert to kWh
    }

    protected function calculateOperatingHours($data): float
    {
        $uniqueHours = $data->groupBy(function ($item) {
            return $item->timestamp->format('Y-m-d H');
        })->count();

        return $uniqueHours;
    }

    protected function updateTrendingData(Device $device): void
    {
        $last24h = $device->dailySummaries()
            ->whereDate('summary_date', today())
            ->sum('total_kwh');

        $last7d = $device->dailySummaries()
            ->whereBetween('summary_date', [today()->subDays(6), today()])
            ->sum('total_kwh');

        $last30d = $device->dailySummaries()
            ->whereBetween('summary_date', [today()->subDays(29), today()])
            ->sum('total_kwh');

        $efficiency = $device->btu
            ? ($device->btu * 0.29307107) / $device->data()->avg('watt')
            : null;

        DeviceTrendingData::updateOrCreate(
            ['device_id' => $device->id],
            [
                'last_24h_kwh' => $last24h,
                'last_7d_kwh' => $last7d,
                'last_30d_kwh' => $last30d,
                'current_efficiency' => $efficiency
            ]
        );
    }
}