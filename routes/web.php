<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RobotsController;
use App\Livewire\Pages\About;
use App\Livewire\Pages\Home;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes — no authentication required
|--------------------------------------------------------------------------
| All data pages are publicly accessible. Authentication is optional and
| unlocks additional features (e.g. saved stations, recent lookups).
| Use auth()->user() inside components to conditionally show those features.
*/
Route::get('/', Home::class)->name('home');
Route::get('/about', About::class)->name('about');
Route::get('/robots.txt', RobotsController::class)->name('robots');

/*
|--------------------------------------------------------------------------
| Guest-only routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated-only routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
});
