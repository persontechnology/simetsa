<?php

// database/migrations/2026_05_29_100054_create_tickets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ticket digital de parqueo tarifado (sustituto digital de las "especies valoradas", Art. 19).
 *
 * Un ticket representa la compra del derecho a estacionar por N horas
 * en una zona determinada. Costo: $0.25/hora o fracción (Art. 22).
 * Tiempo máximo: 2 horas (Art. 14). Exoneraciones: Art. 26 y 27.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            /** Código único legible: T-YYYY-NNNNN (ej: T-2026-00001). */
            $table->string('codigo', 20)->unique();

            $table->foreignId('conductor_id')->constrained('conductores');
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('zona_id')->constrained('zonas');
            $table->foreignId('calle_id')->nullable()->constrained('calles')->nullOnDelete();

            /** Horas compradas: 1 o 2 (Art. 14 — máx 2h). */
            $table->unsignedTinyInteger('horas_compradas');

            /** Monto cobrado en dólares (Art. 22: $0.25/hora). */
            $table->decimal('monto', 8, 2);

            /**
             * Estado del ticket (App\Enums\EstadoTicket):
             * pendiente | activo | en_tolerancia | expirado | cancelado | anulado
             */
            $table->string('estado', 20)->default('pendiente');

            /**
             * Método de pago (App\Enums\MetodoPago):
             * efectivo | pago_simulado | link | qr | tarjeta | saldo
             */
            $table->string('metodo_pago', 30)->default('efectivo');

            /** True cuando el vehículo tiene exoneración de pago (Art. 26 o 27). */
            $table->boolean('es_exonerado')->default(false);

            /** conadis | institucional — presente solo si es_exonerado = true. */
            $table->string('tipo_exoneracion', 20)->nullable();

            /** Momento en que se realizó la compra. */
            $table->timestamp('comprado_en');

            /** Momento límite de vigencia (comprado_en + horas_compradas). */
            $table->timestamp('expira_en');

            $table->timestamps();
            $table->softDeletes();

            $table->index('conductor_id');
            $table->index('vehiculo_id');
            $table->index('zona_id');
            $table->index('estado');
            $table->index('comprado_en');
        });

        // Índice parcial para búsqueda eficiente de tickets vigentes sin examinar cancelados/anulados
        DB::statement(
            "CREATE INDEX tickets_vigentes_idx ON tickets(vehiculo_id, estado) WHERE estado IN ('pendiente','activo','en_tolerancia') AND deleted_at IS NULL"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
