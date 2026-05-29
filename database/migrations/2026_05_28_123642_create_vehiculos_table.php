<?php
// database/migrations/2026_05_28_123642_create_vehiculos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla vehiculos — vehículos registrados por los conductores (Art. 25 Ordenanza SIMETSA).
 *
 * La unicidad de placa se implementa con un índice parcial de PostgreSQL para
 * permitir soft-delete sin colisiones (la misma placa puede reactivarse).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conductor_id')->constrained('conductores')->cascadeOnDelete();
            $table->foreignId('tipo_vehiculo_id')->constrained('tipos_vehiculo');
            $table->string('placa', 10);
            $table->string('marca', 80);
            $table->string('modelo', 80);
            $table->smallInteger('anio');
            $table->string('color', 50);
            $table->string('estado', 20)->default('activo');   // activo | inactivo
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('conductor_id');
            $table->index('estado');
        });

        // Índice parcial: placa única entre vehículos no eliminados (PostgreSQL).
        // Permite re-registrar la misma placa tras un soft-delete.
        DB::statement(
            "CREATE UNIQUE INDEX vehiculos_placa_activa ON vehiculos(placa) WHERE deleted_at IS NULL"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
