<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Collection;

class MenuService
{
    /**
     * Obtiene los elementos del menú autorizados para un rol específico en estructura de árbol.
     */
    public function getMenuPorRol(?int $rolId): Collection
    {
        if (!$rolId) {
            return collect();
        }

        // Obtener todos los menús asociados al rol (filtrados por el scope global 'activo' por defecto)
        $menus = Menu::whereHas('roles', function ($query) use ($rolId) {
            $query->where('rol_id', $rolId);
        })->orderBy('orden')->get();

        // Agrupar los menús en memoria para estructurar el árbol sin consultas N+1
        $grouped = $menus->groupBy('padre_id');

        // Los menús raíces son aquellos cuyo padre_id es null
        $roots = $grouped->get(null) ?: collect();

        // Para cada raíz, asignamos sus submenús (hijos)
        $roots->each(function ($menu) use ($grouped) {
            $menu->setRelation('hijos', $grouped->get($menu->id) ?: collect());
        });

        return $roots;
    }
}
