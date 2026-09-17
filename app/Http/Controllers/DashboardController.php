<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected ReporteService $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    /**
     * Display the dashboard or redirect based on user role.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $rol = $user->rol->nombre ?? '';

        if ($rol === 'Alumno') {
            return redirect()->route('inscripciones.index');
        }

        if ($rol === 'Docente') {
            return redirect()->route('grupos.index');
        }

        if ($rol === 'Secretaria') {
            return redirect()->route('inscripciones.index');
        }

        // Propietario only
        $stats = $this->reporteService->obtenerEstadisticasDashboard();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
        ]);
    }
}
