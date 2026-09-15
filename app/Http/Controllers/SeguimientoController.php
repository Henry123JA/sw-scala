<?php

namespace App\Http\Controllers;

use App\Services\SeguimientoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SeguimientoController extends Controller
{
    public function __construct(
        protected SeguimientoService $seguimientoService
    ) {}

    /**
     * Display lists of overdue and upcoming due payments.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $rol = $user?->rol?->nombre;

        // Alumno: redirige a su propio seguimiento
        if ($rol === 'Alumno') {
            abort(redirect()->route('seguimiento.show', ['alumno_id' => $user->id]));
        }

        $docenteId = ($rol === 'Docente') ? auth()->id() : null;
        $buscar = $request->query('buscar', '');
        $dias = (int) $request->query('dias', 7);

        if ($dias <= 0) {
            $dias = 7;
        }

        $atrasados = $this->seguimientoService->alumnosAtrasados($docenteId, $buscar);
        $proximos = $this->seguimientoService->proximosVencer($dias, $docenteId, $buscar);

        return Inertia::render('Seguimiento/Index', [
            'atrasados' => $atrasados,
            'proximos'  => $proximos,
            'buscar'    => $buscar,
            'dias'      => $dias,
        ]);
    }

    /**
     * Display student's tracking timeline (all active enrollments and their monthly payments).
     */
    public function show(int $alumnoId): Response
    {
        $rol = auth()->user()?->rol?->nombre;
        $currentUserId = auth()->id();

        if ($rol === 'Alumno') {
            if ($currentUserId !== $alumnoId) {
                abort(403, 'No autorizado.');
            }
        } elseif ($rol === 'Docente') {
            if (!$this->seguimientoService->docenteTieneAccesoAAlumno($currentUserId, $alumnoId)) {
                abort(403, 'No autorizado.');
            }
        }

        $alumno = $this->seguimientoService->obtenerAlumno($alumnoId);
        $inscripciones = $this->seguimientoService->estadoAlumno($alumnoId);

        return Inertia::render('Seguimiento/Alumno', [
            'alumno'        => $alumno,
            'inscripciones' => $inscripciones,
        ]);
    }
}
