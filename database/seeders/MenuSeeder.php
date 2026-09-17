<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // First, seed root menus
        $rootMenus = [
            [
                'nombre' => 'Dashboard',
                'ruta' => '/dashboard',
                'icono' => 'LayoutDashboard',
                'orden' => 1,
            ],
            [
                'nombre' => 'Usuarios',
                'ruta' => '/usuarios',
                'icono' => 'Users',
                'orden' => 2,
            ],
            [
                'nombre' => 'Aulas',
                'ruta' => '/aulas',
                'icono' => 'DoorOpen',
                'orden' => 3,
            ],
            [
                'nombre' => 'Cursos',
                'ruta' => '/cursos',
                'icono' => 'BookOpen',
                'orden' => 4,
            ],
            [
                'nombre' => 'Grupos',
                'ruta' => '/grupos',
                'icono' => 'Folder',
                'orden' => 5,
            ],
            [
                'nombre' => 'Horarios',
                'ruta' => '/horarios',
                'icono' => 'Clock',
                'orden' => 6,
            ],
            [
                'nombre' => 'Inscripciones',
                'ruta' => '/inscripciones',
                'icono' => 'UserPlus',
                'orden' => 7,
            ],
            [
                'nombre' => 'Reportes',
                'ruta' => '/reportes',
                'icono' => 'FileText',
                'orden' => 8,
            ],
            [
                'nombre' => 'Bitácora',
                'ruta' => '/bitacora',
                'icono' => 'History',
                'orden' => 9,
            ],
        ];

        // Soft delete obsolete menus
        Menu::whereIn('ruta', ['/mensualidades', '/seguimiento', '/reportes/negocio'])->update(['eliminado' => true]);

        $menuIds = [];
        foreach ($rootMenus as $m) {
            $menu = Menu::updateOrCreate(
                ['ruta' => $m['ruta']],
                [
                    'nombre' => $m['nombre'],
                    'icono' => $m['icono'],
                    'orden' => $m['orden'],
                    'padre_id' => null,
                    'eliminado' => false,
                ]
            );
            $menuIds[$m['nombre']] = $menu->id;
        }

        // Now seed submenu items
        $subMenus = [
            [
                'nombre' => 'Reporte de Acceso',
                'ruta' => '/reportes/acceso',
                'icono' => 'ShieldAlert',
                'orden' => 1,
                'padre' => 'Reportes',
            ],
        ];

        foreach ($subMenus as $sm) {
            $padreId = $menuIds[$sm['padre']] ?? null;
            Menu::updateOrCreate(
                ['ruta' => $sm['ruta']],
                [
                    'nombre' => $sm['nombre'],
                    'icono' => $sm['icono'],
                    'orden' => $sm['orden'],
                    'padre_id' => $padreId,
                    'eliminado' => false,
                ]
            );
        }
    }
}
