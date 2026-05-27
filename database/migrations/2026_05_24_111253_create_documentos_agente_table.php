<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `documentos_agente` — archivos cargados para una solicitud de agente.
 *
 * Cubre los requisitos del Art. 33 (cédula, educación, antecedentes penales,
 * certificado de no adeudar) y el oficio dirigido al Alcalde (Art. 34). Cada
 * documento se almacena en el disco `public` y lleva su estado de validación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_agente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_agente_id')
                  ->constrained('solicitudes_agente')
                  ->cascadeOnDelete();

            $table->enum('tipo', [
                'oficio', 'cedula', 'educacion', 'antecedentes_penales', 'no_adeudar', 'otro',
            ]);

            $table->string('nombre_archivo', 255); // nombre original
            $table->string('ruta_archivo', 500);   // ruta en disco public

            $table->boolean('validado')->default(false);
            $table->text('observacion')->nullable();
            $table->timestamp('fecha_validacion')->nullable();
            $table->foreignId('validado_por')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['solicitud_agente_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_agente');
    }
};