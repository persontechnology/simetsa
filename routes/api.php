<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — SIMETSA
|--------------------------------------------------------------------------
| Endpoints consumidos por la app móvil (React Native) vía Sanctum.
| Todas las respuestas usan el envelope {exito, mensaje, datos, errores}.
*/

Route::prefix('v1')->group(function () {

    // --- Públicas ---
    Route::post('registro', [AuthController::class, 'registrar'])->name('api.registro'); // Fase 4
    Route::post('login',    [AuthController::class, 'login'])->name('api.login');

    // --- Protegidas (token Sanctum) ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::get('perfil',  [AuthController::class, 'perfil'])->name('api.perfil');
    });
});