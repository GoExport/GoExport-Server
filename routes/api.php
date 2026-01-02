<?php

use App\Http\Controllers\Api\ExportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::middleware('api.key')->prefix('v1')->group(function () {
    // Export routes
    Route::get('exports', [ExportController::class, 'index']);
    Route::post('exports', [ExportController::class, 'store']);
    Route::get('exports/{export}', [ExportController::class, 'show']);
    Route::get('exports/{export}/status', [ExportController::class, 'status']);
    Route::get('exports/{export}/download', [ExportController::class, 'download']);
    Route::post('exports/{export}/cancel', [ExportController::class, 'cancel']);
    Route::post('exports/{export}/retry', [ExportController::class, 'retry']);
    Route::delete('exports/{export}', [ExportController::class, 'destroy']);
});
