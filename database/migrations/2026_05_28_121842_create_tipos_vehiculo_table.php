<?php
// database/migrations/2026_05_28_121842_create_tipos_vehiculo_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla tipos_vehiculo — catálogo de categorías de vehículos (Art. 25 Ordenanza SIMETSA).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();      // liviano_privado, taxi, etc.
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('aplica_tarifa')->default(true);  // Art. 25
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_vehiculo');
    }
};
