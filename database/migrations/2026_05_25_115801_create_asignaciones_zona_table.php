<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `asignaciones_zona` — zona(s) asignada(s) a un agente (Art. 16).
 *
 * La Comisaría asigna agentes a las zonas tarifadas con un sistema de
 * rotación para igualar oportunidades (Art. 16). Cada asignación tiene
 * vigencia (fecha_inicio/fin) y puede activarse o desactivarse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_zona', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_parqueo_id')->constrained('agentes_parqueo')->cascadeOnDelete();
            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('activa')->default(true);
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agente_parqueo_id', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_zona');
    }
};