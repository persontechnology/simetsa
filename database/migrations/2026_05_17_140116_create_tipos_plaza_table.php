<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `tipos_plaza` — catálogo de tipos de plaza de estacionamiento.
 *
 * Tipos base según la Ordenanza:
 *  - normal:        plaza estándar pagada $0.25/h (Art. 22, Art. 25).
 *  - discapacidad:  exonerada para personas con credencial CONADIS (Art. 26).
 *  - taxi:          cooperativas de taxis y camionetas (Art. 8 - pagan por otra ordenanza).
 *  - carga:         vehículos de carga liviana para carga/descarga (Art. 25).
 *  - autoridad:     ambulancias, bomberos, policía, FF.AA. exonerados (Art. 27).
 *
 * Soft deletes: una vez referenciado por Plazas, no se puede eliminar
 * físicamente sin perder integridad histórica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_plaza', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();              // snake_case estable
            $table->string('nombre', 100);                        // etiqueta legible
            $table->text('descripcion')->nullable();
            $table->boolean('requiere_credencial')->default(false);  // discapacidad, autoridad
            $table->boolean('es_pagado')->default(true);             // false para exonerados
            $table->string('color_mapa', 7)->default('#1d6fb8');     // hex para Leaflet
            $table->string('icono', 50)->nullable();                 // Bootstrap Icons
            $table->boolean('activo')->default(true);
            $table->decimal('ancho_sugerido', 4, 2)->nullable();
            $table->decimal('largo_sugerido', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Backfill de los tipos estándar existentes (Art. 5: tipos del SIMETSA)
        $defaults = [
            'normal'       => ['ancho' => 2.40, 'largo' => 5.00],
            'discapacidad' => ['ancho' => 2.50, 'largo' => 5.00],
            'taxi'         => ['ancho' => 2.40, 'largo' => 5.50],
            'carga'        => ['ancho' => 2.50, 'largo' => 8.00],
            'autoridad'    => ['ancho' => 2.40, 'largo' => 5.50],
        ];
        foreach ($defaults as $codigo => $dimensiones) {
            DB::table('tipos_plaza')->where('codigo', $codigo)->update([
                'ancho_sugerido' => $dimensiones['ancho'],
                'largo_sugerido' => $dimensiones['largo'],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_plaza');
    }
};