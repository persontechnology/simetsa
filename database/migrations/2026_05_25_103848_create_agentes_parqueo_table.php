<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `agentes_parqueo` — agente de parqueo autorizado (Art. 36).
 *
 * Resultado de la Etapa 3: la Dirección de Seguridad Ciudadana autoriza al
 * postulante previo informe favorable del Comisario y carta compromiso firmada.
 * Se vincula a la solicitud de origen y a la cuenta de usuario del agente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentes_parqueo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();

            $table->foreignId('solicitud_agente_id')->nullable()
                  ->constrained('solicitudes_agente')->nullOnDelete();
            $table->foreignId('user_id')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->string('numero_credencial', 50)->nullable();      // Art. 38.c
            $table->string('numero_oficio_comisario', 100)->nullable(); // Art. 36 (informe favorable)
            $table->boolean('carta_compromiso_firmada')->default(false); // Art. 36
            $table->date('fecha_autorizacion')->nullable();

            $table->enum('estado', ['activo', 'suspendido', 'terminado'])->default('activo');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentes_parqueo');
    }
};