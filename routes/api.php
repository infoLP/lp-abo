<?php
use App\Http\Controllers\Api\SireneController;
use Illuminate\Support\Facades\Route;

// API Sirene : authentification requise + rate limiting
// 30 requêtes par minute par utilisateur
Route::prefix('sirene')
    ->middleware(['auth', 'throttle:30,1'])
    ->group(function () {
        Route::get('/lookup', [SireneController::class, 'lookup']);
        Route::get('/search', [SireneController::class, 'search']);
    });
