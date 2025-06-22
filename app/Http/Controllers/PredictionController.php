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
        // Ambil data hari ini, dikelompokkan per jam
        $hourlyData = DB::table('device_data')
            ->select(DB::raw('AVG(watt) as watt, AVG(temperature) as temperature, HOUR(timestamp) as hour, MAX(timestamp) as timestamp'))
            ->where('device_id', $device->id)
            ->groupBy(DB::raw('HOUR(timestamp)'))
            ->whereDate('timestamp','>=', now()->subDays(2))
            ->orderBy('hour')
            ->get();

        if (count($hourlyData) < 3) {
            return response()->json(['error' => 'Not enough data']);
        }

        // Ambil baris terakhir sebagai acuan
        $last = $hourlyData[count($hourlyData) - 1];
        $lastTemp = $last->temperature;
        $lastWatt = $last->watt;
        $lastTimestamp = Carbon::parse($last->timestamp);
        $tomorrowDate = $lastTimestamp->copy()->addDay()->startOfDay();

        // Rolling average watt dari 3 jam terakhir
        $rollingAvg = collect($hourlyData)->take(-3)->avg('watt');

        $data = [];

        // Simulasikan 24 jam untuk besok
        for ($i = 0; $i < 24; $i++) {
            $ts = $tomorrowDate->copy()->addHours($i);
            $hour = $ts->hour;
            $temp = $lastTemp; // atau gunakan forecast di sini kalau tersedia
            $interaction = $temp * $hour;
            $isIdle = $lastWatt < 50 ? 1 : 0;
            $diff = $temp - $lastWatt;

            $data[] = [
                'temperature' => $temp,
                'hour' => $hour,
                'day' => (int) $ts->format('d'),
                'hour_count' => 24,
                'lagged_watt' => $lastWatt,
                'is_idle' => $isIdle,
                'rolling_avg_watt' => $rollingAvg,
                'watt_diff' => $diff,
                'temp_hour_interaction' => $interaction
            ];

            // Update lastWatt untuk simulasi jam berikutnya
            $lastWatt = $temp; // jika kamu punya model prediksi jam ke-jam, bisa ubah ini
        }

        // Kirim ke FastAPI
        $response = Http::post('http://127.0.0.1:8001/predict', $data);

        // Return response
        return response()->json([
            'prediction' => $response->json()
        ]);
    }
}
