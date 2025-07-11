<?php

namespace App\Observers;

use App\Models\DeviceData;
use App\Models\Device;

class DeviceDataObserver
{
    /**
     * Handle the DeviceData "created" event.
     */
    public function created(DeviceData $deviceData): void
    {
        $device = $deviceData->device;

        // 1. Update timestamp 'last_seen_at' di tabel devices
        $device->touch();

        // 2. Hitung dan update efisiensi EER berdasarkan watt tertinggi (peak) 10 menit terakhir
        if ($device->btu > 0) {
            // Ambil nilai watt tertinggi dari 10 menit terakhir
            $peakWattRecent = $device->data()
                ->where('timestamp', '>=', now()->subMinutes(10))
                ->max('watt');

            if ($peakWattRecent > 0) {
                // Rumus EER: (BTU/jam) / Peak Watt
                $efficiency = $device->btu / $peakWattRecent;

                // Gunakan updateOrCreate untuk membuat atau mengupdate data tren
                $device->trendingData()->updateOrCreate(
                    ['device_id' => $device->id], // Kondisi pencarian
                    ['current_efficiency' => $efficiency] // Nilai yang diupdate
                );
            }
        }
    }


    /**
     * Handle the DeviceData "updated" event.
     */
    public function updated(DeviceData $deviceData): void
    {
        //
    }

    /**
     * Handle the DeviceData "deleted" event.
     */
    public function deleted(DeviceData $deviceData): void
    {
        //
    }

    /**
     * Handle the DeviceData "restored" event.
     */
    public function restored(DeviceData $deviceData): void
    {
        //
    }

    /**
     * Handle the DeviceData "force deleted" event.
     */
    public function forceDeleted(DeviceData $deviceData): void
    {
        //
    }
}
