<?php
// database/migrations/2026_05_28_174359_create_credenciales_discapacidad_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de credenciales CONADIS de conductores (Art. 26 Ordenanza SIMETSA).
 *
 * Un vehículo puede tener historial de credenciales (rechazadas + nueva activa),
 * pero solo una en estado 'pendiente' o 'aprobada' a la vez (regla en el servicio).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credenciales_discapacidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->string('numero_conadis', 50);
            $table->smallInteger('porcentaje_discapacidad')->nullable();
            $table->string('nombre_beneficiario', 200);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('ruta_archivo', 500)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->foreignId('aprobada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credenciales_discapacidad');
    }
};
