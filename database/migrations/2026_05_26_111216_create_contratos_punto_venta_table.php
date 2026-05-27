<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos_punto_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('punto_venta_id')->unique()->constrained('puntos_venta')->cascadeOnDelete();
            $table->string('numero_contrato');
            $table->date('fecha_firma');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('porcentaje_descuento', 5, 2)->default(10);   // Art. 31 / Art. 21
            $table->string('elaborado_por')->nullable();                   // Procuraduría Síndica
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos_punto_venta');
    }
};