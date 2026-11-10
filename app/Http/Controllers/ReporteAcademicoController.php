<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reporte\FiltrarReporteRequest;
use App\Services\ProgresoAcademicoService;
use App\Services\SeguimientoAcademicoService;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Calificacion;
use App\Models\Asistencia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReporteAcademicoController extends Controller
{
    public function __construct(
        protected ProgresoAcademicoService $progresoService,
        protected SeguimientoAcademicoService $seguimientoService
    ) {}

    public function index(FiltrarReporteRequest $request): Response
    {
        $totalInscripciones = Inscripcion::count();
        $promedioGeneral = Calificacion::avg('nota') ?? 0;

        $totalAsistencias = Asistencia::count();
        $asistenciasPresentes = Asistencia::where('estado', 'PRESENTE')->count();
        $porcentajeAsistencia = $totalAsistencias > 0
            ? round(($asistenciasPresentes / $totalAsistencias) * 100, 1)
            : 100;

        $dashboard = [
            'total_inscripciones' => $totalInscripciones,
            'promedio_general' => round((float) $promedioGeneral, 2),
            'porcentaje_asistencia_global' => $porcentajeAsistencia,
            'semaforos' => [
                'EXCELENTE' => Calificacion::where('nota', '>=', 90)->count(),
                'FAVORABLE' => Calificacion::whereBetween('nota', [70, 89.99])->count(),
                'REGULAR'   => Calificacion::whereBetween('nota', [51, 69.99])->count(),
                'EN RIESGO' => Calificacion::where('nota', '<', 51)->count(),
            ],
        ];

        $grupos = Grupo::with(['curso', 'docente.usuario'])->get();

        return Inertia::render('Reportes/AcademicosIndex', [
            'dashboard' => $dashboard,
            'grupos' => $grupos,
        ]);
    }

    public function sabanaGrupo(FiltrarReporteRequest $request, int $grupoId)
    {
        $grupo = Grupo::with(['curso', 'docente.usuario', 'inscripciones.alumno.usuario'])->findOrFail($grupoId);
        $evaluaciones = \App\Models\Evaluacion::where('grupo_id', $grupoId)->get();

        $estudiantes = [];
        foreach ($grupo->inscripciones as $ins) {
            $calificaciones = [];
            $sumaNotas = 0;
            $totalNotas = 0;

            foreach ($evaluaciones as $ev) {
                $calif = \App\Models\Calificacion::where('inscripcion_id', $ins->id)
                    ->where('evaluacion_id', $ev->id)
                    ->first();
                $nota = $calif ? (float) $calif->nota : null;
                $calificaciones[$ev->id] = $nota;

                if ($nota !== null) {
                    $sumaNotas += $nota;
                    $totalNotas++;
                }
            }

            $promedio = $totalNotas > 0 ? round($sumaNotas / $totalNotas, 2) : 0;

            // Semáforo académico
            $semaforo = 'SIN DATOS';
            if ($totalNotas > 0) {
                if ($promedio >= 90) $semaforo = 'EXCELENTE';
                elseif ($promedio >= 70) $semaforo = 'FAVORABLE';
                elseif ($promedio >= 51) $semaforo = 'REGULAR';
                else $semaforo = 'EN RIESGO';
            }

            $totalAsistenciasEst = \App\Models\Asistencia::where('inscripcion_id', $ins->id)->count();
            $asistenciasPresentesEst = \App\Models\Asistencia::where('inscripcion_id', $ins->id)->where('estado', 'PRESENTE')->count();
            $porcentajeAsistencia = $totalAsistenciasEst > 0 
                ? round(($asistenciasPresentesEst / $totalAsistenciasEst) * 100, 1) 
                : 100;

            $estudiantes[] = [
                'inscripcion_id' => $ins->id,
                'alumno' => [
                    'codigo' => $ins->alumno->codigo ?? 'EST-' . $ins->alumno_id,
                    'nombre_completo' => ($ins->alumno->usuario->nombres ?? '') . ' ' . ($ins->alumno->usuario->apellidos ?? ''),
                    'ci' => $ins->alumno->usuario->ci ?? 'S/N',
                ],
                'calificaciones' => $calificaciones,
                'resumen_progreso' => [
                    'promedio' => $promedio,
                    'porcentaje_asistencia' => $porcentajeAsistencia,
                    'semaforo' => $semaforo,
                ],
            ];
        }

        $data = [
            'grupo' => $grupo,
            'docente' => $grupo->docente,
            'evaluaciones' => $evaluaciones,
            'estudiantes' => $estudiantes,
        ];

        if ($request->input('export') === 'csv') {
            return new StreamedResponse(function () use ($data) {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");

                fputcsv($handle, ['CÓDIGO', 'ALUMNO', 'CI', 'PROMEDIO', '% ASISTENCIA', 'SEMÁFORO'], ';');

                foreach ($data['estudiantes'] as $est) {
                    fputcsv($handle, [
                        $est['alumno']['codigo'] ?? '',
                        $est['alumno']['nombre_completo'] ?? '',
                        $est['alumno']['ci'] ?? '',
                        number_format($est['resumen_progreso']['promedio'] ?? 0, 2),
                        ($est['resumen_progreso']['porcentaje_asistencia'] ?? 0) . '%',
                        $est['resumen_progreso']['semaforo'] ?? 'SIN DATOS',
                    ], ';');
                }
                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"sabana_notas_{$grupo->codigo_grupo}.csv\"",
            ]);
        }

        return Inertia::render('Reportes/SabanaGrupo', $data);
    }
    public function boletinIndividual(FiltrarReporteRequest $request, int $inscripcionId): Response
    {
        $ficha = $this->seguimientoService->obtenerFichaIntegral($inscripcionId, $request->user());

        return Inertia::render('Reportes/BoletinIndividual', $ficha);
    }
}