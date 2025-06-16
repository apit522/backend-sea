<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File; // Untuk mendapatkan MIME type
use Symfony\Component\HttpFoundation\Response; // Untuk response code

class StorageFileController extends Controller
{
    public function serveProfilePhoto($filename)
    {
        $path = 'profile_photos/' . $filename; // Path relatif di dalam disk 'public'

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Image not found.');
        }

        $file = Storage::disk('public')->get($path);
        $type = File::mimeType(Storage::disk('public')->path($path)); // Dapatkan MIME type

        // Menggunakan response()->file() juga bisa, tapi ini memberi kontrol lebih
        $response = response($file, 200);
        $response->header("Content-Type", $type);
        // Header lain seperti Cache-Control bisa ditambahkan di sini jika perlu

        return $response;
    }
}