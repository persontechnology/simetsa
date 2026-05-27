<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `dias_feriado` — calendario de feriados que suspenden el SIMETSA.
 *
 * Tipos:
 *  - nacional: feriado oficial del Ecuador (Año Nuevo, Trabajo, Pichincha…).
 *  - civico:   día cívico nacional (3 de noviembre).
 *  - cantonal: fiesta del Cantón Salcedo (19 de septiembre).
 *  - movil:    feriado con fecha variable cada año (Carnaval, Semana Santa).
 *
 * Recurrente: true si la fecha es fija cada año (Año Nuevo, Navidad).
 * False si requiere recargarse anualmente (feriados móviles).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dias_feriado', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('nombre', 150);
            $table->enum('tipo', ['nacional', 'civico', 'cantonal', 'movil']);
            $table->boolean('recurrente')->default(true);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'fecha']);
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dias_feriado');
    }
};