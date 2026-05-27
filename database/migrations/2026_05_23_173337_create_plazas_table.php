<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `plazas` — espacios individuales de estacionamiento.
 *
 * Cada plaza pertenece a una zona y un tipo de plaza (obligatorios), y
 * opcionalmente a una calle y una manzana. Se ubica en un punto geográfico
 * (lat/lng) y tiene un ancho señalizado entre 2.20 y 2.50 m (Art. 6) y una
 * orientación respecto a la acera (Art. 5).
 *
 * La ocupación (libre/ocupada) NO se modela aquí: es estado de operación
 * que se resolverá en la Fase 5 mediante las sesiones de parqueo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plazas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('calle_id')->nullable()->constrained('calles')->nullOnDelete();
            $table->foreignId('manzana_id')->nullable()->constrained('manzanas')->nullOnDelete();
            $table->foreignId('tipo_plaza_id')->constrained('tipos_plaza'); // restrict por defecto

            $table->string('codigo', 30)->unique();
            $table->string('numero', 20)->nullable(); // número visible en señalización

            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();

            $table->decimal('ancho_metros', 4, 2)->nullable(); // Art. 6: 2.20-2.50
            $table->decimal('largo_metros', 5, 2)->nullable();
            $table->enum('orientacion', ['paralelo', 'perpendicular', 'bandera'])
                  ->default('paralelo');

            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['zona_id', 'activo']);
            $table->index('calle_id');
            $table->index('tipo_plaza_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plazas');
    }
};