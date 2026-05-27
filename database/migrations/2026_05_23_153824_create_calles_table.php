<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `calles` — calles tarifadas que pertenecen a una zona.
 *
 * El detalle de calles proviene del Art. 16 de la Ordenanza, que enumera
 * cada vía con su tramo (desde/hasta). Cada calle guarda su sentido de
 * circulación y el costado donde aplica el estacionamiento (Art. 5, 18),
 * más una polilínea geográfica para visualización en Leaflet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')
                  ->constrained('zonas')
                  ->cascadeOnDelete();

            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);

            // Tramo descrito en el Art. 16 (texto libre, las vías de
            // referencia pueden no estar tarifadas)
            $table->string('desde', 150)->nullable();
            $table->string('hasta', 150)->nullable();

            // Sentido de circulación (Art. 18)
            $table->enum('sentido', ['unico', 'doble'])->default('doble');

            // Costado donde se permite estacionar (Art. 5)
            $table->enum('lado_estacionamiento', ['derecho', 'izquierdo', 'ambos'])
                  ->default('derecho');

            // Trazado de la vía: arreglo JSON de pares [lat, lng]
            $table->jsonb('polilinea')->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['zona_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calles');
    }
};