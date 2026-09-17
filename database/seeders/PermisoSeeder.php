<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permiso;

class PermisoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Usuarios
            ['nombre' => 'Ver Usuarios', 'slug' => 'usuarios.index'],
            ['nombre' => 'Crear Usuarios', 'slug' => 'usuarios.create'],
            ['nombre' => 'Editar Usuarios', 'slug' => 'usuarios.edit'],
            ['nombre' => 'Eliminar Usuarios', 'slug' => 'usuarios.delete'],

            // Aulas (Salas)
            ['nombre' => 'Ver Aulas', 'slug' => 'aulas.index'],
            ['nombre' => 'Crear Aulas', 'slug' => 'aulas.create'],
            ['nombre' => 'Editar Aulas', 'slug' => 'aulas.edit'],
            ['nombre' => 'Eliminar Aulas', 'slug' => 'aulas.delete'],

            // Horarios
            ['nombre' => 'Ver Horarios', 'slug' => 'horarios.index'],
            ['nombre' => 'Crear Horarios', 'slug' => 'horarios.create'],
            ['nombre' => 'Editar Horarios', 'slug' => 'horarios.edit'],
            ['nombre' => 'Eliminar Horarios', 'slug' => 'horarios.delete'],

            // Cursos
            ['nombre' => 'Ver Cursos', 'slug' => 'cursos.index'],
            ['nombre' => 'Crear Cursos', 'slug' => 'cursos.create'],
            ['nombre' => 'Editar Cursos', 'slug' => 'cursos.edit'],
            ['nombre' => 'Eliminar Cursos', 'slug' => 'cursos.delete'],

            // Grupos
            ['nombre' => 'Ver Grupos', 'slug' => 'grupos.index'],
            ['nombre' => 'Crear Grupos', 'slug' => 'grupos.create'],
            ['nombre' => 'Editar Grupos', 'slug' => 'grupos.edit'],
            ['nombre' => 'Eliminar Grupos', 'slug' => 'grupos.delete'],

            // Inscripciones
            ['nombre' => 'Ver Inscripciones', 'slug' => 'inscripciones.index'],
            ['nombre' => 'Crear Inscripciones', 'slug' => 'inscripciones.create'],
            ['nombre' => 'Editar Inscripciones', 'slug' => 'inscripciones.edit'],
            ['nombre' => 'Eliminar Inscripciones', 'slug' => 'inscripciones.delete'],

            // Reportes
            ['nombre' => 'Ver Reportes', 'slug' => 'reportes.index'],
            ['nombre' => 'Ver Reportes de Acceso', 'slug' => 'reportes.acceso'],

            // Bitácora
            ['nombre' => 'Ver Bitácora', 'slug' => 'bitacora.index'],
        ];

        // Soft delete obsolete permissions
        Permiso::whereIn('slug', [
            'mensualidades.index', 'mensualidades.create', 'mensualidades.edit', 'mensualidades.delete',
            'seguimiento.index', 'reportes.negocio'
        ])->update(['eliminado' => true]);

        foreach ($permisos as $permiso) {
            Permiso::updateOrCreate(
                ['slug' => $permiso['slug']],
                ['nombre' => $permiso['nombre'], 'eliminado' => false]
            );
        }
    }
}
