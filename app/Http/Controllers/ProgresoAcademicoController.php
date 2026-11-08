<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Services\ProgresoAcademicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgresoAcademicoController extends Controller
{
    public function __construct(
        protected ProgresoAcademicoService $service
    ) {}

    /**
     * Vista principal del panel de progreso académico:
     * - Si el usuario autenticado es Alumno: retorna su progreso personal en todos sus cursos inscritos.
     * - Si es Docente, Secretaria o Propietario: retorna la lista de grupos con métricas agregadas para las tarjetas.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $user = $request->user();

        // 1. Caso Alumno: Libreta y seguimiento personal de progreso
        if ($user->rol?->nombre === 'Alumno') {
            $datos = $this->service->obtenerProgresoAlumno($user);

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json($datos);
            }

            return Inertia::render('Progreso/Index', $datos);
        }

        // 2. Caso Docente / Secretaria / Propietario: Panel analítico de grupos
        $grupos = $this->service->listarGruposProgreso($user);
        $totalAlumnos = $grupos->sum('total_alumnos');
        $gruposConNotas = $grupos->filter(fn ($g) => $g['promedio_grupal'] !== null);

        $promedioG = $gruposConNotas->count() > 0 ? round($gruposConNotas->avg('promedio_grupal'), 2) : 0;
        $asistenciaG = $grupos->count() > 0 ? round($grupos->avg('asistencia_grupal'), 2) : 100.0;
        $aprobadosG = $grupos->sum('aprobados');
        $alumnosEvaluadosG = $grupos->sum('alumnos_evaluados');
        $tasaAprobacionG = $alumnosEvaluadosG > 0 ? round(($aprobadosG / $alumnosEvaluadosG) * 100, 2) : 0;
        $totalEnRiesgo = $grupos->sum('en_riesgo');

        $kpisGlobales = [
            'promedio_general' => $promedioG,
            'porcentaje_asistencia' => $asistenciaG,
            'tasa_aprobacion' => $tasaAprobacionG,
            'aprobados' => $aprobadosG,
            'en_riesgo' => $totalEnRiesgo,
        ];

        $datos = [
            'es_alumno' => false,
            'rol_usuario' => $user->rol?->nombre,
            'kpis_globales' => $kpisGlobales,
            'metricas_globales' => [
                'total_grupos' => $grupos->count(),
                'total_alumnos' => $totalAlumnos,
                'promedio_general' => $promedioG,
                'asistencia_general' => $asistenciaG,
                'total_en_riesgo' => $totalEnRiesgo,
                'conteo_rendimiento' => [
                    'EXCELENTE' => $grupos->sum('conteo_rendimiento.EXCELENTE'),
                    'FAVORABLE' => $grupos->sum('conteo_rendimiento.FAVORABLE'),
                    'REGULAR'   => $grupos->sum('conteo_rendimiento.REGULAR'),
                    'EN RIESGO' => $grupos->sum('conteo_rendimiento.EN RIESGO'),
                ],
            ],
            'grupos' => $grupos,
        ];

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($datos);
        }

        return Inertia::render('Progreso/Index', $datos);
    }

    /**
     * Retorna el detalle analítico y de todos los alumnos de un grupo específico.
     */
    public function progresoGrupo(Request $request, int $grupo): Response|JsonResponse
    {
        $datos = $this->service->calcularProgresoGrupo($grupo, $request->user());

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($datos);
        }

        return Inertia::render('Progreso/DetalleGrupo', array_merge($datos, [
            'metricas' => $datos['metricas_globales'] ?? [],
        ]));
    }

    /**
     * Retorna el progreso detallado de una inscripción específica (para modales o vista individual).
     */
    public function progresoInscripcion(Request $request, int $inscripcion): JsonResponse
    {
        $user = $request->user();

        // Validación de permisos
        if ($user->rol?->nombre === 'Alumno') {
            $inscripcionModel = Inscripcion::findOrFail($inscripcion);
            abort_if((int) $inscripcionModel->alumno_id !== (int) $user->id, 403, 'No tiene autorización para ver este progreso.');
        } elseif ($user->rol?->nombre === 'Docente') {
            $inscripcionModel = Inscripcion::with('grupo')->findOrFail($inscripcion);
            abort_if((int) $inscripcionModel->grupo?->docente_id !== (int) $user->id, 403, 'No tiene autorización para ver este progreso.');
        }

        $datos = $this->service->calcularProgresoInscripcion($inscripcion);

        return response()->json($datos);
    }
}
