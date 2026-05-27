<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `manzanas` — codificación urbana de la trama de cada zona (Art. 10).
 *
 * La Dirección de Seguridad Ciudadana designa una codificación a cada
 * manzana del área del SIMETSA para asignar personal operativo, de control
 * y supervisión. Cada manzana pertenece a una zona y delimita un área
 * (polígono JSON) dentro de ella.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manzanas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')
                  ->constrained('zonas')
                  ->cascadeOnDelete();

            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100)->nullable();
            $table->text('descripcion')->nullable();

            // Polígono de la manzana: arreglo JSON de pares [lat, lng]
            $table->jsonb('poligono')->nullable();

            $table->string('color', 7)->default('#6c757d');
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['zona_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manzanas');
    }
};