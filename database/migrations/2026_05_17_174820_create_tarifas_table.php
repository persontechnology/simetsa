<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `tarifas` — historial parametrizable de tarifas por tipo de plaza.
 *
 * Reglas:
 *  - Cada tarifa tiene un rango de vigencia [vigente_desde, vigente_hasta].
 *  - `vigente_hasta = NULL` indica "vigente hasta nuevo aviso" (la actual).
 *  - No deben solaparse rangos activos para el mismo tipo_plaza_id.
 *  - Soft delete: las tarifas viejas se conservan para reportes retroactivos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tipo_plaza_id')
                  ->constrained('tipos_plaza')
                  ->cascadeOnDelete();

            $table->string('nombre', 100);
            $table->decimal('valor_hora', 8, 4);

            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();

            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_plaza_id', 'vigente_desde']);
            $table->index(['tipo_plaza_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};