<?php

// database/migrations/2026_05_29_220001_add_estado_reembolso_to_cancelaciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 6.C: agrega estado_reembolso a cancelaciones.
 *
 * Registros existentes: todos quedan 'no_aplica' (efectivo/simulado — correcto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cancelaciones', function (Blueprint $table) {
            $table->string('estado_reembolso', 20)
                ->default('no_aplica')
                ->after('monto_reembolsado');
        });
    }

    public function down(): void
    {
        Schema::table('cancelaciones', function (Blueprint $table) {
            $table->dropColumn('estado_reembolso');
        });
    }
};
