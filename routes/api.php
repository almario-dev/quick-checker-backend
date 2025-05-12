<?php

use App\Http\Controllers\Api\AnswerKeyController;
use App\Http\Controllers\Api\AnswerSheetController;
use App\Http\Controllers\Api\SubjectController as ApiSubjectController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\QuickCheckerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('throttle:5,1')
    ->controller(AuthenticationController::class)
    ->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('throttle:10,1')
        ->controller(AuthenticationController::class)
        ->group(function () {
            Route::get('refresh', 'refresh');
            Route::get('logout', 'logout');
        });

    Route::post('quick-check', [QuickCheckerController::class, 'quickCheck']);

    Route::prefix('subjects')
        ->controller(ApiSubjectController::class)
        ->group(function () {
            Route::get('', 'index');
            Route::post('', 'store');
            Route::put('{subject}', 'update');
            Route::delete('{subject}', 'destroy');
        });

    Route::prefix('answer-keys')
        ->controller(AnswerKeyController::class)
        ->group(function () {
            Route::get('', 'index');
            Route::post('', 'store');
            Route::get('{answerKey}/full-details', 'fullDetails');
            Route::put('{answerKey}', 'reanalyze');
            Route::post('{answerKey}', 'update');
            Route::delete('{answerKey}', 'destroy');
        });

    Route::controller(AnswerSheetController::class)->group(function () {
        Route::post('scan', 'store');

        Route::prefix('answer-sheets')->group(function () {
            Route::get('', 'index');
            Route::get('{answerSheet}/info', 'info');
        });
    });

    Route::prefix('user')
        ->controller(UserController::class)
        ->group(function () {
            Route::post('change-name', 'changeName');
            Route::post('change-password', 'changePassword');
        });

    Route::prefix('settings')
        ->controller(SettingsController::class)
        ->group(function () {
            Route::post('', 'save');
        });
});
