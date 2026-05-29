<?php

// database/migrations/2026_05_29_100102_create_notificacion_pushes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cola lógica de notificaciones push pendientes de envío.
 * En Fase 5 se encolan las intenciones; el envío real a FCM se activa en Fase 6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_push', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            /** Ticket relacionado con la notificación (si aplica). */
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();

            /**
             * Tipo de notificación:
             * ticket_expira_pronto | ticket_expirado | ticket_anulado
             */
            $table->string('tipo', 50);

            /** Payload JSON con los datos a enviar al dispositivo. */
            $table->json('payload');

            /** Momento programado para el envío. */
            $table->timestamp('programado_para');

            $table->boolean('enviada')->default(false);
            $table->timestamp('enviada_en')->nullable();

            $table->timestamps();

            $table->index(['enviada', 'programado_para']);
            $table->index('user_id');
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_push');
    }
};
