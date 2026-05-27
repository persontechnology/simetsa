<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inscripciones_curso')) {
            return;
        }

        Schema::table('inscripciones_curso', function (Blueprint $table) {
            $table->dropUnique(['curso_capacitacion_id', 'solicitud_agente_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX inscripciones_curso_curso_capacitacion_id_solicitud_agente_id_unique '
                . 'ON inscripciones_curso (curso_capacitacion_id, solicitud_agente_id) '
                . 'WHERE deleted_at IS NULL'
            );
        } else {
            Schema::table('inscripciones_curso', function (Blueprint $table) {
                $table->unique(['curso_capacitacion_id', 'solicitud_agente_id']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('inscripciones_curso')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS inscripciones_curso_curso_capacitacion_id_solicitud_agente_id_unique');
        } else {
            Schema::table('inscripciones_curso', function (Blueprint $table) {
                $table->dropUnique(['curso_capacitacion_id', 'solicitud_agente_id']);
            });
        }

        Schema::table('inscripciones_curso', function (Blueprint $table) {
            $table->unique(['curso_capacitacion_id', 'solicitud_agente_id']);
        });
    }
};
