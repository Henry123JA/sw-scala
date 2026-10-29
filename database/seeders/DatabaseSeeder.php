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
            PermisoSeeder::class,
            MenuSeeder::class, // Catálogo de rutas para auditoría y 'pagina_visitada'
            // RolMenuSeeder desacoplado: la navegación visual ahora se gestiona en frontend (navigation.js)
            EspecialidadSeeder::class,
            AdminSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
