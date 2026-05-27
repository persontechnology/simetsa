<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `parametros_bitacora` — auditoría de cambios sobre Parametro.
 *
 * Una fila por cada campo modificado en cada update. Append-only:
 * no se actualiza ni se elimina (auditoría LOPDP). Cuando un usuario
 * cambia tanto `valor` como `descripcion` en un mismo guardado, se
 * crean DOS filas (una por campo).
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('parametros_bitacora', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parametro_id')
                  ->constrained('parametros')
                  ->cascadeOnDelete();

            // Quién hizo el cambio. Null si fue cambio automático (seed, job, etc.)
            // o si el usuario fue eliminado posteriormente.
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Campo modificado: 'valor' o 'descripcion'
            $table->string('campo', 50);

            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();

            $table->ipAddress('ip')->nullable();
            $table->timestamp('ocurrido_en')->useCurrent();

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['parametro_id', 'ocurrido_en']);
            $table->index('user_id');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros_bitacora');
    }
};