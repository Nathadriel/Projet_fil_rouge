<?php

use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('/', [IndexController::class, 'index'])->name('home');
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/legal', 'legal.notice')->name('legal');

// Guests only
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Authenticated users
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::resource('events', EventController::class)->only(['create', 'store']);
    Route::post('/events/{event}/bets', [BetController::class, 'store'])->middleware('throttle:30,1')->name('bets.store');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/export', [ProfileController::class, 'export'])->name('profile.export');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
    Route::post('/shop/checkout', [ShopController::class, 'checkout'])->middleware('throttle:10,1')->name('shop.checkout');
    Route::get('/shop/success', [ShopController::class, 'success'])->name('shop.success');
});

Route::resource('events', EventController::class)->only(['index', 'show']);

// Stripe webhook (protected by the Stripe signature)
Route::post('/stripe/webhook', [ShopController::class, 'webhook'])->name('stripe.webhook');

// Back office
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminEventController::class, 'index'])->name('index');
    Route::post('/events/{event}/approve', [AdminEventController::class, 'approve'])->name('events.approve');
    Route::post('/events/{event}/refuse', [AdminEventController::class, 'refuse'])->name('events.refuse');
    Route::post('/events/{event}/resolve', [AdminEventController::class, 'resolve'])->name('events.resolve');
    Route::post('/events/{event}/cancel', [AdminEventController::class, 'cancel'])->name('events.cancel');
});
