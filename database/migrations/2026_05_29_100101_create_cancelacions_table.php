<?php

// database/migrations/2026_05_29_100101_create_cancelacions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cancelaciones de tickets: cubre tanto la cancelación voluntaria del conductor
 * (antes de iniciar sesión) como la anulación administrativa del comisario.
 *
 * El discriminador 'tipo' (App\Enums\TipoCancelacion) distingue el actor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelaciones', function (Blueprint $table) {
            $table->id();

            /** Un ticket solo puede ser cancelado/anulado una vez. */
            $table->foreignId('ticket_id')->unique()->constrained('tickets');

            /** Usuario que ejecutó la acción (conductor o comisario/admin). */
            $table->foreignId('cancelado_por')->constrained('users');

            /**
             * Discriminador del actor (App\Enums\TipoCancelacion):
             * conductor | admin
             */
            $table->string('tipo', 20);

            $table->text('motivo');

            /** Monto a reembolsar (actualmente $0 — pagos en efectivo; relevante cuando se integre PayPhone). */
            $table->decimal('monto_reembolsado', 8, 2)->default(0);

            $table->timestamp('cancelado_en');

            $table->timestamps();

            $table->index('cancelado_por');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelaciones');
    }
};
