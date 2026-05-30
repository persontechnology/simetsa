<?php

// database/migrations/2026_05_30_095728_create_infracciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de infracciones a la Ordenanza SIMETSA.
 * Arts. 17, 18, 28, 29, 30.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infracciones', function (Blueprint $table) {
            $table->id();

            // Vehículo infraccionado — placa obligatoria; conductor puede no estar registrado
            $table->string('placa', 10);
            $table->foreignId('conductor_id')
                ->nullable()
                ->constrained('conductores')
                ->nullOnDelete();

            // Contexto espacial de la infracción
            $table->foreignId('zona_id')->constrained('zonas');
            $table->foreignId('calle_id')
                ->nullable()
                ->constrained('calles')
                ->nullOnDelete();

            // Agente que registra la infracción (Art. 38.l)
            $table->foreignId('agente_parqueo_id')->constrained('agentes_parqueo');

            // Ticket relacionado si la infracción deriva de uno (e.g. tiempo excedido)
            $table->foreignId('ticket_id')
                ->nullable()
                ->constrained('tickets')
                ->nullOnDelete();

            // Clasificación y sanción
            $table->string('tipo_infraccion');
            $table->string('estado')->default('pendiente');
            $table->decimal('monto_multa', 8, 2)->default(0.00);
            $table->decimal('sbu_vigente', 8, 2);

            // Minutos excedidos — requerido para TiempoExcedido (Art. 28, tabla escalonada)
            $table->unsignedSmallInteger('minutos_excedidos')->nullable();

            // Datos de campo
            $table->text('descripcion')->nullable();
            $table->string('foto_evidencia')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();

            // Trazabilidad de anulación
            $table->text('motivo_anulacion')->nullable();
            $table->foreignId('anulada_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('anulada_en')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('placa');
            $table->index('estado');
            $table->index('tipo_infraccion');
            $table->index('agente_parqueo_id');
            $table->index('zona_id');
            $table->index(['placa', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infracciones');
    }
};
