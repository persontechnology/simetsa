<?php
// database/migrations/2026_05_28_180358_create_vehiculos_exonerados_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de vehículos exonerados de pago por tipo de institución (Art. 27 Ordenanza SIMETSA).
 *
 * NO tiene FK a vehiculos: son vehículos institucionales sin registro de conductor.
 * La verificación de exoneración en Fase 5 será:
 *   VehiculoExonerado::where('placa', $placa)->where('activo', true)->exists()
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos_exonerados', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 10);
            $table->string('institucion', 200);
            $table->string('tipo_exoneracion', 50);
            $table->string('nombre_funcionario', 200)->nullable();
            $table->string('numero_oficio', 100)->nullable();
            $table->smallInteger('tiempo_maximo_horas')->default(2);
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->boolean('activo')->default(true);
            $table->date('fecha_registro');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos_exonerados');
    }
};
