<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Usuario;
use Illuminate\Support\Collection;

class ProgresoAcademicoService
{
    /**
     * Definición de niveles de rendimiento y sus metadatos visuales.
     */
    public const RENDIMIENTOS = [
        'EXCELENTE' => [
            'nombre' => 'EXCELENTE',
            'label' => 'Excelente',
            'color' => 'emerald',
            'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'descripcion' => 'Rendimiento sobresaliente (Nota >= 90 y Asistencia >= 85%)',
        ],
        'FAVORABLE' => [
            'nombre' => 'FAVORABLE',
            'label' => 'Favorable',
            'color' => 'blue',
            'badge' => 'bg-blue-100 text-blue-800 border-blue-300',
            'descripcion' => 'Buen desempeño académico y asistencia regular alta (Nota >= 70 y Asistencia >= 75%)',
        ],
        'REGULAR' => [
            'nombre' => 'REGULAR',
            'label' => 'Regular',
            'color' => 'amber',
            'badge' => 'bg-amber-100 text-amber-800 border-amber-300',
            'descripcion' => 'Desempeño dentro de los parámetros mínimos aprobatorios',
        ],
        'EN RIESGO' => [
            'nombre' => 'EN RIESGO',
            'label' => 'En Riesgo',
            'color' => 'rose',
            'badge' => 'bg-rose-100 text-rose-800 border-rose-300',
            'descripcion' => 'En riesgo de reprobación académica o deserción por faltas (Nota < 51 o Asistencia < 65%)',
        ],
    ];

    /**
     * Valida permisos sobre un grupo para Docente, Secretaria o Propietario.
     */
    public function autorizarGrupo(Grupo $grupo, Usuario $usuario): void
    {
        abort_unless(
            in_array($usuario->rol?->nombre, ['Propietario', 'Secretaria', 'Docente'], true),
            403,
            'No autorizado para acceder a este grupo.'
        );

        abort_if(
            $usuario->rol->nombre === 'Docente' && (int) $grupo->docente_id !== (int) $usuario->id,
            403,
            'No tiene acceso a este grupo.'
        );
    }

    /**
     * Evalúa el semáforo / indicador de rendimiento según nota promedio y asistencia ponderada:
     * - 'EXCELENTE': nota >= 90 y asistencia >= 85
     * - 'FAVORABLE': nota >= 70 y asistencia >= 75
     * - 'REGULAR': nota >= 51 o asistencia >= 65
     * - 'EN RIESGO': nota < 51 o asistencia < 65
     */
    public function determinarNivelRendimiento(?float $nota, float $asistencia): string
    {
        // Cuando aún no se tienen calificaciones registradas, evaluamos por asistencia
        if ($nota === null) {
            if ($asistencia >= 85) {
                return 'FAVORABLE';
            }
            if ($asistencia >= 65) {
                return 'REGULAR';
            }
            return 'EN RIESGO';
        }

        if ($nota >= 90 && $asistencia >= 85) {
            return 'EXCELENTE';
        }

        if ($nota >= 70 && $asistencia >= 75) {
            return 'FAVORABLE';
        }

        // Si la nota es reprobatoria (< 51) o la asistencia es deficiente (< 65%)
        if ($nota < 51 || $asistencia < 65) {
            return 'EN RIESGO';
        }

        return 'REGULAR';
    }

