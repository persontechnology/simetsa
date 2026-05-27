<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_punto_venta', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();                 // SPV-XXXX
            $table->string('cedula', 10);
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('telefono', 20)->nullable();
            $table->string('telefono_celular', 20)->nullable();
            $table->string('email');
            $table->string('nombre_comercial');                 // nombre del local / punto de venta
            $table->string('ruc', 13)->nullable();
            $table->string('direccion')->nullable();            // domicilio del solicitante
            $table->string('direccion_local');                  // ubicación del punto de venta
            $table->string('referencia_ubicacion')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->enum('estado', ['documentacion', 'contrato', 'activa', 'rechazada'])->default('documentacion');
            $table->text('observaciones')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->date('fecha_solicitud');
            $table->foreignId('usuario_registro_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_punto_venta');
    }
};