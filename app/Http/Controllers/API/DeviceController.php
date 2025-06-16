<?php
// app/Http/Controllers/API/DeviceController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
            'unique_id' => 'required|string|max:255|unique:devices,unique_id',
            'btu' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device = Auth::user()->devices()->create($validator->validated());

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
        // Pastikan user hanya bisa mengupdate perangkat miliknya
        if ($device->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'unique_id' => 'required|string|max:255|unique:devices,unique_id,' . $device->id,
            'btu' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device->update($validator->validated());

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
        // Otorisasi tetap berjalan
        if ($device->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Ambil SEMUA data untuk perangkat ini, abaikan filter waktu untuk sementara
        $deviceData = $device->data()
            ->orderBy('created_at', 'asc')
            ->get();

        // Anda bisa menambahkan log untuk memastikan data ada
        \Log::info('Mengambil data untuk device ID: ' . $device->id . '. Ditemukan: ' . $deviceData->count() . ' baris.');

        return response()->json($deviceData);
    }
}