    /**
     * Calcula y formatea el progreso analítico completo de una inscripción.
     * Reutilizable tanto para llamadas individuales como para mapeos masivos.
     */
    public function formatearProgresoInscripcion(Inscripcion $inscripcion, ?Collection $evaluacionesGrupo = null): array
    {
        $alumno = $inscripcion->alumno;
        $usuarioAlumno = $alumno?->usuario;
        $grupo = $inscripcion->grupo;
        $docente = $grupo?->docente;
        $usuarioDocente = $docente?->usuario;

        // 1. Asistencias
        $asistencias = $inscripcion->asistencias ?? collect();
        $totalSesiones = $asistencias->count();
        $presentes = $asistencias->where('estado', 'PRESENTE')->count();
        $faltas = $asistencias->where('estado', 'FALTA')->count();
        $licencias = $asistencias->where('estado', 'LICENCIA')->count();
        $atrasos = $asistencias->where('estado', 'ATRASO')->count();

        // Porcentaje de asistencia ponderado: Presente = 100%, Atraso = 80%
        $porcentajeAsistencia = $totalSesiones > 0
            ? round((($presentes * 1.0 + $atrasos * 0.8) / $totalSesiones) * 100, 2)
            : 100.0;

        // 2. Calificaciones
        $calificaciones = $inscripcion->calificaciones ?? collect();
        $calificacionesConNota = $calificaciones->filter(fn ($c) => $c->nota !== null);
        $notas = $calificacionesConNota->pluck('nota')->map(fn ($n) => (float) $n);

        $evaluacionesRendidas = $notas->count();
        $promedioGeneral = $evaluacionesRendidas > 0 ? round($notas->avg(), 2) : null;
        $notaMaxima = $evaluacionesRendidas > 0 ? (float) $notas->max() : null;
        $notaMinima = $evaluacionesRendidas > 0 ? (float) $notas->min() : null;

        // Desglose por tipo de evaluación (PRACTICA, TEORIA, AUDICION, RECITAL)
        $tipos = Evaluacion::TIPOS;
        $desglosePorTipo = [];
        $desgloseCalificacionesSimple = [];

        foreach ($tipos as $tipo) {
            $califsTipo = $calificacionesConNota->filter(fn ($c) => ($c->evaluacion?->tipo ?? '') === $tipo);
            $notasTipo = $califsTipo->pluck('nota')->map(fn ($n) => (float) $n);

            $totalTipo = $evaluacionesGrupo
                ? $evaluacionesGrupo->where('tipo', $tipo)->count()
                : $calificaciones->filter(fn ($c) => ($c->evaluacion?->tipo ?? '') === $tipo)->count();

            $rendidasTipo = $notasTipo->count();
            $promTipo = $rendidasTipo > 0 ? round($notasTipo->avg(), 2) : null;

            $desglosePorTipo[$tipo] = [
                'tipo' => $tipo,
                'total_evaluaciones' => $totalTipo,
                'evaluaciones_rendidas' => $rendidasTipo,
                'promedio' => $promTipo,
                'nota_maxima' => $rendidasTipo > 0 ? (float) $notasTipo->max() : null,
                'nota_minima' => $rendidasTipo > 0 ? (float) $notasTipo->min() : null,
            ];

            $desgloseCalificacionesSimple[$tipo] = $promTipo;
        }

        // Total de evaluaciones planificadas en el grupo
        $totalEvaluacionesPlanificadas = $evaluacionesGrupo
            ? $evaluacionesGrupo->count()
            : $calificaciones->count();

        // 3. Indicador / Semáforo de Rendimiento
        $nivelRendimiento = $this->determinarNivelRendimiento($promedioGeneral, $porcentajeAsistencia);
        $metaRendimiento = self::RENDIMIENTOS[$nivelRendimiento] ?? self::RENDIMIENTOS['REGULAR'];

        // 4. Evolución cronológica de eventos (asistencias y calificaciones ordenadas temporalmente)
        $eventos = [];

        foreach ($asistencias as $asistencia) {
            $fecha = $asistencia->sesionClase?->fecha?->toDateString()
                ?? ($asistencia->created_at ? $asistencia->created_at->toDateString() : null);

            $eventos[] = [
                'id' => 'asist_' . $asistencia->id,
                'tipo_evento' => 'ASISTENCIA',
                'fecha' => $fecha,
                'titulo' => 'Asistencia a Clase',
                'subtitulo' => 'Estado: ' . $asistencia->estado,
                'estado' => $asistencia->estado,
                'valor' => match ($asistencia->estado) {
                    'PRESENTE' => 100,
                    'ATRASO' => 80,
                    'LICENCIA' => null,
                    'FALTA' => 0,
                    default => 0,
                },
                'observaciones' => $asistencia->observacion,
            ];
        }

        foreach ($calificacionesConNota as $calificacion) {
            $evaluacion = $calificacion->evaluacion;
            $fecha = $evaluacion?->fecha?->toDateString()
                ?? ($calificacion->created_at ? $calificacion->created_at->toDateString() : null);

            $eventos[] = [
                'id' => 'calif_' . $calificacion->id,
                'tipo_evento' => 'CALIFICACION',
                'fecha' => $fecha,
                'titulo' => 'Evaluación: ' . ($evaluacion?->nombre ?? 'Evaluación'),
                'subtitulo' => 'Tipo: ' . ($evaluacion?->tipo ?? 'PRACTICA'),
                'tipo_evaluacion' => $evaluacion?->tipo,
                'valor' => (float) $calificacion->nota,
                'ponderacion' => $evaluacion?->ponderacion ? (float) $evaluacion->ponderacion : 100,
                'aprobado' => ((float) $calificacion->nota >= 51),
                'observaciones' => $calificacion->observaciones,
            ];
        }

        // Ordenar cronológicamente (fechas más antiguas primero para gráficos)
        usort($eventos, function ($a, $b) {
            $cmp = strcmp($a['fecha'] ?? '', $b['fecha'] ?? '');
            if ($cmp === 0) {
                return strcmp($a['tipo_evento'], $b['tipo_evento']);
            }
            return $cmp;
        });

        // Detalle de calificaciones por evaluación
        $calificacionesDetalle = $calificaciones->map(function ($c) {
            $ev = $c->evaluacion;
            return [
                'calificacion_id' => $c->id,
                'evaluacion_id' => $c->evaluacion_id,
                'nombre' => $ev?->nombre ?? 'Evaluación',
                'tipo' => $ev?->tipo ?? 'PRACTICA',
                'fecha' => $ev?->fecha?->toDateString(),
                'ponderacion' => $ev?->ponderacion ? (float) $ev->ponderacion : 100,
                'nota' => $c->nota !== null ? (float) $c->nota : null,
                'observaciones' => $c->observaciones,
            ];
        })->values();

        return [
            'inscripcion_id' => $inscripcion->id,
            'estado_inscripcion' => $inscripcion->estado,
            'fecha_inscripcion' => $inscripcion->fecha?->toDateString(),
            'fecha_inicio_clases' => $inscripcion->fecha_inicio_clases?->toDateString(),
            'alumno' => [
                'id' => $alumno?->id,
                'codigo' => $alumno?->codigo ?? 'N/A',
                'nombre_completo' => $usuarioAlumno ? trim($usuarioAlumno->nombres . ' ' . $usuarioAlumno->apellidos) : 'N/A',
                'nombres' => $usuarioAlumno?->nombres,
                'apellidos' => $usuarioAlumno?->apellidos,
                'email' => $usuarioAlumno?->email,
                'ci' => $usuarioAlumno?->ci,
                'telefono' => $alumno?->telefono,
                'nivel' => $alumno?->nivel,
                'foto_url' => $usuarioAlumno?->foto_url,
            ],
            'grupo' => [
                'id' => $grupo?->id,
                'codigo_grupo' => $grupo?->codigo_grupo ?? 'N/A',
                'nivel' => $grupo?->nivel,
                'curso' => [
                    'id' => $grupo?->curso?->id,
                    'nombre' => $grupo?->curso?->nombre ?? 'N/A',
                    'tipo_ensenanza' => $grupo?->curso?->tipo_ensenanza,
                ],
                'docente' => [
                    'id' => $docente?->id,
                    'codigo' => $docente?->codigo,
                    'nombre_completo' => $usuarioDocente ? trim($usuarioDocente->nombres . ' ' . $usuarioDocente->apellidos) : 'Sin asignar',
                    'nombres' => $usuarioDocente?->nombres,
                    'apellidos' => $usuarioDocente?->apellidos,
                    'email' => $usuarioDocente?->email,
                ],
            ],
            // Atributos de acceso directo para vistas y componentes
            'id' => $inscripcion->id,
            'codigo_grupo' => $grupo?->codigo_grupo ?? 'N/A',
            'curso_nombre' => $grupo?->curso?->nombre ?? 'N/A',
            'docente_nombre' => $usuarioDocente ? trim($usuarioDocente->nombres . ' ' . $usuarioDocente->apellidos) : 'Sin asignar',
            'nombres' => $usuarioAlumno?->nombres,
            'apellidos' => $usuarioAlumno?->apellidos,
            'codigo' => $alumno?->codigo ?? $usuarioAlumno?->codigo ?? 'N/A',
            'email' => $usuarioAlumno?->email,
            'foto_url' => $usuarioAlumno?->foto_url,
            'semaforo' => $nivelRendimiento,
            'promedio' => $promedioGeneral,
            'promedio_general' => $promedioGeneral,
            'porcentaje_asistencia' => $porcentajeAsistencia,
            'presentes' => $presentes,
            'asistencias_presentes' => $presentes,
            'faltas' => $faltas,
            'asistencias_faltas' => $faltas,
            'licencias' => $licencias,
            'atrasos' => $atrasos,
            'total_evaluaciones' => $totalEvaluacionesPlanificadas,
            'evaluaciones_rendidas' => $evaluacionesRendidas,
            'nota_maxima' => $notaMaxima,
            'nota_minima' => $notaMinima,
            'desglose_calificaciones' => $desgloseCalificacionesSimple,
            'metricas_asistencia' => [
                'total_sesiones' => $totalSesiones,
                'presentes' => $presentes,
                'faltas' => $faltas,
                'licencias' => $licencias,
                'atrasos' => $atrasos,
                'porcentaje' => $porcentajeAsistencia,
            ],
            'asistencias' => [
                'total_sesiones' => $totalSesiones,
                'presentes' => $presentes,
                'faltas' => $faltas,
                'licencias' => $licencias,
                'atrasos' => $atrasos,
                'porcentaje' => $porcentajeAsistencia,
            ],
            'calificaciones' => [
                'promedio_general' => $promedioGeneral,
                'nota_maxima' => $notaMaxima,
                'nota_minima' => $notaMinima,
                'total_evaluaciones' => $totalEvaluacionesPlanificadas,
                'evaluaciones_rendidas' => $evaluacionesRendidas,
                'desglose_por_tipo' => $desglosePorTipo,
                'detalle' => $calificacionesDetalle,
            ],
            'rendimiento' => [
                'nivel' => $nivelRendimiento,
                'label' => $metaRendimiento['label'],
                'color' => $metaRendimiento['color'],
                'badge' => $metaRendimiento['badge'],
                'descripcion' => $metaRendimiento['descripcion'],
                'aprobado' => ($promedioGeneral !== null ? $promedioGeneral >= 51 : null),
            ],
            'evolucion_cronologica' => $eventos,
        ];
    }

