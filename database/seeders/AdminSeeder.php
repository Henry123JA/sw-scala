<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Tema;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $rol = Rol::where('nombre', 'Propietario')->first();
        $tema = Tema::where('nombre', 'adultos')->first();

        if (!$rol) {
            return;
        }

        Usuario::updateOrCreate(
            ['email' => 'admin@academia.com'],
            [
                'nombres' => 'Admin',
                'apellidos' => 'Propietario',
                'ci' => '1234567',
                'codigo' => 'ADM-001',
                'password' => Hash::make('123456'),
                'rol_id' => $rol->id,
                'tema_id' => $tema?->id,
                'eliminado' => false,
            ]
        );
    }
}
