<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotent: uses firstOrCreate so it is safe to run multiple times.
     */
    public function run(): void
    {
        $especialidades = [
            'Piano',
            'Guitarra',
            'Violín',
            'Batería',
            'Canto',
            'Flauta',
        ];

        foreach ($especialidades as $nombre) {
            Especialidad::firstOrCreate(
                ['nombre' => $nombre],
                ['eliminado' => false]
            );
        }
    }
}
