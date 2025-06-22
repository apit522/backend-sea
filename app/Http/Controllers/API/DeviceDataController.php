<?php
// app/Http/Controllers/Api/DeviceDataController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use App\Services\DeviceDataAggregator;
use Carbon\Carbon;

class DeviceDataController extends Controller
{
    // Method untuk mengambil data mentah (misal untuk grafik real-time detail)
    public function getRawData(Device $device, Request $request)
    {
        // Otorisasi: pastikan user hanya bisa melihat data perangkatnya
        if ($device->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = $device->data()
            ->orderBy('timestamp', 'desc')
            ->limit(100); // Batasi hanya 100 data terakhir untuk performa

        return response()->json($query->get());
    }

    // Method untuk mendapatkan ringkasan data yang sudah diagregasi
    public function getSummary(Device $device, Request $request)
    {
        // Otorisasi
        if ($device->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $range = $request->input('range', 'daily');

        return match ($range) {
            'daily' => $this->getDailySummary($device, $request),
            'monthly' => $this->getMonthlySummary($device, $request),
            'trending' => response()->json($device->trendingData),
            default => response()->json(['message' => 'Invalid range'], 400)
        };
    }

    //get daily summary menggunakan method ondemand
    // protected function getDailySummary(Device $device, Request $request)
    // {
    //     $request->validate([
    //         'start_date' => 'nullable|date_format:Y-m-d',
    //         'end_date' => 'nullable|date_format:Y-m-d'
    //     ]);

    //     $startDate = Carbon::parse($request->input('start_date', today()->subMonth()));
    //     $endDate = Carbon::parse($request->input('end_date', today()));

    //     // --- LOGIKA AGREGASI ON-DEMAND DIMULAI DI SINI ---

    //     // 2. Loop setiap hari dalam rentang tanggal yang diminta
    //     for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
    //         // 3. Periksa apakah ringkasan untuk tanggal ini sudah ada di database
    //         $summaryExists = $device->dailySummaries()
    //             ->whereDate('summary_date', $date)
    //             ->exists();

    //         // 4. Jika TIDAK ADA, panggil service aggregator untuk membuatnya
    //         if (!$summaryExists) {
    //             // Gunakan 'app()' untuk memanggil instance dari service aggregator
    //             app(DeviceDataAggregator::class)->aggregateDailyData($date);
    //         }
    //     }
    //     // --- LOGIKA AGREGASI ON-DEMAND SELESAI ---

    //     // Setelah memastikan semua data ringkasan ada, ambil dan kirim ke frontend
    //     $query = $device->dailySummaries()
    //         ->orderBy('summary_date', 'asc')
    //         ->whereBetween('summary_date', [$startDate, $endDate]);

    //     return response()->json($query->get());
    // }

    // Mengambil ringkasan Harian dari tabel 'device_daily_summaries'
    protected function getDailySummary(Device $device, Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d'
        ]);

        $query = $device->dailySummaries()
            ->orderBy('summary_date', 'asc')
            ->whereBetween('summary_date', [
                $request->input('start_date', today()->subMonth()->format('Y-m-d')),
                $request->input('end_date', today()->format('Y-m-d'))
            ]);

        return response()->json($query->get());
    }

    // Mengambil ringkasan Bulanan dari tabel 'device_monthly_summaries'
    protected function getMonthlySummary(Device $device, Request $request)
    {
        $request->validate(['year' => 'nullable|integer|min:2020']);

        $year = $request->input('year', today()->year);

        $query = $device->monthlySummaries()
            ->where('summary_year', $year)
            ->orderBy('summary_month', 'asc');

        return response()->json($query->get());
    }

}