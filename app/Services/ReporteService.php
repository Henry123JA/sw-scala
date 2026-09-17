<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Curso;
use App\Models\Inscripcion;
use Carbon\Carbon;

class ReporteService
{
    /**
     * Get access log metrics (bitacora) paginated.
     */
    public function getBitacoraLog(array $filters = [], bool $paginate = true)
    {
        $query = Bitacora::query()
            ->with('usuario');

        if (!empty($filters['usuario'])) {
            $search = $filters['usuario'];
            $query->whereHas('usuario', function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['accion'])) {
            $query->where('accion', 'like', "%{$filters['accion']}%");
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('fecha', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }

        if (!empty($filters['end_date'])) {
            $query->where('fecha', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        if ($paginate) {
            return $query->orderBy('fecha', 'desc')->paginate(15);
        }
        return $query->orderBy('fecha', 'desc')->get();
    }

    /**
     * Get dynamic metrics for the main landing dashboard (Ciclo 1 Académico).
     */
    public function obtenerEstadisticasDashboard(): array
    {
        $alumnosActivos = \App\Models\Alumno::where('estado', 'ACTIVO')
            ->whereHas('usuario', function ($q) {
                $q->where('eliminado', false);
            })->count();

        $gruposAbiertos = \App\Models\Grupo::where('estado', 'ACTIVO')
            ->where('eliminado', false)->count();

        $visitasGlobales = (int) \App\Models\PaginaVisitada::sum('contador');

        // ── Chart data ────────────────────────────────────────────────────

        // 1. Active students per course
        $alumnosPorCurso = Curso::query()
            ->get()
            ->map(function ($curso) {
                $count = Inscripcion::whereHas('grupo', function ($q) use ($curso) {
                    $q->where('curso_id', $curso->id);
                })->where('estado', 'ACTIVA')->count();
                return [
                    'nombre' => $curso->nombre,
                    'cantidad' => $count,
                ];
            })->filter(fn($c) => $c['cantidad'] > 0)->values()->toArray();

        // 2. New enrollments per month (last 12 months)
        $inscripciones12Meses = collect(range(0, 11))->map(function ($i) {
            $month = Carbon::now()->subMonths($i);
            $count = Inscripcion::whereBetween('fecha', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])->count();
            return [
                'mes' => $month->isoFormat('MMM YYYY'),
                'inscripciones' => $count,
            ];
        })->reverse()->values()->toArray();

        return [
            'alumnos_activos'        => $alumnosActivos,
            'grupos_abiertos'        => $gruposAbiertos,
            'visitas_globales'       => $visitasGlobales,
            'alumnos_por_curso'      => $alumnosPorCurso,
            'inscripciones_12_meses' => $inscripciones12Meses,
        ];
    }
}
