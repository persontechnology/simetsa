<?php

// database/migrations/2026_05_29_200001_add_proveedor_to_tickets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 6.0: separa el "proveedor gateway" del "medio de pago" en tickets.
 *
 * Datos existentes:
 *   efectivo    → proveedor = 'none'
 *   pago_simulado → proveedor = 'manual'
 *   payphone    → metodo_pago = 'link', proveedor = 'deuna'
 *                 (no existen registros reales; Payphone nunca se integró)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('proveedor', 20)->default('none')->after('metodo_pago');
        });

        // Migrar datos existentes preservando semántica
        DB::statement("UPDATE tickets SET proveedor = 'manual' WHERE metodo_pago = 'pago_simulado'");
        DB::statement("UPDATE tickets SET proveedor = 'none'   WHERE metodo_pago = 'efectivo'");

        // Registros legacy de PayPhone (improbables en producción; seguridad por si acaso)
        DB::statement("UPDATE tickets SET metodo_pago = 'link', proveedor = 'deuna' WHERE metodo_pago = 'payphone'");

        Schema::table('tickets', function (Blueprint $table) {
            $table->index('proveedor', 'tickets_proveedor_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_proveedor_idx');
            $table->dropColumn('proveedor');
        });
    }
};
