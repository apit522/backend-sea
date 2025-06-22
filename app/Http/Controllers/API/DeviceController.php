<?php
// app/Http/Controllers/API/DeviceController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\DeviceDataHourly;
use Illuminate\Support\Facades\DB;


class DeviceController extends Controller
{
    // Menampilkan semua perangkat milik pengguna yang terautentikasi
    public function index()
    {
        $devices = Auth::user()->devices()->latest()->get();
        return response()->json($devices);
    }

    // Menyimpan perangkat baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'unique_id' => 'required|string|max:255|unique:devices',
            'btu' => 'nullable|integer',
            'daya_va' => 'required|integer', // 'tarif_per_kwh' tidak lagi divalidasi dari input user
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // --- LOGIKA OTOMATISASI TARIF ---
        $daya_va = (int) $validatedData['daya_va'];
        $tarif_per_kwh = 1444.70; // Nilai default

        switch (true) {
            case ($daya_va == 900):
                $tarif_per_kwh = 1352.00;
                break;
            case ($daya_va >= 1300 && $daya_va <= 2200):
                $tarif_per_kwh = 1444.70;
                break;
            case ($daya_va >= 3500):
                $tarif_per_kwh = 1699.53;
                break;
        }

        // Tambahkan tarif yang sudah ditentukan ke data yang akan disimpan
        $validatedData['tarif_per_kwh'] = $tarif_per_kwh;
        // --- AKHIR LOGIKA OTOMATISASI TARIF ---

        $device = Auth::user()->devices()->create($validatedData);

        return response()->json($device, 201);
    }

    // Menampilkan satu perangkat (opsional, jika perlu halaman detail)
    public function show(Device $device)
    {
        // Pastikan user hanya bisa melihat perangkat miliknya
        if ($device->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($device);
    }

    // Mengupdate perangkat
    public function update(Request $request, Device $device)
    {
        // Otorisasi
        if ($device->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'unique_id' => 'required|string|max:255|unique:devices,unique_id,' . $device->id,
            'btu' => 'nullable|integer',
            'daya_va' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // --- LOGIKA OTOMATISASI TARIF (Sama seperti di 'store') ---
        $daya_va = (int) $validatedData['daya_va'];
        $tarif_per_kwh = 1444.70; // Nilai default

        switch (true) {
            case ($daya_va == 900):
                $tarif_per_kwh = 1352.00;
                break;
            case ($daya_va >= 1300 && $daya_va <= 2200):
                $tarif_per_kwh = 1444.70;
                break;
            case ($daya_va >= 3500):
                $tarif_per_kwh = 1699.53;
                break;
        }

        $validatedData['tarif_per_kwh'] = $tarif_per_kwh;
        // --- AKHIR LOGIKA OTOMATISASI TARIF ---

        $device->update($validatedData);

        return response()->json($device);
    }

    // Menghapus perangkat
    public function destroy(Device $device)
    {
        // Pastikan user hanya bisa menghapus perangkat miliknya
        if ($device->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $device->delete();

        return response()->json(['message' => 'Device deleted successfully'], 200);
    }

    public function getData(Request $request, Device $device)
    {
        // ... (Otorisasi tidak berubah) ...

        $period = $request->query('period', '24h');
        $startDate = match ($period) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            default => Carbon::now()->subHours(24),
        };

        // Ambil Data menggunakan kolom 'timestamp'
        $deviceData = $device->data()
            ->where('timestamp', '>=', $startDate)
            ->orderBy('timestamp', 'asc')
            ->get();

        return response()->json($deviceData);
    }
    public function getHourlyData(Request $request, Device $device)
    {
        if ($device->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $date = Carbon::parse($request->query('date'));

        $hourlyData = DeviceDataHourly::where('device_id', $device->id)
            ->whereDate('hour_timestamp', $date)
            ->orderBy('hour_timestamp', 'asc')
            ->get();

        return response()->json($hourlyData);
    }

    // public function getHourlyData(Request $request, Device $device)
    // {
    //     // Otorisasi: Pastikan user hanya bisa mengakses data dari perangkat miliknya
    //     if ($device->user_id !== Auth::id()) {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     // Validasi input tanggal
    //     $request->validate(['date' => 'required|date_format:Y-m-d']);
    //     $date = Carbon::parse($request->query('date'));

    //     // === PERUBAHAN UTAMA DI SINI ===
    //     // Sekarang kita query ke tabel 'device_data_hourly' yang sudah diringkas
    //     $hourlyData = DeviceDataHourly::where('device_id', $device->id)
    //         ->whereDate('hour_timestamp', $date)
    //         ->orderBy('hour_timestamp', 'asc')
    //         ->get();

    //     return response()->json($hourlyData);
    // }

    // public function getHourlyData(Request $request, Device $device)
    // {
    //     // 1. Otorisasi: Pastikan user hanya bisa mengakses data dari perangkat miliknya
    //     if ($device->user_id !== Auth::id()) {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     // 2. Validasi input tanggal
    //     $request->validate(['date' => 'required|date_format:Y-m-d']);
    //     $date = Carbon::parse($request->query('date'));

    //     // 3. Query agregasi langsung ke tabel 'device_data'
    //     $hourlyData = DB::table('device_data')
    //         // Kita perlu tarif per kWh, jadi kita join dengan tabel devices
    //         ->join('devices', 'device_data.device_id', '=', 'devices.id')
    //         ->where('device_data.device_id', $device->id)
    //         ->whereDate('device_data.timestamp', $date) // Filter berdasarkan tanggal yang dipilih
    //         ->selectRaw("
    //             -- Kelompokkan timestamp per jam
    //             DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour_timestamp,
    //             -- Hitung rata-rata untuk setiap metrik
    //             AVG(watt) as watt_avg,
    //             AVG(voltage) as voltage_avg,
    //             AVG(current) as current_avg,
    //             AVG(temperature) as temperature_avg,
    //             -- Hitung total kWh untuk setiap jam
    //             (AVG(watt) * 1) / 1000 as kwh_total,
    //             -- Hitung total biaya untuk setiap jam
    //             ((AVG(watt) * 1) / 1000) * devices.tarif_per_kwh as cost_total
    //         ")
    //         ->groupBy('hour_timestamp', 'devices.tarif_per_kwh') // Kelompokkan berdasarkan jam
    //         ->orderBy('hour_timestamp', 'asc') // Urutkan berdasarkan jam
    //         ->get();

    //     return response()->json($hourlyData);
    // }

    public function getLatestData(Device $device)
    {
        if ($device->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $latestData = $device->data()->latest('timestamp')->first();

        return response()->json($latestData);
    }
}