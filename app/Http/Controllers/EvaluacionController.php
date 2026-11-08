<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Services\EvaluacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EvaluacionController extends Controller
{
    public function __construct(private EvaluacionService $service) {}

    /**
     * Vista principal:
     * - Si es Alumno: libreta personal de calificaciones.
     * - Si es Docente/Admin/Secretaria: listado de grupos con sus evaluaciones.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->rol?->nombre === 'Alumno') {
            return Inertia::render('Evaluacion/Index', $this->service->obtenerCalificacionesAlumno($user));
        }

        return Inertia::render('Evaluacion/Index', [
            'es_alumno' => false,
            'grupos' => $this->service->listarGrupos($user),
            'rol_usuario' => $user->rol?->nombre,
        ]);
    }

    /**
     * Crea una nueva evaluación para el grupo indicado.
     */
    public function store(Request $request, int $grupo): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'string', Rule::in(Evaluacion::TIPOS)],
            'fecha' => ['required', 'date'],
            'ponderacion' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $evaluacion = $this->service->crearEvaluacion($grupo, $validated, $request->user());

        return to_route('evaluaciones.show', $evaluacion->id)
            ->with('success', 'Evaluación creada correctamente. Ahora puede ingresar las calificaciones.');
    }

    /**
     * Planilla de calificaciones para ingresar o consultar notas de una evaluación.
     */
    public function show(Request $request, int $evaluacion): Response
    {
        $datos = $this->service->obtenerPlanillaCalificaciones($evaluacion, $request->user());

        return Inertia::render('Evaluacion/Show', $datos);
    }

    /**
     * Guarda en lote las calificaciones de la evaluación.
     */
    public function guardar(Request $request, int $evaluacion): RedirectResponse
    {
        $validated = $request->validate([
            'calificaciones' => ['required', 'array'],
            'calificaciones.*.inscripcion_id' => ['required', 'integer', 'exists:inscripcion,id'],
            'calificaciones.*.nota' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'calificaciones.*.observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->guardarCalificaciones($evaluacion, $validated['calificaciones'], $request->user());

        return to_route('evaluaciones.index')
            ->with('success', 'Calificaciones registradas correctamente.');
    }
}
