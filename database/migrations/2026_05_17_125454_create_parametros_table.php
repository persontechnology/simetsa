<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla `parametros`.
 *
 * Almacena los parámetros globales del SIMETSA como un key-value store
 * tipado. Cada parámetro pertenece a una categoría operativa, declara
 * su artículo de origen en la Ordenanza, y especifica su tipo de dato
 * para parseo correcto al leerlo.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('parametros', function (Blueprint $table) {
            $table->id();

            // Identificador único del parámetro (snake_case)
            $table->string('clave', 100)->unique();

            // El valor SIEMPRE se almacena como texto; el tipo se interpreta al leer
            $table->text('valor');

            // Tipo de dato para parsear `valor` correctamente
            $table->enum('tipo', ['string', 'integer', 'decimal', 'boolean'])
                  ->default('string');

            // Agrupación lógica para la UI (operacion, liquidaciones, multas, etc.)
            $table->string('categoria', 50)->default('general')->index();

            $table->string('descripcion', 255)->nullable();

            // Trazabilidad legal: artículo de la Ordenanza que lo origina
            $table->string('articulo_ordenanza', 50)->nullable();

            // Si false, no se permite editar desde la UI (reservados para futuros
            // parámetros críticos del sistema, ej: tokens, secrets)
            $table->boolean('editable')->default(true);

            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros');
    }
};