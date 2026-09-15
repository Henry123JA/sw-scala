<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Mensualidad;
use Carbon\Carbon;

class ReporteService
{
    /**
     * Get business metrics.
     */
    public function getMetodosNegocio(array $filters = []): array
    {
        $startDate = !empty($filters['start_date']) 
            ? Carbon::parse($filters['start_date'])->startOfDay() 
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = !empty($filters['end_date']) 
            ? Carbon::parse($filters['end_date'])->endOfDay() 
            : Carbon::now()->endOfMonth()->endOfDay();

        // 1. Total income (sum of PAGADO bills in range by fecha_pago)
        $totalIncome = (float) Mensualidad::where('estado', 'PAGADO')
            ->whereBetween('fecha_pago', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('monto_base');

        // 2. Paid vs Pending vs Overdue
        // Pagados → filtrar por fecha_pago (cuándo se cobró, no cuándo vencía)
        $paidMetrics = Mensualidad::where('estado', 'PAGADO')
            ->whereBetween('fecha_pago', [$startDate->toDateString(), $endDate->toDateString()]);
        
        $paidCount = $paidMetrics->count();
        $paidSum = (float) $paidMetrics->sum('monto_base');

        // Pendientes y atrasados → filtrar por fecha_vencimiento (cuándo se debía pagar)
        $pendingMetrics = Mensualidad::whereIn('estado', ['PENDIENTE', 'PENDIENTE_PAGO_QR'])
            ->whereBetween('fecha_vencimiento', [$startDate->toDateString(), $endDate->toDateString()]);

        $pendingCount = $pendingMetrics->count();
        $pendingSum = (float) $pendingMetrics->sum('monto_base');

        $overdueMetrics = Mensualidad::where('estado', 'ATRASADO')
            ->whereBetween('fecha_vencimiento', [$startDate->toDateString(), $endDate->toDateString()]);

        $overdueCount = $overdueMetrics->count();
        $overdueSum = (float) $overdueMetrics->sum('monto_base');

        // 3. Active registrations per course
        $activeRegistrations = Curso::query()
            ->get()
            ->map(function ($curso) {
                $count = Inscripcion::whereHas('grupo', function ($q) use ($curso) {
                    $q->where('curso_id', $curso->id);
                })->where('estado', 'ACTIVA')->count();

                return [
                    'id' => $curso->id,
                    'nombre' => $curso->nombre,
                    'cantidad_activos' => $count,
                ];
            })->toArray();

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_ingresos' => $totalIncome,
            'pagos' => [
                'pagado' => [
                    'cantidad' => $paidCount,
                    'monto' => $paidSum,
                ],
                'pendiente' => [
                    'cantidad' => $pendingCount,
                    'monto' => $pendingSum,
                ],
                'atrasado' => [
                    'cantidad' => $overdueCount,
                    'monto' => $overdueSum,
                ],
            ],
            'inscripciones_por_curso' => $activeRegistrations,
        ];
    }

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
     * Get detailed business report data (mensualidades with alumno + curso).
     */
    public function getDetalleNegocio(array $filters = []): array
    {
        $startDate = !empty($filters['start_date']) 
            ? Carbon::parse($filters['start_date'])->startOfDay() 
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = !empty($filters['end_date']) 
            ? Carbon::parse($filters['end_date'])->endOfDay() 
            : Carbon::now()->endOfMonth()->endOfDay();

        $mensualidades = Mensualidad::with([
                'inscripcion.alumno.usuario', 
                'inscripcion.grupo.curso'
            ])
            ->where(function ($q) use ($startDate, $endDate) {
                // Pagados: filtrar por fecha_pago (cuándo se cobró)
                $q->where(function ($sub) use ($startDate, $endDate) {
                    $sub->where('estado', 'PAGADO')
                        ->whereBetween('fecha_pago', [$startDate->toDateString(), $endDate->toDateString()]);
                })->orWhere(function ($sub) use ($startDate, $endDate) {
                    // Pendientes y atrasados: filtrar por fecha_vencimiento
                    $sub->where('estado', '!=', 'PAGADO')
                        ->whereBetween('fecha_vencimiento', [$startDate->toDateString(), $endDate->toDateString()]);
                });
            })
            ->orderBy('fecha_pago', 'desc')
            ->get()
            ->map(function ($m) {
                $alumnoNombre = $m->inscripcion && $m->inscripcion->alumno && $m->inscripcion->alumno->usuario 
                    ? ($m->inscripcion->alumno->usuario->nombres . ' ' . $m->inscripcion->alumno->usuario->apellidos) 
                    : 'N/A';
                $cursoNombre = $m->inscripcion && $m->inscripcion->grupo && $m->inscripcion->grupo->curso 
                    ? $m->inscripcion->grupo->curso->nombre 
                    : 'N/A';

                return [
                    'id' => $m->id,
                    'alumno' => $alumnoNombre,
                    'curso' => $cursoNombre,
                    'numero_mes' => $m->numero_mes,
                    'monto_base' => (float) $m->monto_base,
                    'fecha_vencimiento' => $m->fecha_vencimiento ? $m->fecha_vencimiento->toDateString() : '',
                    'fecha_pago' => $m->fecha_pago ? $m->fecha_pago->toDateString() : '',
                    'estado' => $m->estado,
                    'numero_recibo' => $m->numero_recibo ?? '',
                ];
            })
            ->values()
            ->toArray();

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => $mensualidades,
        ];
    }

    /**
     * Get dynamic metrics for the main landing dashboard.
     */
    public function obtenerEstadisticasDashboard(): array
    {
        $alumnosActivos = \App\Models\Alumno::where('estado', 'ACTIVO')
            ->whereHas('usuario', function ($q) {
                $q->where('eliminado', false);
            })->count();

        $gruposAbiertos = \App\Models\Grupo::where('estado', 'ACTIVO')
            ->where('eliminado', false)->count();

        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();
        $ingresosMensuales = (float) Mensualidad::where('estado', 'PAGADO')
            ->whereBetween('fecha_pago', [$startDate, $endDate])
            ->sum('monto_base');

        $visitasGlobales = (int) \App\Models\PaginaVisitada::sum('contador');

        // ── Chart data ────────────────────────────────────────────────────

        // 1. Monthly income trend (last 12 months)
        $ingresos12Meses = collect(range(0, 11))->map(function ($i) {
            $month = Carbon::now()->subMonths($i);
            $income = (float) Mensualidad::where('estado', 'PAGADO')
                ->whereBetween('fecha_pago', [
                    $month->copy()->startOfMonth()->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ])->sum('monto_base');
            return [
                'mes' => $month->isoFormat('MMM YYYY'),
                'ingreso' => $income,
            ];
        })->reverse()->values()->toArray();

        // 2. Active students per course
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

        // 3. Current month payment status breakdown
        $pagosMesActual = [
            'pagado' => Mensualidad::where('estado', 'PAGADO')
                ->whereBetween('fecha_pago', [$startDate, $endDate])->count(),
            'pendiente' => Mensualidad::whereIn('estado', ['PENDIENTE', 'PENDIENTE_PAGO_QR'])
                ->whereBetween('fecha_vencimiento', [$startDate, $endDate])->count(),
            'atrasado' => Mensualidad::where('estado', 'ATRASADO')
                ->whereBetween('fecha_vencimiento', [$startDate, $endDate])->count(),
        ];

        // 4. New enrollments per month (last 12 months)
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
            'alumnos_activos' => $alumnosActivos,
            'grupos_abiertos' => $gruposAbiertos,
            'ingresos_mensuales' => $ingresosMensuales,
            'visitas_globales' => $visitasGlobales,
            'ingresos_12_meses' => $ingresos12Meses,
            'alumnos_por_curso' => $alumnosPorCurso,
            'pagos_mes_actual' => $pagosMesActual,
            'inscripciones_12_meses' => $inscripciones12Meses,
        ];
    }
}