    /**
     * 1. calcularProgresoInscripcion(int $inscripcionId):
     * Recupera la inscripción con su alumno (usuario) y grupo (curso, docente).
     * Asistencias: total sesiones, presentes, faltas, licencias, atrasos, porcentaje ponderado.
     * Calificaciones: promedio general sobre 100, desglose por tipo, nota máxima, nota mínima.
     * Indicador/Semáforo de Rendimiento: EXCELENTE, FAVORABLE, REGULAR, EN RIESGO.
     * Evolución cronológica: lista de eventos ordenados por fecha.
     */
    public function calcularProgresoInscripcion(int $inscripcionId): array
    {
        $inscripcion = Inscripcion::with([
            'alumno.usuario',
            'grupo.curso',
            'grupo.docente.usuario',
            'asistencias.sesionClase',
            'calificaciones.evaluacion',
        ])->findOrFail($inscripcionId);

        $evaluacionesGrupo = Evaluacion::where('grupo_id', $inscripcion->grupo_id)->get();

        return $this->formatearProgresoInscripcion($inscripcion, $evaluacionesGrupo);
    }

    /**
     * 2. calcularProgresoGrupo(int $grupoId, Usuario $usuario):
     * Valida autorización: Docente solo sus grupos, Admin/Secretaria cualquier grupo.
     * Lista los alumnos activos inscritos en el grupo y calcula el progreso de cada uno.
     * Calcula métricas globales del grupo: promedio grupal, asistencia grupal, porcentaje de aprobación
     * y conteo por nivel de rendimiento.
     */
    public function calcularProgresoGrupo(int $grupoId, Usuario $usuario): array
    {
        $grupo = Grupo::with([
            'curso',
            'docente.usuario',
            'horarios.sala',
            'evaluaciones',
        ])->findOrFail($grupoId);

        $this->autorizarGrupo($grupo, $usuario);

        // Alumnos activos inscritos en el grupo
        $inscripciones = Inscripcion::where('grupo_id', $grupo->id)
            ->where('estado', 'ACTIVA')
            ->with([
                'alumno.usuario',
                'asistencias.sesionClase',
                'calificaciones.evaluacion',
            ])
            ->get();

        $evaluacionesGrupo = $grupo->evaluaciones;

        // Progreso de cada alumno inscrito
        $alumnosProgreso = $inscripciones->map(function ($inscripcion) use ($evaluacionesGrupo) {
            return $this->formatearProgresoInscripcion($inscripcion, $evaluacionesGrupo);
        })->sortBy('alumno.nombre_completo')->values();

        // Métricas globales del grupo
        $totalAlumnos = $alumnosProgreso->count();
        $alumnosConNotas = $alumnosProgreso->filter(fn ($a) => $a['calificaciones']['promedio_general'] !== null);

        $promedioGrupal = $alumnosConNotas->count() > 0
            ? round($alumnosConNotas->avg(fn ($a) => $a['calificaciones']['promedio_general']), 2)
            : null;

        $asistenciaGrupal = $totalAlumnos > 0
            ? round($alumnosProgreso->avg(fn ($a) => $a['asistencias']['porcentaje']), 2)
            : 100.0;

        $aprobadosCount = $alumnosProgreso->filter(function ($a) {
            $prom = $a['calificaciones']['promedio_general'];
            return $prom !== null && $prom >= 51;
        })->count();

        $porcentajeAprobacion = $alumnosConNotas->count() > 0
            ? round(($aprobadosCount / $alumnosConNotas->count()) * 100, 2)
            : 0.0;

        $conteoRendimiento = [
            'EXCELENTE' => $alumnosProgreso->where('rendimiento.nivel', 'EXCELENTE')->count(),
            'FAVORABLE' => $alumnosProgreso->where('rendimiento.nivel', 'FAVORABLE')->count(),
            'REGULAR'   => $alumnosProgreso->where('rendimiento.nivel', 'REGULAR')->count(),
            'EN RIESGO' => $alumnosProgreso->where('rendimiento.nivel', 'EN RIESGO')->count(),
        ];

        $usuarioDocente = $grupo->docente?->usuario;

        return [
            'grupo' => [
                'id' => $grupo->id,
                'codigo_grupo' => $grupo->codigo_grupo,
                'nivel' => $grupo->nivel,
                'capacidad_maxima' => $grupo->capacidad_maxima,
                'total_evaluaciones' => $evaluacionesGrupo->count(),
                'curso' => [
                    'id' => $grupo->curso?->id,
                    'nombre' => $grupo->curso?->nombre ?? 'N/A',
                    'tipo_ensenanza' => $grupo->curso?->tipo_ensenanza,
                ],
                'docente' => [
                    'id' => $grupo->docente?->id,
                    'codigo' => $grupo->docente?->codigo,
                    'nombre_completo' => $usuarioDocente ? trim($usuarioDocente->nombres . ' ' . $usuarioDocente->apellidos) : 'Sin asignar',
                    'nombres' => $usuarioDocente?->nombres,
                    'apellidos' => $usuarioDocente?->apellidos,
                    'email' => $usuarioDocente?->email,
                ],
                'horarios' => $grupo->horarios->map(fn ($h) => [
                    'dia_semana' => $h->dia_semana,
                    'hora_inicio' => substr((string) $h->hora_inicio, 0, 5),
                    'hora_fin' => substr((string) $h->hora_fin, 0, 5),
                    'sala' => $h->sala?->nombre ?? 'Sin sala',
                ]),
            ],
            'metricas_globales' => [
                'total_alumnos' => $totalAlumnos,
                'alumnos_evaluados' => $alumnosConNotas->count(),
                'promedio_grupal' => $promedioGrupal,
                'promedio_general' => $promedioGrupal,
                'asistencia_grupal' => $asistenciaGrupal,
                'porcentaje_asistencia' => $asistenciaGrupal,
                'porcentaje_aprobacion' => $porcentajeAprobacion,
                'tasa_aprobacion' => $porcentajeAprobacion,
                'aprobados' => $aprobadosCount,
                'reprobados' => $alumnosConNotas->count() - $aprobadosCount,
                'conteo_rendimiento' => $conteoRendimiento,
                'distribucion' => $conteoRendimiento,
                'alumnos_en_riesgo' => $conteoRendimiento['EN RIESGO'],
                'en_riesgo' => $conteoRendimiento['EN RIESGO'],
            ],
            'alumnos' => $alumnosProgreso,
        ];
    }

