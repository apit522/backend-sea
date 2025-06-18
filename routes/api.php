<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\DeviceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// // Rute yang perlu auth
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
// Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum'); // Logout sebaiknya di-protect
// Route::post('/user/profile', [App\Http\Controllers\API\AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
// Route::post('/user/change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');

// Route::apiResource('devices', DeviceController::class);

// Rute yang perlu auth
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('devices', DeviceController::class);
    Route::get('/devices/{device}/data', [DeviceController::class, 'getData']);
    Route::get('/devices/{device}/hourly-data', [DeviceController::class, 'getHourlyData']);
    Route::get('/devices/{device}/latest-data', [DeviceController::class, 'getLatestData']);
});

// Rute yang tidak perlu auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);
