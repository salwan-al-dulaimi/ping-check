<?php

use App\Http\Controllers\PingController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('ping', 'Ping')->name('ping');

    Route::get('/websites', [WebsiteController::class, 'all'])->name('websites.all');
    Route::post('/websites', [WebsiteController::class, 'store'])->name('websites.store');
    Route::patch('/websites/{website}', [WebsiteController::class, 'update'])->name('websites.update');
    Route::delete('/websites/{website}', [WebsiteController::class, 'destroy'])->name('websites.destroy');

    Route::get('/pings', [PingController::class, 'getAllPings'])->name('pings.all');
    Route::post('/pings', [PingController::class, 'store'])->name('pings.store');
    Route::patch('/pings/{ping}', [PingController::class, 'update'])->name('pings.update');
    Route::delete('/pings/{ping}', [PingController::class, 'destroy'])->name('pings.destroy');
});

require __DIR__.'/settings.php';
