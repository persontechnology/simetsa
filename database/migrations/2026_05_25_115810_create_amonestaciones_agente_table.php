<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `amonestaciones_agente` — sanciones al agente (Art. 40).
 *
 * Escalada del Art. 40: 1.ª falta verbal, 2.ª escrita, 3.ª terminación de la
 * autorización. El tipo y el número de falta se calculan automáticamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amonestaciones_agente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_parqueo_id')->constrained('agentes_parqueo')->cascadeOnDelete();
            $table->enum('tipo', ['verbal', 'escrita', 'terminacion']);
            $table->unsignedTinyInteger('numero_falta');
            $table->text('motivo');
            $table->date('fecha');
            $table->foreignId('registrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('agente_parqueo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amonestaciones_agente');
    }
};