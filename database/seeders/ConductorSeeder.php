<?php

namespace Database\Seeders;

use App\Models\Conductor;
use Illuminate\Database\Seeder;

class ConductorSeeder extends Seeder
{
    /**
     * Seed the application's database with conductores.
     */
    public function run(): void
    {
        Conductor::factory()->count(10)->create();
    }
}
