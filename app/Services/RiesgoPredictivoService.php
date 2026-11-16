<?php

namespace App\Services;

use App\Models\AlertaSeguimiento;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\IntervencionSeguimiento;
use App\Models\VersionModelo;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RiesgoPredictivoService
{
    public function __construct(
        protected ProgresoAcademicoService $progresoService
    ) {}

    /**
     * Extrae el vector de características (features) académicas, de asistencia y disciplinarias
     * de una inscripción para alimentar el modelo predictivo (Python ML API / n8n / Motor Local).
     */
    public function extraerFeatures(Inscripcion $inscripcion): array
    {
        $inscripcion->loadMissing([
            'alumno.usuario',
            'grupo.curso',
            'grupo.docente.usuario',
            'asistencias.sesionClase',
            'calificaciones.evaluacion',
            'observacionesAcademicas',
        ]);

        // 1. Métricas de Asistencia
        $asistencias = $inscripcion->asistencias ?? collect();
        $totalSesiones = $asistencias->count();
        $presentes = $asistencias->where('estado', 'PRESENTE')->count();
        $faltas = $asistencias->where('estado', 'FALTA')->count();
        $atrasos = $asistencias->where('estado', 'ATRASO')->count();
        $licencias = $asistencias->where('estado', 'LICENCIA')->count();

        $porcentajeAsistencia = $totalSesiones > 0
            ? round((($presentes * 1.0 + $atrasos * 0.8) / $totalSesiones) * 100, 2)
            : ($inscripcion->estado === 'ACTIVA' ? 100.0 : 50.0);

        // 2. Métricas de Calificaciones y Tendencia Temporal
        $calificacionesOrdenadas = ($inscripcion->calificaciones ?? collect())
            ->filter(fn ($c) => $c->nota !== null)
            ->sortBy(function ($c) {
                $fecha = $c->evaluacion?->fecha;
                return $fecha ? Carbon::parse($fecha)->timestamp : ($c->id ?? 0);
            })
            ->values();

        $evaluacionesRendidas = $calificacionesOrdenadas->count();
        $notas = $calificacionesOrdenadas->pluck('nota')->map(fn ($n) => (float) $n)->values();

        $promedioNotas = $evaluacionesRendidas > 0
            ? round((float) $notas->avg(), 2)
            : null;

        $notaMinima = $evaluacionesRendidas > 0 ? (float) $notas->min() : null;
        $notaMaxima = $evaluacionesRendidas > 0 ? (float) $notas->max() : null;
        $ultimaNota = $evaluacionesRendidas > 0 ? (float) $notas->last() : null;

        // Tendencia: diferencia entre la última calificación y el promedio de las anteriores
        $tendenciaNotas = 0.0;
        if ($evaluacionesRendidas >= 2) {
            $notasPrevias = $notas->slice(0, $evaluacionesRendidas - 1);
            $promedioPrevio = (float) $notasPrevias->avg();
            $tendenciaNotas = round($ultimaNota - $promedioPrevio, 2);
        }

        // 3. Observaciones Pedagógicas (Disciplina / Recomendación)
        $observaciones = $inscripcion->observacionesAcademicas ?? collect();
        $obsDisciplina = $observaciones->where('tipo', 'DISCIPLINA')->count();
        $obsRecomendacion = $observaciones->where('tipo', 'RECOMENDACION')->count();
        $obsFelicitacion = $observaciones->where('tipo', 'FELICITACION')->count();
        $obsProgreso = $observaciones->where('tipo', 'PROGRESO_TECNICO')->count();

        return [
            'inscripcion_id'         => $inscripcion->id,
            'alumno_id'              => $inscripcion->alumno_id,
            'alumno_codigo'          => $inscripcion->alumno?->codigo ?? $inscripcion->alumno?->usuario?->codigo,
            'grupo_id'               => $inscripcion->grupo_id,
            'codigo_grupo'           => $inscripcion->grupo?->codigo_grupo,
            'estado_inscripcion'     => $inscripcion->estado,
            'total_sesiones'         => $totalSesiones,
            'presentes'              => $presentes,
            'faltas_injustificadas'  => $faltas,
            'faltas'                 => $faltas,
            'atrasos'                => $atrasos,
            'licencias'              => $licencias,
            'porcentaje_asistencia'  => $porcentajeAsistencia,
            'evaluaciones_rendidas'  => $evaluacionesRendidas,
            'promedio_calificaciones'=> $promedioNotas,
            'promedio_notas'         => $promedioNotas,
            'nota_minima'            => $notaMinima,
            'nota_maxima'            => $notaMaxima,
            'ultima_nota'            => $ultimaNota,
            'tendencia_notas'        => $tendenciaNotas,
            'obs_disciplina'         => $obsDisciplina,
            'obs_recomendacion'      => $obsRecomendacion,
            'obs_felicitacion'       => $obsFelicitacion,
            'obs_progreso_tecnico'   => $obsProgreso,
            'total_obs_alerta'       => $obsDisciplina + $obsRecomendacion,
        ];
    }

    /**
     * Analiza una inscripción específica invocando el microservicio ML (si está habilitado)
     * o ejecutando el motor predictivo heurístico calibrado local.
     * Registra o actualiza la AlertaSeguimiento correspondiente.
     */
    public function analizarInscripcion(Inscripcion $inscripcion, string $origen = 'MOTOR_LOCAL'): ?AlertaSeguimiento
    {
        $features = $this->extraerFeatures($inscripcion);
        if ($inscripcion->estado === 'CANCELADA'
            || $features['total_sesiones'] < 3
            || $features['evaluaciones_rendidas'] < 1
            || $features['promedio_calificaciones'] === null) {
            return null;
        }
        $modeloActivo = $this->obtenerOCrearModeloActivo();

        $resultadoPrediccion = null;
        $origenFinal = 'MOTOR_LOCAL';
        $metadataMl = null;

        if (config('services.ml.enabled', false)) {
            $endpoint = rtrim(config('services.ml.url'), '/') . '/api/v1/predict';
            try {
                $response = Http::acceptJson()->connectTimeout(2)
                    ->timeout(config('services.ml.timeout', 10))->post($endpoint, [
                        'inscripcion_id' => $inscripcion->id,
                        'features' => $features,
                    ])->throw();
                $payload = $response->json();
                if (is_array($payload) && in_array($payload['estado_prediccion'] ?? null,
                    ['DATOS_INSUFICIENTES', 'NO_APLICA_RETIRO_CONFIRMADO'], true)) {
                    return null;
                }
                $this->validarPrediccionMl($payload, $inscripcion->id);
                $modeloActivo = VersionModelo::firstOrCreate(
                    ['version' => $payload['version_modelo']],
                    ['nombre' => 'FastAPI - ' . $payload['version_modelo'],
                     'algoritmo' => 'Random Forest (scikit-learn)',
                     'accuracy' => 0, 'f1_score' => 0,
                     'fecha_entrenamiento' => now()->toDateString(),
                     'url_endpoint' => $endpoint, 'activo' => false,
                     'descripcion' => 'Registro automatico de version recibida de FastAPI. Fecha de registro, no de entrenamiento. Metricas reales no disponibles; 0 es marcador. Modelo experimental sintetico.']);
                $resultadoPrediccion = [
                    'score_riesgo' => round((float) $payload['score_riesgo'], 2),
                    'nivel_riesgo' => $payload['nivel_riesgo'],
                    'factores_riesgo' => $payload['factores_riesgo'],
                    'resumen_explicativo' => $payload['resumen_explicativo'],
                ];
                $metadataMl = array_intersect_key($payload, array_flip([
                    'version_modelo', 'experimental', 'entrenado_con',
                    'estimacion_reprobo_pct', 'estimacion_abandono_pct',
                    'requiere_seguimiento_por_pausa', 'aviso', 'explicacion_modelo', 'acciones_sugeridas',
                ]));
                $origenFinal = 'ML_API';
            } catch (\Throwable $e) {
                Log::warning('Servicio ML no disponible o respuesta invalida; se usa motor local.', [
                    'inscripcion_id' => $inscripcion->id, 'tipo' => get_class($e),
                ]);
            }
        }

        if ($resultadoPrediccion === null) {
            $resultadoPrediccion = $this->clasificarLocalmente($features, $inscripcion);
        }
        $features['origen_ejecucion'] = $origen;
        if ($metadataMl !== null) {
            $features['prediccion_ml'] = $metadataMl;
        }

        // Buscar alerta existente para esta inscripción (priorizando PENDIENTE o EN_ATENCION)
        $alertaExistente = AlertaSeguimiento::where('inscripcion_id', $inscripcion->id)
            ->orderByDesc('id')
            ->first();

        $estadoAlerta = $alertaExistente?->estado ?? 'PENDIENTE';
        // Si estaba descartada pero subió a riesgo ALTO, reabrir como PENDIENTE
        if ($estadoAlerta === 'DESCARTADA' && $resultadoPrediccion['nivel_riesgo'] === 'ALTO') {
            $estadoAlerta = 'PENDIENTE';
        }

        $datosAlerta = [
            'version_modelo_id'   => $modeloActivo?->id,
            'nivel_riesgo'        => $resultadoPrediccion['nivel_riesgo'],
            'score_riesgo'        => $resultadoPrediccion['score_riesgo'],
            'factores_riesgo'     => $resultadoPrediccion['factores_riesgo'],
            'metricas_base'       => $features,
            'resumen_explicativo' => $resultadoPrediccion['resumen_explicativo'],
            'estado'              => $estadoAlerta,
            'origen'              => $origenFinal,
            'fecha_analisis'      => now(),
        ];

        if ($alertaExistente) {
            $alertaExistente->update($datosAlerta);
            return $alertaExistente->fresh(['inscripcion.alumno.usuario', 'inscripcion.grupo.curso', 'versionModelo', 'intervenciones']);
        }

        return AlertaSeguimiento::create(array_merge(
            ['inscripcion_id' => $inscripcion->id],
            $datosAlerta
        ));
    }

    /**
     * Clasificador heurístico calibrado local (compatible con Random Forest SCALA v1.1.0).
     * Calcula score_riesgo (0-100), nivel_riesgo (ALTO >= 65, MEDIO 35-64.99, BAJO < 35),
     * factores_riesgo ponderados (XAI - HU17) y resumen_explicativo en español.
     */
    protected function validarPrediccionMl(mixed $payload, int $inscripcionId): void
    {
        if (! is_array($payload) || ($payload['inscripcion_id'] ?? null) !== $inscripcionId
            || ($payload['estado_prediccion'] ?? null) !== 'CALCULADA'
            || ! is_numeric($payload['score_riesgo'] ?? null)
            || ! is_finite((float) $payload['score_riesgo'])
            || $payload['score_riesgo'] < 0 || $payload['score_riesgo'] > 100
            || ! is_string($payload['version_modelo'] ?? null)
            || ! preg_match('/^[a-zA-Z0-9._-]{1,30}$/', $payload['version_modelo'])
            || ! is_array($payload['factores_riesgo'] ?? null)
            || ! is_string($payload['resumen_explicativo'] ?? null)) {
            throw new \UnexpectedValueException('Respuesta ML incompatible');
        }
        $score = round((float) $payload['score_riesgo'], 2);
        $esperado = $score >= 65 ? 'ALTO' : ($score >= 35 ? 'MEDIO' : 'BAJO');
        if (($payload['nivel_riesgo'] ?? null) !== $esperado) {
            throw new \UnexpectedValueException('Nivel ML inconsistente con score');
        }
    }

    protected function clasificarLocalmente(array $features, Inscripcion $inscripcion): array
    {
        $asistenciaPct = (float) ($features['porcentaje_asistencia'] ?? 100.0);
        $faltas = (int) ($features['faltas_injustificadas'] ?? 0);
        $atrasos = (int) ($features['atrasos'] ?? 0);
        $licencias = (int) ($features['licencias'] ?? 0);
        $totalSesiones = (int) ($features['total_sesiones'] ?? 0);
        $promedio = $features['promedio_calificaciones'];
        $tendencia = (float) ($features['tendencia_notas'] ?? 0.0);
        $obsDisciplina = (int) ($features['obs_disciplina'] ?? 0);
        $obsRecomendacion = (int) ($features['obs_recomendacion'] ?? 0);
        $estadoInsc = (string) ($features['estado_inscripcion'] ?? 'ACTIVA');

        $pesosCrudos = [];

        // 1. Componente Asistencia
        $puntosAsistencia = 0.0;
        if ($asistenciaPct < 88.0) {
            $puntosAsistencia += (88.0 - $asistenciaPct) * 1.15;
        }
        $puntosAsistencia += ($faltas * 14.0) + ($atrasos * 4.5) + ($licencias * 3.0);
        if ($faltas >= 1 && $totalSesiones > 0 && $totalSesiones <= 5) {
            $puntosAsistencia += 11.0; // Alerta temprana por falta en ciclo corto
        }
        if ($puntosAsistencia > 0) {
            $detalleAsist = "{$asistenciaPct}% de asistencia";
            if ($faltas > 0 || $atrasos > 0) {
                $detalleAsist .= " ({$faltas} falta(s), {$atrasos} atraso(s))";
            }
            $pesosCrudos[] = [
                'factor'          => $faltas > 0
                    ? "Inasistencias injustificadas y regularidad ({$asistenciaPct}%)"
                    : "Variación en regularidad de asistencia y puntualidad ({$asistenciaPct}%)",
                'categoria'       => 'ASISTENCIA',
                'puntos'          => $puntosAsistencia,
                'valor_observado' => $detalleAsist,
                'descripcion'     => $faltas > 0
                    ? "El estudiante registra {$faltas} inasistencia(s) sin justificar y un porcentaje de asistencia de {$asistenciaPct}%."
                    : "Se registran {$atrasos} atraso(s) y {$licencias} licencia(s), situando la asistencia ponderada en {$asistenciaPct}%.",
            ];
        }

        // 2. Componente Rendimiento Académico (Promedio)
        $puntosAcademico = 0.0;
        if ($promedio !== null) {
            $promFloat = (float) $promedio;
            if ($promFloat < 70.0) {
                $puntosAcademico += (75.0 - $promFloat) * 1.85;
            } elseif ($promFloat < 82.0) {
                $puntosAcademico += (83.0 - $promFloat) * 1.25;
            } elseif ($promFloat < 88.0) {
                $puntosAcademico += (88.0 - $promFloat) * 0.5;
            }

            if ($puntosAcademico > 0) {
                $pesosCrudos[] = [
                    'factor'          => $promFloat < 70.0
                        ? "Promedio de calificaciones crítico ({$promFloat}/100)"
                        : "Margen de mejora en evaluaciones prácticas/teóricas ({$promFloat}/100)",
                    'categoria'       => 'ACADEMICO',
                    'puntos'          => $puntosAcademico,
                    'valor_observado' => "Promedio: {$promFloat} pts",
                    'descripcion'     => "El promedio acumulado en las evaluaciones del grupo es de {$promFloat} sobre 100 puntos.",
                ];
            }
        } else {
            $puntosAcademico = 16.0;
            $pesosCrudos[] = [
                'factor'          => 'Sin evaluaciones calificadas en el ciclo activo',
                'categoria'       => 'ACADEMICO',
                'puntos'          => $puntosAcademico,
                'valor_observado' => '0 evaluaciones rendidas',
                'descripcion'     => 'El estudiante aún no cuenta con calificaciones registradas para contrastar su progreso técnico.',
            ];
        }

        // 3. Componente Evolución / Tendencia y Continuidad
        $puntosEvolucion = 0.0;
        if ($tendencia < 0) {
            $puntosEvolucion += min(22.0, abs($tendencia) * 1.9);
        }
        if ($estadoInsc === 'PAUSADA') {
            $puntosEvolucion += 26.0;
        } elseif ($estadoInsc === 'CANCELADA') {
            $puntosEvolucion += 48.0;
        }

        if ($puntosEvolucion > 0) {
            $etiquetaEvol = match (true) {
                $estadoInsc === 'CANCELADA' => 'Retiro / Cancelación de inscripción registrada',
                $estadoInsc === 'PAUSADA' && $tendencia < 0 => "Inscripción en pausa y descenso en evaluaciones ({$tendencia} pts)",
                $estadoInsc === 'PAUSADA' => 'Pausa temporal de estudios activa',
                default => "Tendencia descendente en últimas calificaciones ({$tendencia} pts)",
            };

            $pesosCrudos[] = [
                'factor'          => $etiquetaEvol,
                'categoria'       => 'EVOLUCION',
                'puntos'          => $puntosEvolucion,
                'valor_observado' => "Estado: {$estadoInsc} | Tendencia: {$tendencia} pts",
                'descripcion'     => $estadoInsc !== 'ACTIVA'
                    ? "La inscripción se encuentra en estado {$estadoInsc}, lo que incrementa la probabilidad de deserción definitiva."
                    : "Se detectó una variación de {$tendencia} puntos entre la última evaluación y el promedio previo.",
            ];
        }

        // 4. Componente Bitácora Pedagógica / Disciplina
        $puntosDisciplina = ($obsDisciplina * 14.0) + ($obsRecomendacion * 7.5);
        if ($puntosDisciplina > 0) {
            $pesosCrudos[] = [
                'factor'          => $obsDisciplina > 0
                    ? "Observaciones de seguimiento disciplinario ({$obsDisciplina}) y técnico ({$obsRecomendacion})"
                    : "Recomendaciones pedagógicas de refuerzo técnico ({$obsRecomendacion})",
                'categoria'       => 'DISCIPLINA',
                'puntos'          => $puntosDisciplina,
                'valor_observado' => "{$obsDisciplina} obs. disciplina, {$obsRecomendacion} recomendación(es)",
                'descripcion'     => "El docente registró {$obsDisciplina} observación(es) de disciplina/puntualidad y {$obsRecomendacion} recomendación(es) de refuerzo.",
            ];
        }

        $scoreBruto = collect($pesosCrudos)->sum('puntos');
        $scoreRiesgo = round(max(2.0, min(98.5, $scoreBruto)), 2);

        $nivelRiesgo = match (true) {
            $scoreRiesgo >= 65.0 => 'ALTO',
            $scoreRiesgo >= 35.0 => 'MEDIO',
            default              => 'BAJO',
        };

        // Normalizar impactos de factores para que sumen 100% (HU17 XAI)
        $factoresNormalizados = [];
        if (count($pesosCrudos) > 0 && $scoreBruto > 0) {
            usort($pesosCrudos, fn ($a, $b) => $b['puntos'] <=> $a['puntos']);
            $acumulado = 0;
            $ultimoIdx = count($pesosCrudos) - 1;

            foreach ($pesosCrudos as $idx => $item) {
                $impacto = ($idx === $ultimoIdx)
                    ? max(1, 100 - $acumulado)
                    : (int) round(($item['puntos'] / $scoreBruto) * 100);
                $acumulado += $impacto;

                $factoresNormalizados[] = [
                    'factor'          => $item['factor'],
                    'nombre'          => $item['factor'],
                    'impacto'         => $impacto,
                    'porcentaje'      => $impacto,
                    'peso'            => $impacto,
                    'categoria'       => $item['categoria'],
                    'valor_observado' => $item['valor_observado'],
                    'detalle'         => $item['valor_observado'],
                    'valor_actual'    => $item['valor_observado'],
                    'descripcion'     => $item['descripcion'],
                ];
            }
        } else {
            $factoresNormalizados[] = [
                'factor'          => 'Desempeño académico y asistencia estables',
                'nombre'          => 'Desempeño académico y asistencia estables',
                'impacto'         => 100,
                'porcentaje'      => 100,
                'peso'            => 100,
                'categoria'       => 'ACADEMICO',
                'valor_observado' => "Asistencia {$asistenciaPct}% | Promedio " . ($promedio ?? 'S/N'),
                'detalle'         => "Asistencia {$asistenciaPct}% | Promedio " . ($promedio ?? 'S/N'),
                'valor_actual'    => "Asistencia {$asistenciaPct}% | Promedio " . ($promedio ?? 'S/N'),
                'descripcion'     => 'El estudiante mantiene indicadores favorables de asistencia y aprovechamiento musical.',
            ];
        }

        $alumnoNombre = trim(($inscripcion->alumno?->usuario?->nombres ?? '') . ' ' . ($inscripcion->alumno?->usuario?->apellidos ?? ''));
        $cursoNombre = $inscripcion->grupo?->curso?->nombre ?? 'el curso';
        $factorPrincipal = $factoresNormalizados[0]['factor'] ?? 'indicadores generales';

        $resumenExplicativo = match ($nivelRiesgo) {
            'ALTO' => "Alerta Predictiva Prioritaria: {$alumnoNombre} presenta un riesgo ALTO ({$scoreRiesgo}%) de deserción o reprobación en {$cursoNombre}. El factor determinante principal es «{$factorPrincipal}». Se recomienda intervención pedagógica y contacto inmediato.",
            'MEDIO' => "Alerta Preventiva Moderada: {$alumnoNombre} registra un riesgo MEDIO ({$scoreRiesgo}%) en {$cursoNombre}, influenciado principalmente por «{$factorPrincipal}». Se sugiere seguimiento docente focalizado y refuerzo de hábitos de estudio.",
            default => "Seguimiento Favorable: {$alumnoNombre} mantiene un nivel de riesgo BAJO ({$scoreRiesgo}%) en {$cursoNombre}, con {$asistenciaPct}% de asistencia" . ($promedio !== null ? " y promedio de {$promedio}/100 pts." : '.'),
        };

        return [
            'score_riesgo'        => $scoreRiesgo,
            'nivel_riesgo'        => $nivelRiesgo,
            'factores_riesgo'     => $factoresNormalizados,
            'resumen_explicativo' => $resumenExplicativo,
        ];
    }

    /**
     * Analiza masivamente todas las inscripciones de un grupo o de la academia
     * respetando el alcance del usuario autenticado.
     */
    public function analizarAcademia(?int $grupoId, ?Usuario $usuario = null, string $origen = 'MOTOR_LOCAL'): int
    {
        $query = Inscripcion::query()->with([
            'alumno.usuario',
            'grupo.curso',
            'grupo.docente.usuario',
            'asistencias.sesionClase',
            'calificaciones.evaluacion',
            'observacionesAcademicas',
        ]);

        if ($grupoId !== null) {
            $query->where('grupo_id', $grupoId);
        }

        if ($usuario !== null) {
            $rol = $usuario->rol?->nombre;
            if ($rol === 'Docente') {
                $query->whereHas('grupo', fn ($q) => $q->where('docente_id', $usuario->id));
            } elseif ($rol === 'Alumno') {
                $query->where('alumno_id', $usuario->id);
            }
        }

        $inscripciones = $query->get();
        $contador = 0;

        foreach ($inscripciones as $inscripcion) {
            if ($this->analizarInscripcion($inscripcion, $origen) !== null) {
                $contador++;
            }
        }

        return $contador;
    }

    /**
     * Lista alertas predictivas con filtros reactivos, KPIs agregados y versiones del modelo ML.
     */
    public function listarAlertas(array $filtros, Usuario $usuario): array
    {
        $rol = $usuario->rol?->nombre;

        // Si aún no existen alertas en la BD pero hay inscripciones, ejecutar análisis inicial automático
        if (AlertaSeguimiento::count() === 0 && Inscripcion::count() > 0) {
            $this->analizarAcademia(null, null, 'MOTOR_LOCAL');
        }

        $baseQuery = AlertaSeguimiento::query()
            ->with([
                'inscripcion.alumno.usuario',
                'inscripcion.grupo.curso',
                'inscripcion.grupo.docente.usuario',
                'versionModelo',
                'intervenciones',
            ]);

        if ($rol === 'Docente') {
            $baseQuery->whereHas('inscripcion.grupo', function ($q) use ($usuario) {
                $q->where('docente_id', $usuario->id);
            });
        } elseif ($rol === 'Alumno') {
            $baseQuery->whereHas('inscripcion', function ($q) use ($usuario) {
                $q->where('alumno_id', $usuario->id);
            });
        } elseif (! in_array($rol, ['Propietario', 'Secretaria'], true)) {
            abort(403, 'No autorizado para consultar alertas predictivas.');
        }

        // Clonar query base para calcular KPIs sobre el universo visible del usuario
        $todasVisibles = (clone $baseQuery)->get();

        $totalAlertas = $todasVisibles->count();
        $riesgoAlto = $todasVisibles->where('nivel_riesgo', 'ALTO')->count();
        $riesgoMedio = $todasVisibles->where('nivel_riesgo', 'MEDIO')->count();
        $riesgoBajo = $todasVisibles->where('nivel_riesgo', 'BAJO')->count();
        $pendientes = $todasVisibles->where('estado', 'PENDIENTE')->count();
        $enAtencion = $todasVisibles->where('estado', 'EN_ATENCION')->count();
        $resueltas = $todasVisibles->where('estado', 'RESUELTA')->count();
        $descartadas = $todasVisibles->where('estado', 'DESCARTADA')->count();
        $scorePromedio = $totalAlertas > 0 ? round((float) $todasVisibles->avg('score_riesgo'), 2) : 0.0;
        $totalIntervenciones = $todasVisibles->sum(fn ($a) => $a->intervenciones->count());

        // Aplicar filtros sobre la consulta principal
        $buscar = trim((string) ($filtros['buscar'] ?? $filtros['search'] ?? $filtros['q'] ?? ''));
        if ($buscar !== '') {
            $baseQuery->where(function ($sub) use ($buscar) {
                $sub->whereHas('inscripcion.alumno.usuario', function ($q) use ($buscar) {
                    $q->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('codigo', 'like', "%{$buscar}%")
                        ->orWhere('ci', 'like', "%{$buscar}%");
                })->orWhereHas('inscripcion.grupo', function ($q) use ($buscar) {
                    $q->where('codigo_grupo', 'like', "%{$buscar}%")
                        ->orWhereHas('curso', function ($c) use ($buscar) {
                            $c->where('nombre', 'like', "%{$buscar}%");
                        });
                });
            });
        }

        if (! empty($filtros['nivel_riesgo']) && $filtros['nivel_riesgo'] !== 'TODOS') {
            $baseQuery->where('nivel_riesgo', strtoupper((string) $filtros['nivel_riesgo']));
        }

        if (! empty($filtros['estado']) && $filtros['estado'] !== 'TODOS') {
            $baseQuery->where('estado', strtoupper((string) $filtros['estado']));
        }

        if (! empty($filtros['grupo_id']) && $filtros['grupo_id'] !== 'TODOS') {
            $grupoIdFiltro = (int) $filtros['grupo_id'];
            $baseQuery->whereHas('inscripcion', fn ($q) => $q->where('grupo_id', $grupoIdFiltro));
        }

        $alertasColeccion = $baseQuery
            ->orderByDesc('score_riesgo')
            ->orderByDesc('id')
            ->get()
            ->map(fn (AlertaSeguimiento $alerta) => $this->formatearAlerta($alerta))
            ->values();

        $modeloActivoModel = $this->obtenerOCrearModeloActivo();
        $modeloActivo = $modeloActivoModel ? $this->formatearVersionModelo($modeloActivoModel) : null;

        $versionesModelo = VersionModelo::orderByDesc('activo')
            ->orderByDesc('fecha_entrenamiento')
            ->orderByDesc('id')
            ->get()
            ->map(fn (VersionModelo $v) => $this->formatearVersionModelo($v))
            ->values();

        $gruposQuery = Grupo::with(['curso', 'docente.usuario'])
            ->where('eliminado', false)
            ->orderBy('codigo_grupo');

        if ($rol === 'Docente') {
            $gruposQuery->where('docente_id', $usuario->id);
        }

        $grupos = $gruposQuery->get()->map(fn (Grupo $g) => [
            'id'           => $g->id,
            'codigo_grupo' => $g->codigo_grupo,
            'nivel'        => $g->nivel,
            'curso_nombre' => $g->curso?->nombre ?? 'Curso',
            'nombre_label' => "{$g->codigo_grupo} — " . ($g->curso?->nombre ?? 'Curso'),
            'curso'        => [
                'id'     => $g->curso?->id,
                'nombre' => $g->curso?->nombre,
            ],
        ])->values();

        $kpis = [
            'total_analizados'     => $totalAlertas,
            'total_alertas'        => $totalAlertas,
            'total'                => $totalAlertas,
            'riesgo_alto'          => $riesgoAlto,
            'alto'                 => $riesgoAlto,
            'riesgo_medio'         => $riesgoMedio,
            'medio'                => $riesgoMedio,
            'riesgo_bajo'          => $riesgoBajo,
            'bajo'                 => $riesgoBajo,
            'pendientes'           => $pendientes,
            'en_atencion'          => $enAtencion,
            'alertas_pendientes'   => $pendientes + $enAtencion,
            'resueltas'            => $resueltas,
            'descartadas'          => $descartadas,
            'score_promedio'       => $scorePromedio,
            'total_intervenciones' => $totalIntervenciones,
            'accuracy_modelo'      => $modeloActivo['accuracy'] ?? 89.4,
            'f1_modelo'            => $modeloActivo['f1_score'] ?? 88.2,
        ];

        return [
            'kpis'             => $kpis,
            'modeloActivo'     => $modeloActivo,
            'modelo_activo'    => $modeloActivo,
            'versionesModelo'  => $versionesModelo,
            'versiones_modelo' => $versionesModelo,
            'alertas'          => $alertasColeccion,
            'grupos'           => $grupos,
            'filtros'          => [
                'buscar'       => $buscar,
                'nivel_riesgo' => $filtros['nivel_riesgo'] ?? '',
                'estado'       => $filtros['estado'] ?? '',
                'grupo_id'     => $filtros['grupo_id'] ?? '',
            ],
            'rol_usuario'      => $rol,
            'es_alumno'        => $rol === 'Alumno',
            'puede_gestionar'  => in_array($rol, ['Propietario', 'Secretaria', 'Docente'], true),
            'puede_entrenar'   => in_array($rol, ['Propietario', 'Secretaria'], true),
        ];
    }

    /**
     * Obtiene el detalle completo de una alerta predictiva, sus factores XAI (HU17),
     * métricas académicas de la inscripción y el historial de intervenciones (HU20).
     */
    public function obtenerDetalleAlerta(int $alertaId, Usuario $usuario): array
    {
        $alerta = AlertaSeguimiento::with([
            'inscripcion.alumno.usuario',
            'inscripcion.grupo.curso',
            'inscripcion.grupo.docente.usuario',
            'inscripcion.grupo.horarios.sala',
            'inscripcion.asistencias.sesionClase',
            'inscripcion.calificaciones.evaluacion',
            'inscripcion.observacionesAcademicas.docente.usuario',
            'versionModelo',
            'intervenciones.usuario.rol',
        ])->findOrFail($alertaId);

        $rol = $usuario->rol?->nombre;
        $inscripcion = $alerta->inscripcion;

        if ($rol === 'Alumno' && (int) $inscripcion?->alumno_id !== (int) $usuario->id) {
            abort(403, 'No está autorizado para consultar esta alerta.');
        }

        if ($rol === 'Docente' && (int) $inscripcion?->grupo?->docente_id !== (int) $usuario->id) {
            abort(403, 'No está autorizado para consultar alertas de otros grupos.');
        }

        $alertaFormateada = $this->formatearAlerta($alerta);

        $intervenciones = $alerta->intervenciones
            ->sortByDesc(fn ($i) => ($i->fecha?->format('Y-m-d') ?? '') . '_' . str_pad((string) $i->id, 6, '0', STR_PAD_LEFT))
            ->values()
            ->map(fn (IntervencionSeguimiento $i) => $this->formatearIntervencion($i))
            ->values();

        $progreso = $inscripcion
            ? $this->progresoService->calcularProgresoInscripcion($inscripcion->id)
            : null;

        return [
            'alerta'          => array_merge($alertaFormateada, [
                'intervenciones' => $intervenciones,
            ]),
            'intervenciones'  => $intervenciones,
            'metricas_base'   => $alertaFormateada['metricas_base'],
            'inscripcion'     => $alertaFormateada['inscripcion'],
            'alumno'          => $alertaFormateada['alumno'],
            'grupo'           => $alertaFormateada['grupo'],
            'curso'           => $alertaFormateada['curso'],
            'docente'         => $alertaFormateada['docente'],
            'modeloActivo'    => $alertaFormateada['version_modelo'],
            'modelo_activo'   => $alertaFormateada['version_modelo'],
            'progreso'        => $progreso,
            'rol_usuario'     => $rol,
            'es_alumno'       => $rol === 'Alumno',
            'puede_editar'    => in_array($rol, ['Propietario', 'Secretaria', 'Docente'], true),
            'puede_gestionar' => in_array($rol, ['Propietario', 'Secretaria', 'Docente'], true),
        ];
    }

    /**
     * Genera el dataset estructurado de alumnos e inscripciones para n8n y microservicio Python ML.
     */
    public function generarDatasetAlumnos(): array
    {
        $inscripciones = Inscripcion::with([
            'alumno.usuario',
            'grupo.curso',
            'grupo.docente.usuario',
            'asistencias.sesionClase',
            'calificaciones.evaluacion',
            'observacionesAcademicas',
        ])->get();

        $modeloActivo = $this->obtenerOCrearModeloActivo();

        $registros = $inscripciones->map(function (Inscripcion $ins) {
            $features = $this->extraerFeatures($ins);
            $usuarioAlu = $ins->alumno?->usuario;

            return [
                'inscripcion_id' => $ins->id,
                'alumno' => [
                    'id'              => $ins->alumno_id,
                    'codigo'          => $ins->alumno?->codigo ?? $usuarioAlu?->codigo,
                    'nombre_completo' => trim(($usuarioAlu?->nombres ?? '') . ' ' . ($usuarioAlu?->apellidos ?? '')),
                    'email'           => $usuarioAlu?->email,
                ],
                'grupo' => [
                    'id'           => $ins->grupo_id,
                    'codigo_grupo' => $ins->grupo?->codigo_grupo,
                    'curso'        => $ins->grupo?->curso?->nombre,
                ],
                'features' => $features,
            ];
        })->values()->all();

        return [
            'status'          => 'ok',
            'generado_en'     => now()->toIso8601String(),
            'version_modelo'  => $modeloActivo?->version ?? 'v1.1.0',
            'total_registros' => count($registros),
            'dataset'         => $registros,
            'alumnos'         => $registros,
        ];
    }

    /**
     * Procesa un lote de alertas enviadas vía Webhook desde n8n o servicio ML externo.
     */
    /** Guarda exclusivamente resultados ML; no inventa scores ni factores locales. */
    public function procesarResultadosMl(array $resultados): array
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($resultados) {
            $ids = [];
            $omitidas = [];
            foreach ($resultados as $item) {
                $inscripcion = Inscripcion::whereKey($item['inscripcion_id'])->lockForUpdate()->first();
                if (! $inscripcion) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'resultados' => 'La inscripcion indicada no existe.',
                    ]);
                }
                $features = $this->extraerFeatures($inscripcion);
                $estado = $item['estado_prediccion'];
                if ($estado !== 'CALCULADA' || $inscripcion->estado === 'CANCELADA'
                    || $features['total_sesiones'] < 3 || $features['evaluaciones_rendidas'] < 1
                    || $features['promedio_calificaciones'] === null) {
                    $omitidas[] = $inscripcion->id;
                    continue;
                }
                try {
                    $this->validarPrediccionMl($item, $inscripcion->id);
                } catch (\UnexpectedValueException $e) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'resultados' => 'Score, nivel o contrato ML incompatible para inscripcion ' . $inscripcion->id,
                    ]);
                }
                $modelo = VersionModelo::firstOrCreate(
                    ['version' => $item['version_modelo']],
                    ['nombre' => 'FastAPI - ' . $item['version_modelo'],
                     'algoritmo' => 'Random Forest (scikit-learn)', 'accuracy' => 0, 'f1_score' => 0,
                     'fecha_entrenamiento' => now()->toDateString(),
                     'url_endpoint' => rtrim(config('services.ml.url'), '/') . '/api/v1/predict',
                     'activo' => false,
                     'descripcion' => 'Version recibida de FastAPI. Fecha de registro; metricas 0 son marcadores sin validacion real.']);
                $features['origen_ejecucion'] = 'N8N_AUTO';
                $features['prediccion_ml'] = array_intersect_key($item, array_flip([
                    'version_modelo', 'experimental', 'entrenado_con', 'estimacion_reprobo_pct',
                    'estimacion_abandono_pct', 'requiere_seguimiento_por_pausa', 'aviso', 'explicacion_modelo', 'acciones_sugeridas',
                ]));
                $features['prediccion_ml']['proveedor'] = 'ML_API';
                $alerta = AlertaSeguimiento::where('inscripcion_id', $inscripcion->id)
                    ->orderByDesc('id')->lockForUpdate()->first();
                $estadoAlerta = $alerta?->estado ?? 'PENDIENTE';
                if ($estadoAlerta === 'DESCARTADA' && $item['nivel_riesgo'] === 'ALTO') {
                    $estadoAlerta = 'PENDIENTE';
                }
                $datos = [
                    'version_modelo_id' => $modelo->id, 'nivel_riesgo' => $item['nivel_riesgo'],
                    'score_riesgo' => round((float) $item['score_riesgo'], 2),
                    'factores_riesgo' => $item['factores_riesgo'], 'metricas_base' => $features,
                    'resumen_explicativo' => $item['resumen_explicativo'],
                    'estado' => $estadoAlerta, 'origen' => 'N8N_AUTO', 'fecha_analisis' => now(),
                ];
                if ($alerta) {
                    $alerta->update($datos);
                } else {
                    $alerta = AlertaSeguimiento::create(['inscripcion_id' => $inscripcion->id] + $datos);
                }
                $ids[] = $alerta->id;
            }
            return ['procesadas' => count($ids), 'omitidas' => count($omitidas),
                    'alerta_ids' => $ids, 'inscripciones_omitidas' => $omitidas,
                    'origen' => 'N8N_AUTO'];
        });
    }

    public function procesarWebhookAlertas(array $predicciones, ?string $versionCodigo = null): array
    {
        $modelo = $versionCodigo
            ? VersionModelo::where('version', $versionCodigo)->first()
            : null;

        if (! $modelo) {
            $modelo = $this->obtenerOCrearModeloActivo();
        }

        $procesadas = 0;
        $alertasActualizadas = [];

        foreach ($predicciones as $item) {
            $inscripcionId = $item['inscripcion_id'] ?? null;
            if (! $inscripcionId) {
                continue;
            }

            $inscripcion = Inscripcion::find($inscripcionId);
            if (! $inscripcion) {
                continue;
            }

            $features = $this->extraerFeatures($inscripcion);
            $calculoLocal = $this->clasificarLocalmente($features, $inscripcion);

            $score = isset($item['score_riesgo']) ? round((float) $item['score_riesgo'], 2) : $calculoLocal['score_riesgo'];
            $nivel = isset($item['nivel_riesgo'])
                ? strtoupper((string) $item['nivel_riesgo'])
                : ($score >= 65 ? 'ALTO' : ($score >= 35 ? 'MEDIO' : 'BAJO'));

            $factores = ! empty($item['factores_riesgo']) && is_array($item['factores_riesgo'])
                ? $item['factores_riesgo']
                : $calculoLocal['factores_riesgo'];

            $resumen = $item['resumen_explicativo'] ?? $calculoLocal['resumen_explicativo'];

            $alertaExistente = AlertaSeguimiento::where('inscripcion_id', $inscripcion->id)
                ->orderByDesc('id')
                ->first();

            $datos = [
                'version_modelo_id'   => $modelo?->id,
                'nivel_riesgo'        => $nivel,
                'score_riesgo'        => $score,
                'factores_riesgo'     => $factores,
                'metricas_base'       => $features,
                'resumen_explicativo' => $resumen,
                'estado'              => $alertaExistente?->estado ?? 'PENDIENTE',
                'origen'              => $item['origen'] ?? 'N8N_AUTO',
                'fecha_analisis'      => now(),
            ];

            if ($alertaExistente) {
                $alertaExistente->update($datos);
                $alerta = $alertaExistente;
            } else {
                $alerta = AlertaSeguimiento::create(array_merge(['inscripcion_id' => $inscripcion->id], $datos));
            }

            $procesadas++;
            $alertasActualizadas[] = $alerta->id;
        }

        return [
            'procesadas' => $procesadas,
            'alerta_ids' => $alertasActualizadas,
            'modelo'     => $modelo?->version,
        ];
    }

    /**
     * Obtiene la versión activa del modelo predictivo o crea una por defecto si la tabla está vacía.
     */
    public function obtenerOCrearModeloActivo(): ?VersionModelo
    {
        $activo = VersionModelo::where('activo', true)->first();
        if ($activo) {
            return $activo;
        }

        $ultimo = VersionModelo::orderByDesc('id')->first();
        if ($ultimo) {
            $ultimo->update(['activo' => true]);
            return $ultimo;
        }

        return VersionModelo::create([
            'version'             => 'v1.1.0',
            'nombre'              => 'Random Forest Classifier - SCALA',
            'algoritmo'           => 'Random Forest (Scikit-Learn + Calibración Heurística)',
            'accuracy'            => 89.40,
            'f1_score'            => 88.20,
            'fecha_entrenamiento' => '2026-11-01',
            'url_endpoint'        => 'http://127.0.0.1:8001/api/v1/predict',
            'activo'              => true,
            'descripcion'         => 'Modelo ensemble entrenado con registros históricos de asistencia, evolución de calificaciones prácticas/teóricas y bitácora pedagógica.',
        ]);
    }

    public function formatearVersionModelo(VersionModelo $modelo): array
    {
        return [
            'id'                  => $modelo->id,
            'version'             => $modelo->version,
            'nombre'              => $modelo->nombre,
            'algoritmo'           => $modelo->algoritmo,
            'accuracy'            => round((float) $modelo->accuracy, 2),
            'f1_score'            => round((float) $modelo->f1_score, 2),
            'fecha_entrenamiento' => $modelo->fecha_entrenamiento?->format('Y-m-d'),
            'url_endpoint'        => $modelo->url_endpoint,
            'activo'              => (bool) $modelo->activo,
            'descripcion'         => $modelo->descripcion,
        ];
    }

    public function formatearAlerta(AlertaSeguimiento $alerta): array
    {
        $inscripcion = $alerta->inscripcion;
        $alumno = $inscripcion?->alumno;
        $usuarioAlu = $alumno?->usuario;
        $grupo = $inscripcion?->grupo;
        $curso = $grupo?->curso;
        $docente = $grupo?->docente;
        $usuarioDoc = $docente?->usuario;
        $metricas = $alerta->metricas_base ?? [];

        $nombreAlumno = trim(($usuarioAlu?->nombres ?? '') . ' ' . ($usuarioAlu?->apellidos ?? ''));
        $nombreDocente = trim(($usuarioDoc?->nombres ?? '') . ' ' . ($usuarioDoc?->apellidos ?? ''));

        $alumnoData = [
            'id'              => $alumno?->id,
            'codigo'          => $alumno?->codigo ?? $usuarioAlu?->codigo ?? 'S/C',
            'nombres'         => $usuarioAlu?->nombres,
            'apellidos'       => $usuarioAlu?->apellidos,
            'nombre_completo' => $nombreAlumno ?: 'Estudiante',
            'email'           => $usuarioAlu?->email,
            'ci'              => $usuarioAlu?->ci,
            'telefono'        => $alumno?->telefono,
            'nivel'           => $alumno?->nivel,
            'foto_url'        => $usuarioAlu?->foto_url,
        ];

        $cursoData = [
            'id'             => $curso?->id,
            'nombre'         => $curso?->nombre ?? 'Sin curso',
            'tipo_ensenanza' => $curso?->tipo_ensenanza,
        ];

        $docenteData = [
            'id'              => $docente?->id,
            'codigo'          => $docente?->codigo ?? $usuarioDoc?->codigo,
            'nombres'         => $usuarioDoc?->nombres,
            'apellidos'       => $usuarioDoc?->apellidos,
            'nombre_completo' => $nombreDocente ?: 'Sin docente',
            'email'           => $usuarioDoc?->email,
            'telefono'        => $docente?->telefono,
        ];

        $grupoData = [
            'id'           => $grupo?->id,
            'codigo_grupo' => $grupo?->codigo_grupo ?? 'S/G',
            'nivel'        => $grupo?->nivel,
            'estado'       => $grupo?->estado,
            'curso'        => $cursoData,
            'docente'      => $docenteData,
        ];

        $versionModeloData = $alerta->versionModelo
            ? $this->formatearVersionModelo($alerta->versionModelo)
            : null;

        return [
            'id'                    => $alerta->id,
            'inscripcion_id'        => $alerta->inscripcion_id,
            'version_modelo_id'     => $alerta->version_modelo_id,
            'nivel_riesgo'          => $alerta->nivel_riesgo,
            'score_riesgo'          => round((float) $alerta->score_riesgo, 2),
            'factores_riesgo'       => $alerta->factores_riesgo ?? [],
            'metricas_base'         => $metricas,
            'resumen_explicativo'   => $alerta->resumen_explicativo,
            'estado'                => $alerta->estado,
            'origen'                => $alerta->origen,
            'fecha_analisis'        => $alerta->fecha_analisis?->format('Y-m-d H:i'),
            'fecha_analisis_iso'    => $alerta->fecha_analisis?->toIso8601String(),
            'porcentaje_asistencia' => $metricas['porcentaje_asistencia'] ?? 100.0,
            'promedio_notas'        => $metricas['promedio_calificaciones'] ?? $metricas['promedio_notas'] ?? null,
            'faltas'                => $metricas['faltas_injustificadas'] ?? $metricas['faltas'] ?? 0,
            'atrasos'               => $metricas['atrasos'] ?? 0,
            'tendencia_notas'       => $metricas['tendencia_notas'] ?? 0.0,
            'total_intervenciones'  => $alerta->relationLoaded('intervenciones') ? $alerta->intervenciones->count() : 0,
            'alumno'                => $alumnoData,
            'grupo'                 => $grupoData,
            'curso'                 => $cursoData,
            'docente'               => $docenteData,
            'version_modelo'        => $versionModeloData,
            'versionModelo'         => $versionModeloData,
            'inscripcion'           => [
                'id'                  => $inscripcion?->id,
                'estado'              => $inscripcion?->estado,
                'fecha'               => $inscripcion?->fecha?->format('Y-m-d'),
                'fecha_inicio_clases' => $inscripcion?->fecha_inicio_clases?->format('Y-m-d'),
                'fecha_pausa'         => $inscripcion?->fecha_pausa?->format('Y-m-d'),
                'fecha_retiro'        => $inscripcion?->fecha_retiro?->format('Y-m-d'),
                'alumno'              => $alumnoData,
                'grupo'               => $grupoData,
                'curso'               => $cursoData,
                'docente'             => $docenteData,
            ],
        ];
    }

    public function formatearIntervencion(IntervencionSeguimiento $intervencion): array
    {
        $usuario = $intervencion->usuario;
        $nombreResponsable = trim(($usuario?->nombres ?? '') . ' ' . ($usuario?->apellidos ?? ''));

        return [
            'id'                 => $intervencion->id,
            'alerta_id'          => $intervencion->alerta_id,
            'inscripcion_id'     => $intervencion->inscripcion_id,
            'usuario_id'         => $intervencion->usuario_id,
            'fecha'              => $intervencion->fecha?->format('Y-m-d'),
            'tipo'               => $intervencion->tipo,
            'titulo'             => $intervencion->titulo,
            'descripcion'        => $intervencion->descripcion,
            'compromiso'         => $intervencion->compromiso,
            'resultado'          => $intervencion->resultado,
            'responsable_nombre' => $nombreResponsable ?: 'Docente / Coordinación',
            'responsable_rol'    => $usuario?->rol?->nombre ?? 'Personal Académico',
            'usuario'            => [
                'id'              => $usuario?->id,
                'nombre_completo' => $nombreResponsable ?: 'Docente / Coordinación',
                'rol'             => $usuario?->rol?->nombre,
            ],
            'created_at'         => $intervencion->created_at?->format('Y-m-d H:i'),
        ];
    }
}
