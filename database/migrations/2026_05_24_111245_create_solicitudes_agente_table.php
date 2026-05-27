<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `solicitudes_agente` — trámite de postulación a Agente de Parqueo.
 *
 * Modela el proceso de autorización del Art. 32 mediante el campo `estado`,
 * que avanza por las 3 etapas (documentación → capacitación → autorización)
 * hasta `autorizada`, o termina en `rechazada`. Los requisitos del postulante
 * provienen del Art. 33 (educación básica media, edad mínima 18 años, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_agente', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique(); // folio del trámite (SA-0001)

            // Datos del postulante (Art. 33)
            $table->string('cedula', 10);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->date('fecha_nacimiento'); // valida edad >= 18 (Art. 33 req. 4)
            $table->string('telefono', 20)->nullable();
            $table->string('telefono_celular', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('nivel_educacion', 30)->nullable(); // Art. 33 req. 1

            // Estado del trámite (Art. 32)
            $table->enum('estado', [
                'documentacion', 'capacitacion', 'autorizacion', 'autorizada', 'rechazada',
            ])->default('documentacion');

            $table->text('observaciones')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->date('fecha_solicitud');

            $table->foreignId('usuario_registro_id')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
            $table->index('cedula');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_agente');
    }
};