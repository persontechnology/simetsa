<?php

namespace Database\Seeders;

use App\Models\Infraccion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InfraccionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Infraccion::factory()->count(20)->create();
    }
}