    /**
     * 3. listarGruposProgreso(Usuario $usuario):
     * Retorna los grupos disponibles con métricas de resumen para tarjetas de progreso
     * (Docente solo sus grupos, Admin/Secretaria todos).
     */
    public function listarGruposProgreso(Usuario $usuario): Collection
    {
        $grupos = Grupo::with([
            'curso',
            'docente.usuario',
            'horarios.sala',
            'evaluaciones',
            'inscripciones' => function ($q) {
                $q->where('estado', 'ACTIVA')
                    ->with([
                        'alumno.usuario',
                        'asistencias',
                        'calificaciones.evaluacion',
                    ]);
            },
        ])
        ->when($usuario->rol?->nombre === 'Docente', fn ($q) => $q->where('docente_id', $usuario->id))
        ->orderBy('codigo_grupo')
        ->get();

        return $grupos->map(function ($grupo) {
            $inscripciones = $grupo->inscripciones ?? collect();
            $totalAlumnos = $inscripciones->count();
            $evaluaciones = $grupo->evaluaciones ?? collect();

            $alumnosResumen = $inscripciones->map(function ($inscripcion) use ($evaluaciones) {
                return $this->formatearProgresoInscripcion($inscripcion, $evaluaciones);
            });

            $alumnosConNotas = $alumnosResumen->filter(fn ($a) => $a['calificaciones']['promedio_general'] !== null);

            $promedioGrupal = $alumnosConNotas->count() > 0
                ? round($alumnosConNotas->avg(fn ($a) => $a['calificaciones']['promedio_general']), 2)
                : null;

            $asistenciaGrupal = $totalAlumnos > 0
                ? round($alumnosResumen->avg(fn ($a) => $a['asistencias']['porcentaje']), 2)
                : 100.0;

            $aprobadosCount = $alumnosResumen->filter(function ($a) {
                $prom = $a['calificaciones']['promedio_general'];
                return $prom !== null && $prom >= 51;
            })->count();

            $porcentajeAprobacion = $alumnosConNotas->count() > 0
                ? round(($aprobadosCount / $alumnosConNotas->count()) * 100, 2)
                : 0.0;

            $conteoRendimiento = [
                'EXCELENTE' => $alumnosResumen->where('rendimiento.nivel', 'EXCELENTE')->count(),
                'FAVORABLE' => $alumnosResumen->where('rendimiento.nivel', 'FAVORABLE')->count(),
                'REGULAR'   => $alumnosResumen->where('rendimiento.nivel', 'REGULAR')->count(),
                'EN RIESGO' => $alumnosResumen->where('rendimiento.nivel', 'EN RIESGO')->count(),
            ];

            $usuarioDocente = $grupo->docente?->usuario;

            return [
                'id' => $grupo->id,
                'codigo_grupo' => $grupo->codigo_grupo,
                'nivel' => $grupo->nivel,
                'capacidad_maxima' => $grupo->capacidad_maxima,
                'total_evaluaciones' => $evaluaciones->count(),
                'curso' => [
                    'id' => $grupo->curso?->id,
                    'nombre' => $grupo->curso?->nombre ?? 'N/A',
                    'tipo_ensenanza' => $grupo->curso?->tipo_ensenanza,
                ],
                'docente' => [
                    'id' => $grupo->docente?->id,
                    'codigo' => $grupo->docente?->codigo,
                    'nombre_completo' => $usuarioDocente ? trim($usuarioDocente->nombres . ' ' . $usuarioDocente->apellidos) : 'Sin asignar',
                    'nombres' => $usuarioDocente?->nombres,
                    'apellidos' => $usuarioDocente?->apellidos,
                    'email' => $usuarioDocente?->email,
                ],
                'horarios' => $grupo->horarios->map(fn ($h) => [
                    'dia_semana' => $h->dia_semana,
                    'hora_inicio' => substr((string) $h->hora_inicio, 0, 5),
                    'hora_fin' => substr((string) $h->hora_fin, 0, 5),
                    'sala' => $h->sala?->nombre ?? 'Sin sala',
                ]),
                'total_alumnos' => $totalAlumnos,
                'alumnos_evaluados' => $alumnosConNotas->count(),
                'promedio_grupal' => $promedioGrupal,
                'promedio_general' => $promedioGrupal,
                'asistencia_grupal' => $asistenciaGrupal,
                'porcentaje_asistencia' => $asistenciaGrupal,
                'porcentaje_aprobacion' => $porcentajeAprobacion,
                'tasa_aprobacion' => $porcentajeAprobacion,
                'aprobados' => $aprobadosCount,
                'alumnos_en_riesgo' => $conteoRendimiento['EN RIESGO'],
                'en_riesgo' => $conteoRendimiento['EN RIESGO'],
                'conteo_rendimiento' => $conteoRendimiento,
                'semaforo' => $this->determinarNivelRendimiento($promedioGrupal, $asistenciaGrupal),
            ];
        });
    }

