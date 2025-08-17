<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PredictionController extends Controller
{
    /**
     * Mengambil data fitur, mengirimkannya ke API prediksi, dan mengembalikan hasilnya.
     * Prediksi dapat didasarkan pada waktu saat ini atau tanggal historis yang ditentukan.
     *
     * @param Device $device
     * @param string|null $date Tanggal akhir opsional (format: YYYY-MM-DD).
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrediction(Device $device, $date = null)
    {
        // Ukuran window histori dalam jam yang dibutuhkan oleh model ML
        $windowSize = 120;
        $predictionApiUrl = env('PREDICTION_API_URL');

        // Tentukan tanggal akhir untuk data histori.
        // Jika tanggal diberikan di URL, gunakan itu. Jika tidak, gunakan waktu sekarang.
        try {
            $endDate = $date ? Carbon::parse($date)->endOfDay() : Carbon::now();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Format tanggal tidak valid. Gunakan YYYY-MM-DD.'], 400);
        }

        // 1. Ambil data fitur yang SUDAH DIOLAH dari database
        $featuresData = DB::table('device_hourly_features')
            ->where('device_id', $device->id)
            ->where('hour_timestamp', '<=', $endDate)
            ->select([
                'hour_x_weekend',
                'avg_usage_last_24h',
                'hour_sin_x_weekday_sin',
                'usage_same_hour_last_week',
                'hour_cos_x_weekday_cos',
                'delta_roll',
                'usage_Wh_roll12',
                'usage_Wh_roll6',
                'pct_change_Wh',
                'usage_Wh_roll3',
                'usage_Wh_lag1',
                'Cooling_Rate',
                'delta_on_rate',
                'mean_watt',
                'Delta_Cooling_Rate'
            ])
            ->orderBy('hour_timestamp', 'desc')
            ->limit($windowSize)
            ->get()
            ->sortBy('hour_timestamp'); // Urutkan kembali dari terlama -> terbaru

        // 2. Validasi apakah data cukup untuk membuat prediksi
        if ($featuresData->count() < $windowSize) {
            return response()->json([
                'error' => 'Data histori tidak cukup untuk membuat prediksi.',
                'message' => "Dibutuhkan {$windowSize} jam data, tersedia: {$featuresData->count()} jam."
            ], 422); // Unprocessable Entity
        }

        // 3. Siapkan payload untuk dikirim ke API prediksi
        $payload = $featuresData->values()->toArray();

        // 4. Panggil API Prediksi (FastAPI)
        try {
            $response = Http::timeout(30)->post($predictionApiUrl, [
                'data' => $payload
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Gagal mendapatkan prediksi dari server AI.',
                    'details' => $response->json() ?? $response->body()
                ], 502); // Bad Gateway
            }

            // 5. Olah respons dari API untuk ditampilkan ke pengguna
            $hourlyPredictions = $response->json()['predicted_energy_wh'];

            // Tanggal prediksi dimulai SATU HARI SETELAH $endDate, pada jam 00:00
            $predictionStartTimestamp = $endDate->copy()->addDay()->startOfDay();

            // --- PERUBAHAN DIMULAI DI SINI ---

            // Proses prediksi harian (agregasi)
            $predictionDays = 3; // Asumsikan horizon prediksi adalah 3 hari (72 jam)
            $dailyPredictions = [];

            for ($i = 0; $i < $predictionDays; $i++) {
                $dailyData = array_slice($hourlyPredictions, $i * 24, 24);
                if (empty($dailyData))
                    continue;

                $dailyPredictions[] = [
                    'tanggal' => $predictionStartTimestamp->copy()->addDays($i)->format('Y-m-d'),
                    'total_wh' => array_sum($dailyData),
                    'satuan' => 'Wh'
                ];
            }

            // Array nilai ensemble saja (tanpa tanggal dan satuan)
            $ensembleArray = array_map(function ($item) {
                return $item['total_wh'];
            }, $dailyPredictions);

            // Format jam ke string waktu agar mudah dibaca
            foreach ($hourlyPredictions as $index => $wh) {
                $hour = $predictionStartTimestamp->copy()->addHours($index);
                $hourlyPredictionDetails[] = [
                    'waktu' => $hour->format('Y-m-d H:i:s'),
                    'nilai_wh' => $wh,
                    'satuan' => 'Wh'
                ];
            }

            return response()->json([
                'prediction' => [
                    'ensemble' => $ensembleArray,
                    'hourly_prediction' => $hourlyPredictionDetails
                ]
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Tidak dapat terhubung ke server prediksi AI.',
                'details' => $e->getMessage()
            ], 503); // Service Unavailable
        }
    }
}