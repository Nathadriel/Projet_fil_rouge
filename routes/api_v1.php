<?php

use App\Http\Controllers\API\BetController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::get('/ping', function () {
        return response()->json(['status' => 'success', 'message' => 'pong']);
    })->name('ping');

    // Public routes (10 attempts per minute on register and login)
    Route::post('/register', [UserController::class, 'register'])->middleware('throttle:10,1')->name('register');
    Route::post('/login', [UserController::class, 'login'])->middleware('throttle:10,1')->name('login');

    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/leaderboard', [EventController::class, 'leaderboard'])->name('leaderboard');

    // Routes protected by Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [UserController::class, 'logout'])->name('logout');
        Route::get('/me', [UserController::class, 'me'])->name('me');

        Route::post('/events', [EventController::class, 'store'])->name('events.store');

        Route::get('/me/bets', [BetController::class, 'index'])->name('bets.index');
        Route::post('/events/{event}/bets', [BetController::class, 'store'])->middleware('throttle:30,1')->name('bets.store');
    });
});
