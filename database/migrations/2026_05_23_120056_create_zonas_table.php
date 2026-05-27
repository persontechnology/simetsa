<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `zonas` — zonas tarifadas del SIMETSA.
 *
 * El SIMETSA inicia con una zona sobre la Parroquia San Miguel del Cantón
 * Salcedo (Art. 2, Art. 3), pero el sistema soporta múltiples zonas, ya
 * que la Ordenanza prevé que sean "susceptibles de ampliarse o reducirse".
 *
 * La geometría se almacena como JSON (arreglo de pares [lat, lng]) en una
 * columna jsonb, sin requerir PostGIS. Cada zona guarda además su centro
 * y nivel de zoom para visualización en Leaflet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();

            // Centro de la zona para centrar el mapa (coordenadas de Salcedo)
            $table->decimal('centro_lat', 10, 7)->default(-1.0458000);
            $table->decimal('centro_lng', 10, 7)->default(-78.5916000);
            $table->unsignedTinyInteger('zoom')->default(16);

            // Polígono del límite: arreglo JSON de pares [lat, lng]
            $table->jsonb('poligono')->nullable();

            $table->string('color', 7)->default('#0d4a8f'); // hex para el mapa
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas');
    }
};