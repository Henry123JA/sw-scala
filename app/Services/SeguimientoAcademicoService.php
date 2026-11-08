<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use App\Models\ObservacionAcademica;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SeguimientoAcademicoService
{
    public function __construct(
        protected ProgresoAcademicoService $progresoService
    ) {}

    /**
     * Listar estudiantes e inscripciones para seguimiento con control de acceso por rol y filtros.
     *
     * - Docente: solo inscripciones de sus grupos asignados.
     * - Propietario / Secretaria: todas las inscripciones (activas, pausadas, canceladas).
     * - Alumno: solo sus propias inscripciones.
     * - Admite búsqueda por nombre/apellido del alumno, curso o grupo.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function listarEstudiantesSeguimiento(array $filtros, Usuario $usuario): LengthAwarePaginator
    {
        $rol = $usuario->rol?->nombre;

        $query = Inscripcion::query()
            ->with([
                'alumno.usuario',
                'grupo.curso',
                'grupo.docente.usuario',
                'asistencias.sesionClase',
                'calificaciones',
            ]);

        // Validación de permisos por rol
        if ($rol === 'Alumno') {
            $query->where('alumno_id', $usuario->id);
        } elseif ($rol === 'Docente') {
            $query->whereHas('grupo', function ($q) use ($usuario) {
                $q->where('docente_id', $usuario->id);
            });
        } elseif (in_array($rol, ['Propietario', 'Secretaria'], true)) {
            // Propietario y Secretaria tienen acceso a todas las inscripciones
        } else {
            abort(403, 'No autorizado para consultar el seguimiento académico.');
        }

        // Búsqueda por texto (nombre, apellido, código, CI de alumno o código/curso del grupo)
        $buscar = trim($filtros['buscar'] ?? $filtros['q'] ?? '');
        if ($buscar !== '') {
            $query->where(function ($sub) use ($buscar) {
                $sub->whereHas('alumno.usuario', function ($q) use ($buscar) {
                    $q->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('codigo', 'like', "%{$buscar}%")
                        ->orWhere('ci', 'like', "%{$buscar}%");
                })->orWhereHas('grupo', function ($q) use ($buscar) {
                    $q->where('codigo_grupo', 'like', "%{$buscar}%")
                        ->orWhereHas('curso', function ($c) use ($buscar) {
                            $c->where('nombre', 'like', "%{$buscar}%");
                        });
                });
            });
        }

        // Filtro opcional por estado de inscripción
        if (! empty($filtros['estado']) && $filtros['estado'] !== 'TODOS') {
            $query->where('estado', $filtros['estado']);
        }

        // Filtro opcional por grupo específico
        if (! empty($filtros['grupo_id'])) {
            $query->where('grupo_id', $filtros['grupo_id']);
        }

        // Filtro opcional por curso
        if (! empty($filtros['curso_id'])) {
            $query->whereHas('grupo', function ($q) use ($filtros) {
                $q->where('curso_id', $filtros['curso_id']);
            });
        }

        $query->orderByDesc('id');

        $perPage = (int) ($filtros['per_page'] ?? 15);
        $paginador = $query->paginate($perPage)->withQueryString();

        $paginador->through(function (Inscripcion $inscripcion) {
            $alumnoUser = $inscripcion->alumno?->usuario;
            $docenteUser = $inscripcion->grupo?->docente?->usuario;

            $resumenRendimiento = $this->obtenerResumenRendimientoInscripcion($inscripcion);

            return [
                'id' => $inscripcion->id,
                'estado' => $inscripcion->estado,
                'fecha' => $inscripcion->fecha?->format('Y-m-d'),
                'fecha_inicio_clases' => $inscripcion->fecha_inicio_clases?->format('Y-m-d'),
                'fecha_pausa' => $inscripcion->fecha_pausa?->format('Y-m-d'),
                'fecha_retorno' => $inscripcion->fecha_retorno?->format('Y-m-d'),
                'fecha_retiro' => $inscripcion->fecha_retiro?->format('Y-m-d'),
                'observaciones' => $inscripcion->observaciones,
                'alumno' => [
                    'id' => $inscripcion->alumno?->id,
                    'codigo' => $inscripcion->alumno?->codigo ?? $alumnoUser?->codigo,
                    'nombres' => $alumnoUser?->nombres,
                    'apellidos' => $alumnoUser?->apellidos,
                    'nombre_completo' => trim(($alumnoUser?->nombres ?? '').' '.($alumnoUser?->apellidos ?? '')),
                    'email' => $alumnoUser?->email,
                    'ci' => $alumnoUser?->ci,
                    'foto_url' => $alumnoUser?->foto_url,
                    'nivel' => $inscripcion->alumno?->nivel,
                ],
                'grupo' => [
                    'id' => $inscripcion->grupo?->id,
                    'codigo_grupo' => $inscripcion->grupo?->codigo_grupo,
                    'nivel' => $inscripcion->grupo?->nivel,
                    'estado' => $inscripcion->grupo?->estado,
                ],
                'curso' => [
                    'id' => $inscripcion->grupo?->curso?->id,
                    'nombre' => $inscripcion->grupo?->curso?->nombre,
                    'tipo_ensenanza' => $inscripcion->grupo?->curso?->tipo_ensenanza,
                ],
                'docente' => [
                    'id' => $inscripcion->grupo?->docente?->id,
                    'nombre_completo' => trim(($docenteUser?->nombres ?? '').' '.($docenteUser?->apellidos ?? '')),
                    'nombres' => $docenteUser?->nombres,
                    'apellidos' => $docenteUser?->apellidos,
                    'codigo' => $inscripcion->grupo?->docente?->codigo ?? $docenteUser?->codigo,
                ],
                'progreso' => [
                    'semaforo' => $resumenRendimiento['semaforo'],
                    'promedio' => $resumenRendimiento['promedio'],
                    'porcentaje_asistencia' => $resumenRendimiento['asistencia_porcentaje'],
                    'asistencias_presentes' => $resumenRendimiento['presentes'],
                    'asistencias_faltas' => $resumenRendimiento['faltas'],
                    'total_evaluaciones' => $resumenRendimiento['total_evaluaciones'],
                ],
                'resumen_rendimiento' => $resumenRendimiento,
            ];
        });

        return $paginador;
    }

    /**
     * Obtener el expediente completo de seguimiento (Ficha Integral) de un estudiante.
     *
     * Valida permisos:
     * - Alumno: solo su propia inscripción (403 si no).
     * - Docente: solo si es el docente asignado al grupo de la inscripción (403 si no).
     * - Propietario / Secretaria: acceso total.
     *
     * Reúne:
     * - inscripcion: datos de la inscripción, estado, fechas de inicio/pausa/retiro.
     * - alumno: nombres, apellidos, código, email, ci.
     * - grupo: código_grupo, nivel, horarios.
     * - curso: nombre, descripción.
     * - docente: nombre completo.
     * - progreso: llamada a ProgresoAcademicoService::calcularProgresoInscripcion($inscripcionId).
     * - asistencias: historial cronológico de todas las sesiones de asistencia.
     * - calificaciones: lista de evaluaciones del grupo y calificación del alumno.
     * - observaciones: pedagógicas con filtro estricto where('visible_para_alumno', true) si es Alumno.
     */
    public function obtenerFichaIntegral(int $inscripcionId, Usuario $usuario): array
    {
        $inscripcion = Inscripcion::with([
            'alumno.usuario',
            'grupo.curso',
            'grupo.docente.usuario',
            'grupo.horarios.sala',
        ])->findOrFail($inscripcionId);

        $rol = $usuario->rol?->nombre;

        // Validación estricta de autorización (HTTP 403 si no tiene acceso)
        if ($rol === 'Alumno') {
            if ((int) $inscripcion->alumno_id !== (int) $usuario->id) {
                abort(403, 'No está autorizado para consultar la ficha de seguimiento de otro estudiante.');
            }
        } elseif ($rol === 'Docente') {
            if ((int) $inscripcion->grupo?->docente_id !== (int) $usuario->id) {
                abort(403, 'No está autorizado para consultar fichas de alumnos de otros docentes.');
            }
        } elseif (! in_array($rol, ['Propietario', 'Secretaria'], true)) {
            abort(403, 'No autorizado para consultar la ficha de seguimiento.');
        }

        $usuarioAlumno = $inscripcion->alumno?->usuario;
        $grupo = $inscripcion->grupo;
        $curso = $grupo?->curso;
        $usuarioDocente = $grupo?->docente?->usuario;

        // Horarios formateados
        $horarios = $grupo?->horarios?->map(function ($h) {
            return [
                'id' => $h->id,
                'dia_semana' => $h->dia_semana,
                'hora_inicio' => is_string($h->hora_inicio) ? substr($h->hora_inicio, 0, 5) : $h->hora_inicio,
                'hora_fin' => is_string($h->hora_fin) ? substr($h->hora_fin, 0, 5) : $h->hora_fin,
                'sala' => $h->sala?->nombre ?? 'Sin sala',
                'tipo_sesion' => $h->tipo_sesion,
            ];
        })->values() ?? collect();

        // 1. Progreso (HU11) mediante ProgresoAcademicoService
        $progreso = $this->progresoService->calcularProgresoInscripcion($inscripcionId);

        // 2. Historial cronológico de asistencias
        $asistencias = Asistencia::where('inscripcion_id', $inscripcionId)
            ->with('sesionClase')
            ->get()
            ->sortBy(function ($a) {
                return $a->sesionClase?->fecha ? Carbon::parse($a->sesionClase->fecha)->timestamp : 0;
            })
            ->values()
            ->map(function ($a) {
                $fecha = $a->sesionClase?->fecha;

                return [
                    'id' => $a->id,
                    'sesion_clase_id' => $a->sesion_clase_id,
                    'fecha' => $fecha ? Carbon::parse($fecha)->format('Y-m-d') : null,
                    'tema' => 'Sesión ordinaria de clase',
                    'estado' => $a->estado,
                    'observacion' => $a->observacion,
                ];
            });

        // 3. Evaluaciones del grupo y notas del alumno
        $evaluaciones = Evaluacion::where('grupo_id', $inscripcion->grupo_id)
            ->with(['calificaciones' => function ($q) use ($inscripcionId) {
                $q->where('inscripcion_id', $inscripcionId);
            }])
            ->orderBy('fecha', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $calificaciones = $evaluaciones->map(function ($ev) {
            $calif = $ev->calificaciones->first();

            return [
                'evaluacion_id' => $ev->id,
                'fecha' => $ev->fecha ? Carbon::parse($ev->fecha)->format('Y-m-d') : null,
                'nombre' => $ev->nombre,
                'tipo' => $ev->tipo,
                'ponderacion' => (float) $ev->ponderacion,
                'descripcion' => $ev->descripcion,
                'calificacion_id' => $calif?->id,
                'nota' => ($calif !== null && $calif->nota !== null) ? (float) $calif->nota : null,
                'comentarios' => $calif?->observaciones,
                'observaciones' => $calif?->observaciones,
            ];
        })->values();

        // 4. Observaciones pedagógicas (con filtro de privacidad para Alumnos)
        $observacionesQuery = ObservacionAcademica::with(['docente.usuario'])
            ->where('inscripcion_id', $inscripcionId);

        if ($rol === 'Alumno') {
            $observacionesQuery->where('visible_para_alumno', true);
        }

        $observaciones = $observacionesQuery
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function (ObservacionAcademica $obs) {
                $docUser = $obs->docente?->usuario;

                return [
                    'id' => $obs->id,
                    'fecha' => $obs->fecha ? Carbon::parse($obs->fecha)->format('Y-m-d') : null,
                    'tipo' => $obs->tipo,
                    'titulo' => $obs->titulo,
                    'descripcion' => $obs->descripcion,
                    'visible_para_alumno' => (bool) $obs->visible_para_alumno,
                    'docente_nombre' => trim(($docUser?->nombres ?? '').' '.($docUser?->apellidos ?? '')),
                    'docente' => [
                        'id' => $obs->docente_id,
                        'nombre_completo' => trim(($docUser?->nombres ?? '').' '.($docUser?->apellidos ?? '')),
                    ],
                    'created_at' => $obs->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return [
            'inscripcion' => [
                'id' => $inscripcion->id,
                'estado' => $inscripcion->estado,
                'fecha' => $inscripcion->fecha?->format('Y-m-d'),
                'fecha_inicio_clases' => $inscripcion->fecha_inicio_clases?->format('Y-m-d'),
                'fecha_pausa' => $inscripcion->fecha_pausa?->format('Y-m-d'),
                'fecha_retorno' => $inscripcion->fecha_retorno?->format('Y-m-d'),
                'fecha_retiro' => $inscripcion->fecha_retiro?->format('Y-m-d'),
                'observaciones' => $inscripcion->observaciones,
            ],
            'alumno' => [
                'id' => $inscripcion->alumno?->id,
                'nombres' => $usuarioAlumno?->nombres,
                'apellidos' => $usuarioAlumno?->apellidos,
                'nombre_completo' => trim(($usuarioAlumno?->nombres ?? '').' '.($usuarioAlumno?->apellidos ?? '')),
                'codigo' => $inscripcion->alumno?->codigo ?? $usuarioAlumno?->codigo,
                'email' => $usuarioAlumno?->email,
                'ci' => $usuarioAlumno?->ci,
                'telefono' => $inscripcion->alumno?->telefono,
                'telefono_alternativo' => $inscripcion->alumno?->telefono_alternativo,
                'nivel' => $inscripcion->alumno?->nivel,
                'foto_url' => $usuarioAlumno?->foto_url,
            ],
            'grupo' => [
                'id' => $grupo?->id,
                'codigo_grupo' => $grupo?->codigo_grupo,
                'nivel' => $grupo?->nivel,
                'capacidad_maxima' => $grupo?->capacidad_maxima,
                'estado' => $grupo?->estado,
                'horarios' => $horarios,
            ],
            'curso' => [
                'id' => $curso?->id,
                'nombre' => $curso?->nombre,
                'descripcion' => $curso?->descripcion,
                'tipo_ensenanza' => $curso?->tipo_ensenanza,
                'duracion_estandar' => $curso?->duracion_estandar,
            ],
            'docente' => [
                'id' => $grupo?->docente?->id,
                'nombre_completo' => trim(($usuarioDocente?->nombres ?? '').' '.($usuarioDocente?->apellidos ?? '')),
                'nombres' => $usuarioDocente?->nombres,
                'apellidos' => $usuarioDocente?->apellidos,
                'codigo' => $grupo?->docente?->codigo ?? $usuarioDocente?->codigo,
                'email' => $usuarioDocente?->email,
                'telefono' => $grupo?->docente?->telefono,
                'foto_url' => $usuarioDocente?->foto_url,
            ],
            'progreso' => $progreso,
            'asistencias' => $asistencias,
            'calificaciones' => $calificaciones,
            'observaciones' => $observaciones,
        ];
    }

    /**
     * Resumen de rendimiento optimizado para listados y tarjetas usando ProgresoAcademicoService.
     */
    protected function obtenerResumenRendimientoInscripcion(Inscripcion $inscripcion): array
    {
        $asistencias = $inscripcion->asistencias;
        $totalSesiones = $asistencias->count();

        $presentes = $asistencias->where('estado', 'PRESENTE')->count();
        $atrasos = $asistencias->where('estado', 'ATRASO')->count();
        $licencias = $asistencias->where('estado', 'LICENCIA')->count();
        $faltas = $asistencias->where('estado', 'FALTA')->count();

        $asistenciaPct = $totalSesiones > 0
            ? round((($presentes * 1.0 + $atrasos * 0.8) / $totalSesiones) * 100, 2)
            : 100.0;

        $calificaciones = $inscripcion->calificaciones->whereNotNull('nota');
        $totalCalificaciones = $calificaciones->count();
        $promedio = $totalCalificaciones > 0
            ? round((float) $calificaciones->avg('nota'), 2)
            : null;

        $nivelRendimiento = $this->progresoService->determinarNivelRendimiento($promedio, $asistenciaPct);
        $metaRendimiento = ProgresoAcademicoService::RENDIMIENTOS[$nivelRendimiento] ?? ProgresoAcademicoService::RENDIMIENTOS['REGULAR'];

        return [
            'promedio' => $promedio,
            'asistencia_porcentaje' => $asistenciaPct,
            'total_evaluaciones' => $totalCalificaciones,
            'total_sesiones' => $totalSesiones,
            'presentes' => $presentes,
            'faltas' => $faltas,
            'atrasos' => $atrasos,
            'licencias' => $licencias,
            'semaforo' => $nivelRendimiento,
            'rendimiento' => [
                'nivel' => $nivelRendimiento,
                'label' => $metaRendimiento['label'],
                'color' => $metaRendimiento['color'],
                'badge' => $metaRendimiento['badge'],
            ],
        ];
    }
}
