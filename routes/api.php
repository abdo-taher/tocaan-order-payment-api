<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('orders', OrderController::class);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
});
