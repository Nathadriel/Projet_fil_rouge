<?php

use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PariController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Pages publiques
Route::get('/', HomeController::class)->name('home');
Route::get('/evenements', [EventController::class, 'index'])->name('events.index');
Route::get('/classement', LeaderboardController::class)->name('leaderboard');
Route::view('/confidentialite', 'legal.privacy')->name('privacy');
Route::view('/mentions-legales', 'legal.notice')->name('legal');

// Invités uniquement
Route::middleware('guest')->group(function () {
    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store']);
});

// Utilisateurs connectés
Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/evenements/creer', [EventController::class, 'create'])->name('events.create');
    Route::post('/evenements', [EventController::class, 'store'])->name('events.store');
    Route::post('/evenements/{event}/paris', [PariController::class, 'store'])
        ->middleware('throttle:30,1')->name('paris.store');

    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profil/export', [ProfileController::class, 'export'])->name('profile.export');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Back-office
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminEventController::class, 'index'])->name('index');
    Route::post('/evenements/{event}/valider', [AdminEventController::class, 'approve'])->name('events.approve');
    Route::post('/evenements/{event}/refuser', [AdminEventController::class, 'refuse'])->name('events.refuse');
    Route::post('/evenements/{event}/resoudre', [AdminEventController::class, 'resolve'])->name('events.resolve');
    Route::post('/evenements/{event}/annuler', [AdminEventController::class, 'cancel'])->name('events.cancel');
});

// Placée après /evenements/creer pour ne pas l'intercepter
Route::get('/evenements/{event}', [EventController::class, 'show'])->name('events.show');
