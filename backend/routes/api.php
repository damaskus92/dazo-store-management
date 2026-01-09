<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashierController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:super_admin')->group(function () {
        Route::apiResource('stores', StoreController::class);
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('cashiers', CashierController::class);

        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    Route::middleware('role:admin,cashier')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
    });
});
