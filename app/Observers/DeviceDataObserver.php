<?php

// app/Observers/DeviceDataObserver.php
namespace App\Observers;

use App\Models\DeviceData;
use App\Models\Device;

class DeviceDataObserver
{
    public function created(DeviceData $deviceData): void
    {
        // Update last seen timestamp
        Device::where('id', $deviceData->device_id)
            ->update(['last_seen_at' => now()]);

        // Update trending data efficiency
        $device = $deviceData->device;
        if ($device->btu && $deviceData->watt > 0) {
            $efficiency = ($device->btu * 0.29307107) / $deviceData->watt;

            $device->trendingData()->updateOrCreate(
                [],
                ['current_efficiency' => $efficiency]
            );
        }
    }
}
