<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\Api\DeviceDataController;
use App\Http\Controllers\PredictionController;

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
    Route::get('/devices/{device}/raw-data', [DeviceDataController::class, 'getRawData']);
    Route::get('/devices/{device}/summary', [DeviceDataController::class, 'getSummary']);
    Route::get('/devices/{device}/hourly-data', [DeviceController::class, 'getHourlyData']);
    Route::get('/devices/{device}/latest-data', [DeviceController::class, 'getLatestData']);
});

// Rute yang tidak perlu auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

Route::post('/predict-usage/{device}', [PredictionController::class, 'predictTomorrow']);
Route::get('/predict-usage/{device}', [PredictionController::class, 'predictTomorrow']);
