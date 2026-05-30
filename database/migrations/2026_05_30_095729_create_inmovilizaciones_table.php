<?php

// database/migrations/2026_05_30_095729_create_inmovilizaciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de inmovilizaciones (candado) de vehículos infraccionados.
 * Art. 15: queda sin efecto al pagar la infracción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmovilizaciones', function (Blueprint $table) {
            $table->id();

            // Relación 1:1 con la infracción que la origina
            $table->foreignId('infraccion_id')
                ->unique()
                ->constrained('infracciones')
                ->cascadeOnDelete();

            // Agente que coloca el candado (puede diferir del que registró la infracción)
            $table->foreignId('agente_parqueo_id')->constrained('agentes_parqueo');

            $table->string('estado')->default('activa');

            $table->string('foto_candado')->nullable();
            $table->text('notas')->nullable();

            $table->timestamp('inmovilizada_en');
            $table->timestamp('liberada_en')->nullable();

            // Trazabilidad de anulación administrativa
            $table->text('motivo_anulacion')->nullable();
            $table->foreignId('anulada_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmovilizaciones');
    }
};
