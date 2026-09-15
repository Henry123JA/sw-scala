<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nombre' => 'Propietario',
                'descripcion' => 'Acceso total (es el administrador del sistema)',
            ],
            [
                'nombre' => 'Secretaria',
                'descripcion' => 'Gestión operativa completa de todos los módulos',
            ],
            [
                'nombre' => 'Docente',
                'descripcion' => 'Solo lectura: sus grupos, horarios, alumnos inscritos',
            ],
            [
                'nombre' => 'Alumno',
                'descripcion' => 'Solo lectura: sus inscripciones y mensualidades',
            ],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(
                ['nombre' => $rol['nombre']],
                ['descripcion' => $rol['descripcion'], 'eliminado' => false]
            );
        }
    }
}
