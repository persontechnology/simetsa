<?php

// database/migrations/2026_05_29_100101_create_dispositivo_movils_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dispositivos móviles registrados para recibir notificaciones push vía FCM.
 * La integración real con Firebase Cloud Messaging se realiza en Fase 6.
 * En Fase 5, esta tabla persiste los tokens para tenerlos listos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos_moviles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            /** Token FCM del dispositivo. */
            $table->string('token_fcm', 512);

            /** ios | android */
            $table->string('plataforma', 10);

            $table->boolean('activo')->default(true);

            $table->timestamp('ultimo_uso_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
        });

        // Un usuario puede tener múltiples dispositivos, pero no el mismo token duplicado
        DB::statement(
            "CREATE UNIQUE INDEX dispositivos_moviles_user_token ON dispositivos_moviles(user_id, token_fcm)"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos_moviles');
    }
};
