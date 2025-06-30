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
        $device->touch('last_seen_at'); // 'touch' adalah cara cepat untuk update timestamp

        // 2. Hitung dan update efisiensi EER berdasarkan rata-rata 10 menit terakhir
        if ($device->btu > 0) {
            // Ambil semua data dari 10 menit terakhir untuk perangkat ini
            $recentData = $device->data()
                ->where('timestamp', '>=', now()->subMinutes(10))
                ->get();

            // Hitung rata-rata watt dari data tersebut
            $avgWattRecent = $recentData->avg('watt');

            if ($avgWattRecent > 0) { // Hindari pembagian dengan nol
                // Rumus EER: (BTU/jam) / Watt
                // Faktor 0.293... adalah untuk konversi BTU/h ke Watt, tapi karena kita
                // membandingkan BTU/h dengan Watt, kita tidak perlu konversi ini.
                // EER yang umum diukur dalam (BTU/h) / Watt.
                $efficiency = $device->btu / $avgWattRecent;

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
