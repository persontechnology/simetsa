<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_punto_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_punto_venta_id')->constrained('solicitudes_punto_venta')->cascadeOnDelete();
            $table->enum('tipo', ['solicitud_alcalde', 'cedula', 'no_adeudar', 'patente_municipal', 'otro']);
            $table->string('nombre_archivo');
            $table->string('ruta_archivo');
            $table->boolean('validado')->default(false);
            $table->string('observacion')->nullable();
            $table->timestamp('fecha_validacion')->nullable();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_punto_venta');
    }
};