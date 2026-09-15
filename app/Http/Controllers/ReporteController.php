<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use App\Models\Mensualidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReporteController extends Controller
{
    protected ReporteService $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    /**
     * Display reports landing index page.
     */
    public function index()
    {
        return \Inertia\Inertia::render('Reporte/Index');
    }

    /**
     * Display business reports.
     */
    public function negocio(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $metrics = $this->reporteService->getMetodosNegocio($filters);

        return \Inertia\Inertia::render('Reporte/Negocio', [
            'metrics' => $metrics,
            'filters' => $filters,
        ]);
    }

    /**
     * Display access logs.
     */
    public function acceso(Request $request)
    {
        $filters = $request->only(['usuario', 'accion', 'estado', 'start_date', 'end_date']);
        $logs = $this->reporteService->getBitacoraLog($filters);

        return \Inertia\Inertia::render('Reporte/Acceso', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }

    /**
     * Export access logs to CSV.
     */
    public function exportarAcceso(Request $request)
    {
        $filters = $request->only(['usuario', 'accion', 'estado', 'start_date', 'end_date']);
        $logs = $this->reporteService->getBitacoraLog($filters, false);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_acceso_' . now()->format('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($file, ['ID', 'Usuario', 'Email', 'Acción', 'Recurso', 'Método', 'Estado', 'IP', 'Fecha']);

            foreach ($logs as $log) {
                $usuarioNombre = $log->usuario ? ($log->usuario->nombres . ' ' . $log->usuario->apellidos) : 'N/A';
                $usuarioEmail = $log->usuario ? $log->usuario->email : 'N/A';
                fputcsv($file, [
                    $log->id,
                    $usuarioNombre,
                    $usuarioEmail,
                    $log->accion,
                    $log->recurso,
                    $log->metodo,
                    $log->estado,
                    $log->ip,
                    $log->fecha ? $log->fecha->toDateTimeString() : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export business report details to CSV.
     */
    public function exportarNegocio(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $startDate = !empty($filters['start_date']) 
            ? Carbon::parse($filters['start_date'])->startOfDay() 
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = !empty($filters['end_date']) 
            ? Carbon::parse($filters['end_date'])->endOfDay() 
            : Carbon::now()->endOfMonth()->endOfDay();

        $mensualidades = Mensualidad::with(['inscripcion.alumno.usuario', 'inscripcion.grupo.curso'])
            ->whereBetween('fecha_vencimiento', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_negocio_' . now()->format('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($mensualidades) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['ID Mensualidad', 'Alumno', 'Curso', 'Nro Mes', 'Monto Base', 'Vencimiento', 'Fecha Pago', 'Estado', 'Nro Recibo']);

            foreach ($mensualidades as $m) {
                $alumnoNombre = $m->inscripcion && $m->inscripcion->alumno && $m->inscripcion->alumno->usuario 
                    ? ($m->inscripcion->alumno->usuario->nombres . ' ' . $m->inscripcion->alumno->usuario->apellidos) 
                    : 'N/A';
                $cursoNombre = $m->inscripcion && $m->inscripcion->grupo && $m->inscripcion->grupo->curso 
                    ? $m->inscripcion->grupo->curso->nombre 
                    : 'N/A';

                fputcsv($file, [
                    $m->id,
                    $alumnoNombre,
                    $cursoNombre,
                    $m->numero_mes,
                    $m->monto_base,
                    $m->fecha_vencimiento ? $m->fecha_vencimiento->toDateString() : '',
                    $m->fecha_pago ? $m->fecha_pago->toDateString() : '',
                    $m->estado,
                    $m->numero_recibo ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Render business report print layout.
     */
    public function imprimirNegocio(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $metrics = $this->reporteService->getMetodosNegocio($filters);

        return \Inertia\Inertia::render('Reporte/ImprimirNegocio', [
            'metrics' => $metrics,
        ]);
    }

    /**
     * JSON endpoint — detailed business data for charts/tables.
     */
    public function detalleNegocio(Request $request): JsonResponse
    {
        $filters = $request->only(['start_date', 'end_date']);
        return response()->json(
            $this->reporteService->getDetalleNegocio($filters)
        );
    }

    /**
     * JSON endpoint — access log data for charts/tables.
     */
    public function detalleAcceso(Request $request): JsonResponse
    {
        $filters = $request->only(['usuario', 'accion', 'estado', 'start_date', 'end_date']);
        $logs = $this->reporteService->getBitacoraLog($filters, false);
        return response()->json($logs);
    }
}
