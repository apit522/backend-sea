<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Device;

class PredictionController extends Controller
{
    public function predictTomorrow(Device $device)
    {
        // Ambil data 14 hari terakhir
        $rawData = DB::table('device_data')
            ->select('watt', 'temperature', 'timestamp')
            ->where('device_id', $device->id)
            ->where('timestamp', '>=', Carbon::now()->subDays(14))
            ->orderBy('timestamp')
            ->get();

        if (count($rawData) < 14) {
            return response()->json(['error' => 'Not enough data (min 14 days required)']);
        }

        // Ubah ke array dengan Carbon
        $dataArray = $rawData->map(function ($row) {
            return [
                'timestamp' => Carbon::parse($row->timestamp),
                'watt' => (float) $row->watt,
                'temperature' => (float) $row->temperature,
            ];
        })->toArray();

        // Hitung Wh per hari
        $daily = [];
        foreach ($dataArray as $row) {
            $day = $row['timestamp']->format('Y-m-d');
            if (!isset($daily[$day])) {
                $daily[$day] = ['Wh' => 0, 'temp_sum' => 0, 'count' => 0];
            }
            $daily[$day]['Wh'] += $row['watt'] * (30 / 3600);
            $daily[$day]['temp_sum'] += $row['temperature'];
            $daily[$day]['count']++;
        }

        $dailyData = [];
        foreach ($daily as $day => $val) {
            $avgTemp = $val['temp_sum'] / $val['count'];
            $date = Carbon::parse($day);
            $dailyData[] = [
                'date' => $date,
                'Wh' => $val['Wh'],
                'temp_avg' => $avgTemp
            ];
        }

        usort($dailyData, function ($a, $b) {
            return $a['date']->timestamp - $b['date']->timestamp;
        });

        // Hitung fitur tambahan
        $result = [];
        $rolling = [];
        foreach ($dailyData as $i => $row) {
            $Wh = $row['Wh'];
            $temp_avg = $row['temp_avg'];
            $date = $row['date'];

            $prevWh = $i > 0 ? $dailyData[$i - 1]['Wh'] : $Wh;
            $delta_pct = ($prevWh != 0) ? (($Wh - $prevWh) / $prevWh) : 0;

            $rolling[] = $Wh;
            if (count($rolling) > 7)
                array_shift($rolling);
            $rolling_mean_7 = array_sum($rolling) / count($rolling);

            $trend_7 = $Wh - $rolling_mean_7;

            $dayofweek = (int) $date->dayOfWeek;
            $is_weekend = in_array($dayofweek, [5, 6]) ? 1 : 0;

            // Tambahan fitur baru
            $thermal_pressure = ($temp_avg - 28) * $Wh;
            $cooling_rate_avg = 0; // tidak tersedia di harian → set 0
            $cooling_freq = 0; // tidak tersedia di harian → set 0
            $temp_hour_interaction = $temp_avg * sin(2 * pi() * 12 / 24); // asumsi rata-rata jam = 12
            $temp_squared = $temp_avg ** 2;

            $result[] = [
                'Wh' => $Wh,
                'temp_avg' => $temp_avg,
                'delta_pct' => $delta_pct,
                'rolling_mean_7' => $rolling_mean_7,
                'trend_7' => $trend_7,
                'dayofweek' => $dayofweek,
                'is_weekend' => $is_weekend,
                'thermal_pressure' => $thermal_pressure,
                'cooling_rate_avg' => $cooling_rate_avg,
                'cooling_freq' => $cooling_freq,
                'temp_hour_interaction' => $temp_hour_interaction,
                'temp_squared' => $temp_squared
            ];
        }

        $windowData = array_slice($result, -14);

        // Kirim ke API FastAPI
        $response = Http::post('http://127.0.0.1:8001/predict', [
            'data' => $windowData
        ]);

        return response()->json([
            'prediction' => $response->json()
        ]);
    }
}