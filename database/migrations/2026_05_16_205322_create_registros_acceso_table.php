<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla `registros_acceso`.
 *
 * Registra cada evento de autenticación del sistema (login exitoso,
 * logout, intento fallido, bloqueo por throttling). Diseñada como
 * tabla append-only para fines de auditoría LOPDP.
 *
 * Campos relevantes:
 *  - user_id (nullable): null en intentos fallidos sin usuario coincidente
 *    o si el usuario fue eliminado posteriormente (ON DELETE SET NULL).
 *  - email_intento: email digitado por el usuario al intentar autenticarse;
 *    crítico para investigar intentos de acceso fallidos a cuentas inexistentes.
 *  - evento: tipo discriminador ('login', 'logout', 'fallido', 'bloqueo').
 *  - ocurrido_en: separado de created_at para precisión semántica.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('registros_acceso', function (Blueprint $table) {
            $table->id();

            // Nullable porque en Failed el usuario puede no existir,
            // y nullOnDelete preserva el log si el usuario es eliminado.
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Email digitado en el intento (útil para Failed y Lockout)
            $table->string('email_intento', 255)->nullable();

            // Discriminador del tipo de evento
            $table->enum('evento', ['login', 'logout', 'fallido', 'bloqueo']);

            // Contexto de la petición
            $table->ipAddress('ip')->nullable();        // soporta IPv4 e IPv6
            $table->text('user_agent')->nullable();     // text para UAs largos

            // Momento exacto del evento
            $table->timestamp('ocurrido_en')->useCurrent();

            $table->timestamps();

            // Índices para los filtros más comunes del backoffice
            $table->index(['evento', 'ocurrido_en']);
            $table->index('ocurrido_en');
            $table->index('email_intento');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_acceso');
    }
};