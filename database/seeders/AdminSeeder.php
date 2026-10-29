<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $rol = Rol::where('nombre', 'Propietario')->first();

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
                'eliminado' => false,
            ]
        );
    }
}
