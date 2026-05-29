<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         $this->call([
            // Fase 1
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,

            // Fase 2
            ParametroSeeder::class,
            TipoPlazaSeeder::class,
            DiaFeriadoSeeder::class,
            HorarioOperacionSeeder::class,
            TarifaSeeder::class,
            ZonaSeeder::class,
            CalleSeeder::class,
            ManzanaSeeder::class,
            PlazaSeeder::class,
            SolicitudAgenteSeeder::class,
            CursoCapacitacionSeeder::class,
            AgenteParqueoSeeder::class,
            OperacionAgenteSeeder::class,
            SolicitudPuntoVentaSeeder::class,
            PuntoVentaSeeder::class,
            // Fase 4
            TipoVehiculoSeeder::class,
            ConductorSeeder::class,
        ]);
    }
}
