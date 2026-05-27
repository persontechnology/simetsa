<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `expedientes_agente` — expediente personal del agente (Art. 40).
 *
 * Relación 1:1 con el agente. Las amonestaciones y demás registros (3.D)
 * referencian al agente y se muestran agregados en su expediente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes_agente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_parqueo_id')->unique()
                  ->constrained('agentes_parqueo')->cascadeOnDelete();
            $table->text('observaciones')->nullable();
            $table->date('fecha_apertura')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes_agente');
    }
};