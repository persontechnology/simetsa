<?php
// database/migrations/2026_05_20_130200_create_calificaciones_curso_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `calificaciones_curso` — nota por temática de una inscripción.
 *
 * Una fila por cada una de las 3 temáticas del Art. 33.5 (Atención al Cliente,
 * Primeros Auxilios, Educación Vial). La nota admite 2 decimales (Art. 33.5.c).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones_curso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_curso_id')->constrained('inscripciones_curso')->cascadeOnDelete();
            $table->enum('tematica', ['atencion_cliente', 'primeros_auxilios', 'educacion_vial']);
            $table->decimal('nota', 5, 2); // 0.00 - 100.00
            $table->timestamps();

            $table->unique(['inscripcion_curso_id', 'tematica']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones_curso');
    }
};