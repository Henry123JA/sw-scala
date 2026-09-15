<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Menu;
use App\Models\PaginaVisitada;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class BitacoraService
{
    /**
     * Registra un evento en la bitácora.
     */
    public function registrar(?int $usuarioId, string $accion, ?string $recurso, ?string $metodo, string $estado, ?string $ip): Bitacora
    {
        return Bitacora::create([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'recurso' => $recurso,
            'metodo' => $metodo,
            'estado' => $estado,
            'ip' => $ip,
            'fecha' => now(),
        ]);
    }

    /**
     * List all log entries paginated by 50 items.
     */
    public function listar(array $filters = []): LengthAwarePaginator
    {
        $query = Bitacora::query()->with('usuario');

        // Filter by state
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        // Filter by user search (names, email)
        if (!empty($filters['usuario'])) {
            $searchUser = $filters['usuario'];
            $query->whereHas('usuario', function ($q) use ($searchUser) {
                $q->where('nombres', 'like', "%{$searchUser}%")
                  ->orWhere('apellidos', 'like', "%{$searchUser}%")
                  ->orWhere('email', 'like', "%{$searchUser}%");
            });
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $query->where('fecha', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('fecha', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        // Search text: action, resource, ip
        if (!empty($filters['buscar'])) {
            $search = $filters['buscar'];
            $query->where(function ($q) use ($search) {
                $q->where('accion', 'like', "%{$search}%")
                  ->orWhere('recurso', 'like', "%{$search}%")
                  ->orWhere('ip', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('fecha', 'desc')->paginate(50);
    }

    /**
     * Get the top N most accessed resources (paginas visitadas), aggregated by menu.
     */
    public function recursosMasAccedidos(?string $desde = null, ?string $hasta = null, int $topN = 10): array
    {
        $query = PaginaVisitada::selectRaw('
                menu_id,
                SUM(contador) as total_visitas,
                MAX(ultima_visita) as ultima_visita
            ')
            ->whereHas('menu')
            ->groupBy('menu_id')
            ->orderByDesc('total_visitas')
            ->take($topN);

        if ($desde) {
            $query->where('ultima_visita', '>=', Carbon::parse($desde)->startOfDay());
        }
        if ($hasta) {
            $query->where('ultima_visita', '<=', Carbon::parse($hasta)->endOfDay());
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Eager load menu data
        $menuIds = $rows->pluck('menu_id');
        $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');

        return $rows->map(function ($row) use ($menus) {
            $menu = $menus->get($row->menu_id);
            return [
                'menu_id' => $row->menu_id,
                'menu_nombre' => $menu?->nombre ?? 'Desconocido',
                'menu_ruta' => $menu?->ruta ?? '',
                'menu_icono' => $menu?->icono ?? 'FileQuestion',
                'total_visitas' => (int) $row->total_visitas,
                'ultima_visita' => $row->ultima_visita?->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Get visit details for a specific resource (menu), grouped by user.
     */
    public function detalleRecurso(int $menuId, ?string $desde = null, ?string $hasta = null): ?array
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return null;
        }

        $query = PaginaVisitada::where('menu_id', $menuId)
            ->whereHas('usuario')
            ->with('usuario:id,nombres,apellidos,email');

        if ($desde) {
            $query->where('ultima_visita', '>=', Carbon::parse($desde)->startOfDay());
        }
        if ($hasta) {
            $query->where('ultima_visita', '<=', Carbon::parse($hasta)->endOfDay());
        }

        $rows = $query->orderByDesc('contador')->get();

        return [
            'menu_nombre' => $menu->nombre,
            'menu_ruta' => $menu->ruta,
            'visitas' => $rows->map(function ($row) {
                return [
                    'usuario_id' => $row->usuario_id,
                    'usuario_nombres' => $row->usuario->nombres,
                    'usuario_apellidos' => $row->usuario->apellidos,
                    'usuario_email' => $row->usuario->email,
                    'contador' => (int) $row->contador,
                    'ultima_visita' => $row->ultima_visita?->toISOString(),
                ];
            })->toArray(),
        ];
    }
}
