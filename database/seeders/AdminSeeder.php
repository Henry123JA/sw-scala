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

        // Si existía con el correo anterior (admin@academia.com o código ADM-001), lo actualizamos al nuevo correo oficial
        $adminExistente = Usuario::withoutGlobalScopes()
            ->where('codigo', 'ADM-001')
            ->orWhere('email', 'admin@academia.com')
            ->orWhere('email', 'inmobiliaria2024inmoviva@gmail.com')
            ->first();

        if ($adminExistente) {
            $adminExistente->update([
                'nombres'   => 'Admin',
                'apellidos' => 'Propietario',
                'ci'        => '1234567',
                'codigo'    => 'ADM-001',
                'email'     => 'inmobiliaria2024inmoviva@gmail.com',
                'password'  => Hash::make('123456'),
                'rol_id'    => $rol->id,
                'eliminado' => false,
            ]);
        } else {
            Usuario::create([
                'nombres'   => 'Admin',
                'apellidos' => 'Propietario',
                'ci'        => '1234567',
                'codigo'    => 'ADM-001',
                'email'     => 'inmobiliaria2024inmoviva@gmail.com',
                'password'  => Hash::make('123456'),
                'rol_id'    => $rol->id,
                'eliminado' => false,
            ]);
        }
    }
}
