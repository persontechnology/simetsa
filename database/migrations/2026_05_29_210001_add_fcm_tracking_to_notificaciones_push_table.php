<?php

// database/migrations/2026_05_29_210001_add_fcm_tracking_to_notificaciones_push_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 6.B: agrega columnas de tracking al ciclo de vida del EnviarNotificacionFCMJob.
 *
 * - fallida_en:  momento en que el job falló definitivamente (todos los reintentos agotados).
 * - ultimo_error: mensaje del último error (truncado a 500 chars).
 * - omitida:     true cuando FCM_ENABLED=false y el job saltó el envío.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones_push', function (Blueprint $table) {
            $table->timestamp('fallida_en')->nullable()->after('enviada_en');
            $table->text('ultimo_error')->nullable()->after('fallida_en');
            $table->boolean('omitida')->default(false)->after('ultimo_error');
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones_push', function (Blueprint $table) {
            $table->dropColumn(['fallida_en', 'ultimo_error', 'omitida']);
        });
    }
};
