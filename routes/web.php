<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorageFileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/app-storage/profile-photos/{filename}', [StorageFileController::class, 'serveProfilePhoto'])
    ->where('filename', '[a-zA-Z0-9._-]+')
    ->name('profile.photo.serve');
