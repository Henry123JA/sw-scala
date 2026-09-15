<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Menu;
use App\Models\RolMenu;

class RolMenuSeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            'Propietario' => [
                '/dashboard',
                '/usuarios',
                '/aulas',
                '/cursos',
                '/grupos',
                '/horarios',
                '/inscripciones',
                '/mensualidades',
                '/seguimiento',
                '/reportes',
                '/reportes/negocio',
                '/reportes/acceso',
                '/bitacora',
            ],
            'Secretaria' => [
                '/aulas',
                '/cursos',
                '/grupos',
                '/horarios',
                '/inscripciones',
                '/mensualidades',
                '/seguimiento',
            ],
            'Docente' => [
                '/grupos',
                '/horarios',
            ],
            'Alumno' => [
                '/inscripciones',
                '/mensualidades',
                '/seguimiento',
            ],
        ];

        foreach ($mappings as $rolNombre => $rutas) {
            $rol = Rol::where('nombre', $rolNombre)->first();
            if (!$rol) {
                continue;
            }

            // Sync menu assignments
            $menuIds = Menu::whereIn('ruta', $rutas)->pluck('id');

            // Sync: reemplaza TODAS las asignaciones del rol con las nuevas rutas
            // (elimina las viejas, agrega las que falten)
            RolMenu::where('rol_id', $rol->id)->delete();
            foreach ($menuIds as $menuId) {
                RolMenu::create([
                    'rol_id' => $rol->id,
                    'menu_id' => $menuId,
                ]);
            }
        }
    }
}
