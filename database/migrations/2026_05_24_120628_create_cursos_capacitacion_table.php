<?php
// database/migrations/2026_05_20_130000_create_cursos_capacitacion_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `cursos_capacitacion` — ediciones del curso para Agentes de Parqueo.
 *
 * El GAD, a través de la Dirección de Seguridad Ciudadana, organiza el curso
 * las veces que sean necesarias (Art. 33.5.a). Cada edición agrupa las
 * inscripciones de los postulantes en etapa de capacitación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos_capacitacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->unsignedSmallInteger('cupo')->nullable();
            $table->enum('estado', ['planificado', 'en_curso', 'finalizado'])->default('planificado');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos_capacitacion');
    }
};