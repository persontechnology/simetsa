<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CredencialDiscapacidadController as ApiCredencialDiscapacidadController;
use App\Http\Controllers\Api\TicketController as ApiTicketController;
use App\Http\Controllers\Api\TipoVehiculoController as ApiTipoVehiculoController;
use App\Http\Controllers\Api\VehiculoController as ApiVehiculoController;
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

        // ===== Fase 4.A — Catálogo de tipos de vehículo (solo lectura, Art. 25) =====
        Route::get('tipos-vehiculo', [ApiTipoVehiculoController::class, 'index'])->name('api.tipos-vehiculo.index');

        // ===== Fase 4.B — Vehículos del conductor (Art. 25) =====
        Route::apiResource('vehiculos', ApiVehiculoController::class)
            ->names([
                'index'   => 'api.vehiculos.index',
                'store'   => 'api.vehiculos.store',
                'show'    => 'api.vehiculos.show',
                'update'  => 'api.vehiculos.update',
                'destroy' => 'api.vehiculos.destroy',
            ]);

        // ===== Fase 4.C — Credencial CONADIS del conductor (Art. 26) =====
        Route::post('vehiculos/{vehiculo}/credencial',  [ApiCredencialDiscapacidadController::class, 'store'])->name('api.credencial.store');
        Route::get('vehiculos/{vehiculo}/credencial',   [ApiCredencialDiscapacidadController::class, 'show'])->name('api.credencial.show');

        // ===== Fase 5.C — Tickets del conductor (Arts. 13, 14, 19, 22) =====
        Route::get('tickets', [ApiTicketController::class, 'index'])
            ->middleware('permission:tickets.ver')
            ->name('api.tickets.index');
        Route::get('tickets/historial', [ApiTicketController::class, 'historial'])
            ->middleware('permission:tickets.ver')
            ->name('api.tickets.historial');
        Route::get('tickets/{ticket}', [ApiTicketController::class, 'show'])
            ->middleware('permission:tickets.ver')
            ->name('api.tickets.show');
        Route::post('tickets', [ApiTicketController::class, 'store'])
            ->middleware('permission:tickets.comprar')
            ->name('api.tickets.store');
        Route::post('tickets/{ticket}/cancelar', [ApiTicketController::class, 'cancelar'])
            ->middleware('permission:tickets.cancelar')
            ->name('api.tickets.cancelar');
    });
});