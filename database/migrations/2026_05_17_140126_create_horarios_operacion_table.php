<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `horarios_operacion` — horario semanal del SIMETSA.
 *
 * Exactamente 7 registros, uno por cada día de la semana (0=domingo … 6=sábado).
 * El registro define si el día opera (activo) y, si opera, las horas
 * de inicio y fin. Conforme al Art. 12 de la Ordenanza:
 * martes-viernes y domingo, 08:00-18:00.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_operacion', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('dia_semana')->unique();   // 0=dom … 6=sáb
            $table->time('hora_inicio')->default('08:00');
            $table->time('hora_fin')->default('18:00');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_operacion');
    }
};