<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            // TemaSeeder desacoplado: tema unificado en frontend (Nota Menta Claro)
            PermisoSeeder::class,
            // MenuSeeder y RolMenuSeeder desacoplados de la siembra principal
            EspecialidadSeeder::class,
            AdminSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
