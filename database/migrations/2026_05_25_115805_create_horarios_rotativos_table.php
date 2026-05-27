<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `horarios_rotativos` — turnos rotativos del agente (Art. 37.4).
 *
 * La Comisaría elabora los horarios rotativos del sistema (Art. 37.4). Cada
 * registro fija el día y la franja horaria en que un agente atiende una zona,
 * con periodo de vigencia. El SIMETSA opera mar-vie y domingo, 08:00-18:00
 * (Art. 12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_rotativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_parqueo_id')->constrained('agentes_parqueo')->cascadeOnDelete();
            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 0=domingo ... 6=sábado
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agente_parqueo_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_rotativos');
    }
};