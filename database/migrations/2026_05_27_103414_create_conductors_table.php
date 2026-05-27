<?php
// database/migrations/2026_05_27_000001_create_conductores_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla conductores — registro 1:1 del conductor con su cuenta de usuario (Fase 4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conductores', function (Blueprint $table) {
            $table->id();
            // 1:1 con users; si se borra el usuario, se borra el conductor
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('codigo')->unique();              // CD-00001
            $table->string('estado')->default('activo');     // activo | bloqueado
            $table->timestamps();
            $table->softDeletes();                           // retención de datos (LOPDP)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conductores');
    }
};