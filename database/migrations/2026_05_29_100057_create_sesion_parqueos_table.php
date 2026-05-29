<?php

// database/migrations/2026_05_29_100057_create_sesion_parqueos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesión de parqueo: registro del inicio físico del estacionamiento confirmado por el agente.
 *
 * Relación 1:1 con tickets. El ticket lo crea el conductor (compra);
 * la sesión la crea el agente cuando confirma que el vehículo está estacionado.
 * La tolerancia de 5 minutos se calcula contra expira_en del ticket (Art. 13).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_parqueo', function (Blueprint $table) {
            $table->id();

            /** FK única: un ticket tiene como máximo una sesión de parqueo. */
            $table->foreignId('ticket_id')->unique()->constrained('tickets');

            /** Agente que confirmó el inicio (nullable: podría iniciarse automáticamente en Fase 6). */
            $table->foreignId('agente_id')->nullable()->constrained('agentes_parqueo')->nullOnDelete();

            /** Plaza específica donde se estacionó (el agente puede marcarla). */
            $table->foreignId('plaza_id')->nullable()->constrained('plazas')->nullOnDelete();

            /** Coordenadas donde el agente registró el inicio. */
            $table->decimal('lat_inicio', 10, 7)->nullable();
            $table->decimal('lng_inicio', 10, 7)->nullable();

            /** Momento exacto en que el agente confirmó el inicio del parqueo. */
            $table->timestamp('inicio_at');

            /** Fin programado = inicio_at + horas_compradas del ticket. */
            $table->timestamp('fin_programado_at');

            /** Momento real de finalización (cuando el vehículo salió o el agente lo cerró). */
            $table->timestamp('fin_real_at')->nullable();

            /**
             * Estado de la sesión (App\Enums\EstadoSesionParqueo):
             * activa | finalizada | excedida
             */
            $table->string('estado', 20)->default('activa');

            $table->timestamps();

            $table->index('agente_id');
            $table->index('estado');
            $table->index('inicio_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_parqueo');
    }
};
