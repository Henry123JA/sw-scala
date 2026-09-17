<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * JSON endpoint — access log data for charts/tables.
     */
    public function detalleAcceso(Request $request): JsonResponse
    {
        $filters = $request->only(['usuario', 'accion', 'estado', 'start_date', 'end_date']);
        $logs = $this->reporteService->getBitacoraLog($filters, false);
        return response()->json($logs);
    }
}
