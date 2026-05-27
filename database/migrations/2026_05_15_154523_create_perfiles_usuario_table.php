<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla `perfiles_usuario`.
 *
 * Extiende al modelo User (sin tocar la tabla `users`) con datos
 * personales requeridos por el SIMETSA y por la Ley Orgánica de
 * Protección de Datos Personales del Ecuador (LOPDP):
 *  - cedula (10 dígitos, algoritmo módulo 10)
 *  - telefono, telefono_celular, direccion
 *  - fecha_nacimiento, genero, foto_perfil
 *  - acepta_terminos + fecha_aceptacion_terminos (consentimiento informado)
 *  - activo (soft-disable sin borrar)
 *  - softDeletes para retención de datos sensibles
 */
return new class extends Migration
{
    /**
     * Crea la tabla `perfiles_usuario` con relación 1:1 a `users`.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('perfiles_usuario', function (Blueprint $table) {
            $table->id();

            // Relación 1:1 con users; unique fuerza la cardinalidad
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Cédula ecuatoriana: 10 dígitos, única e indexada para búsquedas
            $table->string('cedula', 10)->unique();

            // Teléfono fijo (opcional) y celular (obligatorio para notificaciones)
            $table->string('telefono', 15)->nullable();
            $table->string('telefono_celular', 15)->nullable();

            // Dirección domiciliaria (opcional)
            $table->string('direccion', 255)->nullable();

            // Fecha de nacimiento (opcional, útil para validar mayoría de edad
            // de Agentes de Parqueo según Art. 33.4 de la Ordenanza)
            $table->date('fecha_nacimiento')->nullable();

            // Género: M=Masculino, F=Femenino, O=Otro, ND=No declara
            $table->enum('genero', ['M', 'F', 'O', 'ND'])->nullable();

            // Ruta relativa al archivo en storage/app/public/perfiles
            $table->string('foto_perfil', 255)->nullable();

            // Consentimiento informado LOPDP (obligatorio para procesar datos)
            $table->boolean('acepta_terminos')->default(false);
            $table->timestamp('fecha_aceptacion_terminos')->nullable();

            // Soft-disable: permite inhabilitar sin borrar (sesión cerrada, sin login)
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes(); // Retención bajo LOPDP
        });
    }

    /**
     * Revierte la migración eliminando la tabla.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('perfiles_usuario');
    }
};