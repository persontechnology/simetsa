<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntos_venta', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();                 // PV-XXXX
            $table->foreignId('solicitud_punto_venta_id')->nullable()->constrained('solicitudes_punto_venta')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre_comercial');
            $table->string('direccion_local');
            $table->string('referencia_ubicacion')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->enum('estado', ['activo', 'suspendido', 'inactivo'])->default('activo');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos_venta');
    }
};