    /**
     * Retorna el progreso académico personal del alumno en todos sus cursos inscritos.
     */
    public function obtenerProgresoAlumno(Usuario $usuario): array
    {
        $inscripciones = Inscripcion::where('alumno_id', $usuario->id)
            ->with([
                'alumno.usuario',
                'grupo.curso',
                'grupo.docente.usuario',
                'asistencias.sesionClase',
                'calificaciones.evaluacion',
            ])
            ->orderByDesc('id')
            ->get();

        $cursosProgreso = $inscripciones->map(function ($inscripcion) {
            $evaluaciones = Evaluacion::where('grupo_id', $inscripcion->grupo_id)->get();
            return $this->formatearProgresoInscripcion($inscripcion, $evaluaciones);
        })->values();

        $cursosConNotas = $cursosProgreso->filter(fn ($c) => $c['calificaciones']['promedio_general'] !== null);

        $promedioGlobal = $cursosConNotas->count() > 0
            ? round($cursosConNotas->avg(fn ($c) => $c['calificaciones']['promedio_general']), 2)
            : null;

        $asistenciaGlobal = $cursosProgreso->count() > 0
            ? round($cursosProgreso->avg(fn ($c) => $c['asistencias']['porcentaje']), 2)
            : 100.0;

        $nivelGlobal = $this->determinarNivelRendimiento($promedioGlobal, $asistenciaGlobal);
        $metaGlobal = self::RENDIMIENTOS[$nivelGlobal] ?? self::RENDIMIENTOS['REGULAR'];

        $resumenPersonal = [
            'semaforo' => $nivelGlobal,
            'promedio_general' => $promedioGlobal,
            'porcentaje_asistencia' => $asistenciaGlobal,
            'total_cursos' => $cursosProgreso->count(),
            'total_evaluaciones' => $cursosProgreso->sum(fn ($c) => $c['calificaciones']['evaluaciones_rendidas'] ?? 0),
            'rendimiento' => $metaGlobal,
        ];

        return [
            'es_alumno' => true,
            'alumno' => [
                'id' => $usuario->id,
                'codigo' => $usuario->codigo,
                'nombre_completo' => trim($usuario->nombres . ' ' . $usuario->apellidos),
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'email' => $usuario->email,
                'ci' => $usuario->ci,
                'foto_url' => $usuario->foto_url,
            ],
            'resumen_personal' => $resumenPersonal,
            'metricas_globales' => [
                'total_cursos' => $cursosProgreso->count(),
                'cursos_activos' => $cursosProgreso->where('estado_inscripcion', 'ACTIVA')->count(),
                'promedio_global' => $promedioGlobal,
                'asistencia_global' => $asistenciaGlobal,
                'rendimiento_global' => [
                    'nivel' => $nivelGlobal,
                    'label' => $metaGlobal['label'],
                    'color' => $metaGlobal['color'],
                    'badge' => $metaGlobal['badge'],
                    'descripcion' => $metaGlobal['descripcion'],
                ],
                'conteo_rendimiento' => [
                    'EXCELENTE' => $cursosProgreso->where('rendimiento.nivel', 'EXCELENTE')->count(),
                    'FAVORABLE' => $cursosProgreso->where('rendimiento.nivel', 'FAVORABLE')->count(),
                    'REGULAR'   => $cursosProgreso->where('rendimiento.nivel', 'REGULAR')->count(),
                    'EN RIESGO' => $cursosProgreso->where('rendimiento.nivel', 'EN RIESGO')->count(),
                ],
            ],
            'cursos' => $cursosProgreso,
        ];
    }
}
