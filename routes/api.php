<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\QuickCheckerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('throttle:5,1')
    ->controller(AuthenticationController::class)
    ->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('throttle:10,1')->controller(AuthenticationController::class)->group(function () {
        Route::get('refresh', 'refresh');
        Route::get('logout', 'logout');
    });

    Route::post('quick-check', [QuickCheckerController::class, 'quickCheck']);
});
