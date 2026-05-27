<?php
// database/migrations/2026_05_20_130100_create_inscripciones_curso_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `inscripciones_curso` — postulante inscrito en una edición del curso.
 *
 * Vincula una SolicitudAgente (en etapa de capacitación) con un curso. Guarda
 * el promedio final y el resultado (aprobado/reprobado) según el corte del
 * Art. 33.5.b (mínimo 70/100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones_curso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_capacitacion_id')->constrained('cursos_capacitacion')->cascadeOnDelete();
            $table->foreignId('solicitud_agente_id')->constrained('solicitudes_agente')->cascadeOnDelete();
            $table->date('fecha_inscripcion');
            $table->enum('estado', ['inscrito', 'aprobado', 'reprobado'])->default('inscrito');
            $table->decimal('promedio_final', 5, 2)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['curso_capacitacion_id', 'solicitud_agente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones_curso');
    }
};