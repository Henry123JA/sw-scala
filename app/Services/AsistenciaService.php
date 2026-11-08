<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\SesionClase;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsistenciaService
{
    /**
     * Lista los grupos disponibles para gestión de asistencia con información para las tarjetas.
     * Docente: solo sus grupos asignados.
     * Propietario / Secretaria: todos los grupos de la academia.
     * Incluye si ya se tomó asistencia para la fecha de hoy.
     */
    public function listarGrupos(Usuario $usuario, ?string $fecha = null): Collection
    {
        $fechaConsulta = $fecha ?? now()->toDateString();

        $grupos = Grupo::with([
                'curso',
                'docente.usuario:id,nombres,apellidos',
                'horarios.sala',
            ])
            ->withCount(['inscripciones as alumnos_inscritos' => function ($q) {
                $q->where('estado', 'ACTIVA');
            }])
            ->when($usuario->rol?->nombre === 'Docente', fn ($q) => $q->where('docente_id', $usuario->id))
            ->orderBy('codigo_grupo')
            ->get();

        // Obtener sesiones de la fecha para determinar si ya se tomó asistencia
        $grupoIds = $grupos->pluck('id');
        $sesionesHoy = SesionClase::whereIn('grupo_id', $grupoIds)
            ->whereDate('fecha', $fechaConsulta)
            ->get()
            ->keyBy('grupo_id');

        $sesionesConAsistencias = Asistencia::whereIn('sesion_clase_id', $sesionesHoy->pluck('id'))
            ->select('sesion_clase_id', DB::raw('count(*) as total'))
            ->groupBy('sesion_clase_id')
            ->pluck('total', 'sesion_clase_id');

        return $grupos->map(function ($grupo) use ($sesionesHoy, $sesionesConAsistencias) {
            $sesion = $sesionesHoy->get($grupo->id);
            $totalMarcados = $sesion ? ($sesionesConAsistencias->get($sesion->id) ?? 0) : 0;
            $asistenciaTomadaHoy = $totalMarcados > 0;

            $grupo->asistencia_hoy_tomada = $asistenciaTomadaHoy;
            $grupo->sesion_hoy_id = $sesion?->id;
            $grupo->alumnos_marcados_hoy = $totalMarcados;

            return $grupo;
        });
    }

    private function autorizarGrupo(Grupo $grupo, Usuario $usuario): void
    {
        abort_unless(in_array($usuario->rol?->nombre, ['Propietario', 'Secretaria', 'Docente'], true), 403);
        abort_if($usuario->rol->nombre === 'Docente' && (int) $grupo->docente_id !== (int) $usuario->id, 403);
    }

    public function seleccionarSesion(array $datos, Usuario $usuario): SesionClase
    {
        $grupo = Grupo::findOrFail($datos['grupo_id']);
        $this->autorizarGrupo($grupo, $usuario);

        return SesionClase::firstOrCreate(['grupo_id' => $grupo->id, 'fecha' => $datos['fecha']]);
    }

    private function obtenerSesion(int $id, Usuario $usuario): SesionClase
    {
        $sesion = SesionClase::with('grupo.curso')->findOrFail($id);
        abort_unless($sesion->grupo, 404);
        $this->autorizarGrupo($sesion->grupo, $usuario);

        return $sesion;
    }

    public function listarAlumnos(int $sesionId, Usuario $usuario): array
    {
        $sesion = $this->obtenerSesion($sesionId, $usuario);
        $asistencias = Asistencia::where('sesion_clase_id', $sesion->id)->get()->keyBy('inscripcion_id');
        $alumnos = Inscripcion::with('alumno.usuario')->where('grupo_id', $sesion->grupo_id)->get()
            ->map(function ($inscripcion) use ($asistencias) {
                $registro = $asistencias->get($inscripcion->id);
                $persona = $inscripcion->alumno?->usuario;

                return [
                    'inscripcion_id' => $inscripcion->id,
                    'nombre' => $persona ? trim($persona->nombres.' '.$persona->apellidos) : 'Inscripción #'.$inscripcion->id,
                    'estado_inscripcion' => $inscripcion->estado,
                    'estado' => $registro?->estado,
                    'observacion' => $registro?->observacion,
                ];
            })->sortBy('nombre')->values();

        return ['sesion' => $sesion, 'alumnos' => $alumnos];
    }

    public function guardar(int $sesionId, array $asistencias, Usuario $usuario): void
    {
        DB::transaction(function () use ($sesionId, $asistencias, $usuario) {
            $sesion = $this->obtenerSesion($sesionId, $usuario);
            $ids = array_column($asistencias, 'inscripcion_id');
            $inscripciones = Inscripcion::where('grupo_id', $sesion->grupo_id)->whereIn('id', $ids)->lockForUpdate()->pluck('id');
            foreach ($asistencias as $indice => $fila) {
                if (! $inscripciones->contains($fila['inscripcion_id'])) {
                    throw ValidationException::withMessages(["asistencias.$indice.inscripcion_id" => 'La inscripción no pertenece al grupo de esta sesión.']);
                }
            }
            $ahora = now();
            $filas = array_map(fn ($fila) => [
                'sesion_clase_id' => $sesion->id,
                'inscripcion_id' => $fila['inscripcion_id'],
                'estado' => $fila['estado'],
                'observacion' => $fila['observacion'] ?? null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], $asistencias);
            Asistencia::upsert($filas, ['sesion_clase_id', 'inscripcion_id'], ['estado', 'observacion', 'updated_at']);
        });
    }

    /**
     * Obtiene el historial personal de asistencias de un alumno autenticado.
     * Solo devuelve las asistencias correspondientes a sus propias inscripciones.
     */
    public function obtenerAsistenciasAlumno(Usuario $usuario): array
    {
        $inscripciones = Inscripcion::with([
                'grupo.curso',
                'grupo.docente.usuario:id,nombres,apellidos',
            ])
            ->where('alumno_id', $usuario->id)
            ->get();

        $inscripcionIds = $inscripciones->pluck('id');

        $registros = Asistencia::with('sesionClase.grupo.curso')
            ->whereIn('inscripcion_id', $inscripcionIds)
            ->join('sesion_clase', 'asistencia.sesion_clase_id', '=', 'sesion_clase.id')
            ->select('asistencia.*')
            ->orderByDesc('sesion_clase.fecha')
            ->get();

        // Calcular resumen y métricas por cada curso en el que está o estuvo inscrito
        $resumenCursos = $inscripciones->map(function ($inscripcion) use ($registros) {
            $misAsistenciasCurso = $registros->where('inscripcion_id', $inscripcion->id);
            $totalClases = $misAsistenciasCurso->count();
            $presentes = $misAsistenciasCurso->where('estado', 'PRESENTE')->count();
            $faltas = $misAsistenciasCurso->where('estado', 'FALTA')->count();
            $licencias = $misAsistenciasCurso->where('estado', 'LICENCIA')->count();
            $atrasos = $misAsistenciasCurso->where('estado', 'ATRASO')->count();

            $porcentajeAsistencia = $totalClases > 0 
                ? round((($presentes + ($atrasos * 0.8)) / $totalClases) * 100, 1) 
                : 100.0;

            $docentePersona = $inscripcion->grupo?->docente?->usuario;

            return [
                'inscripcion_id' => $inscripcion->id,
                'codigo_grupo' => $inscripcion->grupo?->codigo_grupo ?? 'N/A',
                'curso' => $inscripcion->grupo?->curso?->nombre ?? 'Curso',
                'docente' => $docentePersona ? trim($docentePersona->nombres . ' ' . $docentePersona->apellidos) : 'Sin asignar',
                'estado_inscripcion' => $inscripcion->estado,
                'total_clases' => $totalClases,
                'presentes' => $presentes,
                'faltas' => $faltas,
                'licencias' => $licencias,
                'atrasos' => $atrasos,
                'porcentaje' => $porcentajeAsistencia,
            ];
        })->values();

        // Historial detallado ordenado cronológicamente
        $historial = $registros->map(function ($asistencia) {
            $sesion = $asistencia->sesionClase;
            $grupo = $sesion?->grupo;
            return [
                'id' => $asistencia->id,
                'fecha' => $sesion?->fecha,
                'curso' => $grupo?->curso?->nombre ?? 'Curso',
                'codigo_grupo' => $grupo?->codigo_grupo ?? 'N/A',
                'estado' => $asistencia->estado,
                'observacion' => $asistencia->observacion,
            ];
        })->values();

        return [
            'es_alumno' => true,
            'resumen_cursos' => $resumenCursos,
            'historial' => $historial,
        ];
    }

    /**
     * Obtiene el historial de sesiones y asistencias para un grupo específico.
     * Operativa diaria para Docente y Admin.
     */
    public function obtenerHistorialGrupo(int $grupoId, Usuario $usuario): array
    {
        $grupo = Grupo::with([
                'curso',
                'docente.usuario:id,nombres,apellidos',
                'horarios.sala',
            ])
            ->withCount(['inscripciones as alumnos_inscritos' => function ($q) {
                $q->where('estado', 'ACTIVA');
            }])
            ->findOrFail($grupoId);

        $this->autorizarGrupo($grupo, $usuario);

        $sesiones = SesionClase::where('grupo_id', $grupo->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $sesionIds = $sesiones->pluck('id');

        // Conteos por estado en cada sesión
        $conteos = Asistencia::whereIn('sesion_clase_id', $sesionIds)
            ->select('sesion_clase_id', 'estado', DB::raw('count(*) as total'))
            ->groupBy('sesion_clase_id', 'estado')
            ->get();

        $agrupados = [];
        foreach ($conteos as $c) {
            $agrupados[$c->sesion_clase_id][$c->estado] = (int) $c->total;
        }

        $sesionesHistorial = $sesiones->map(function ($sesion) use ($agrupados) {
            $estados = $agrupados[$sesion->id] ?? [];
            $presentes = $estados['PRESENTE'] ?? 0;
            $faltas = $estados['FALTA'] ?? 0;
            $licencias = $estados['LICENCIA'] ?? 0;
            $atrasos = $estados['ATRASO'] ?? 0;
            $totalCalificados = $presentes + $faltas + $licencias + $atrasos;

            $porcentajeAsistencia = $totalCalificados > 0
                ? round((($presentes + ($atrasos * 0.8)) / $totalCalificados) * 100, 1)
                : 0.0;

            return [
                'id' => $sesion->id,
                'fecha' => $sesion->fecha?->format('Y-m-d') ?? (string) $sesion->fecha,
                'presentes' => $presentes,
                'faltas' => $faltas,
                'licencias' => $licencias,
                'atrasos' => $atrasos,
                'total_calificados' => $totalCalificados,
                'porcentaje_asistencia' => $porcentajeAsistencia,
            ];
        })->values();

        // Resumen acumulado global del grupo para tarjetas KPI
        $totalSesiones = $sesionesHistorial->count();
        $totalPresentes = $sesionesHistorial->sum('presentes');
        $totalFaltas = $sesionesHistorial->sum('faltas');
        $totalLicencias = $sesionesHistorial->sum('licencias');
        $totalAtrasos = $sesionesHistorial->sum('atrasos');
        $granTotalCalificados = $totalPresentes + $totalFaltas + $totalLicencias + $totalAtrasos;
        $promedioAsistencia = $granTotalCalificados > 0
            ? round((($totalPresentes + ($totalAtrasos * 0.8)) / $granTotalCalificados) * 100, 1)
            : 0.0;

        $docentePersona = $grupo->docente?->usuario;

        return [
            'grupo' => [
                'id' => $grupo->id,
                'codigo_grupo' => $grupo->codigo_grupo,
                'nivel' => $grupo->nivel,
                'capacidad_maxima' => $grupo->capacidad_maxima,
                'alumnos_inscritos' => $grupo->alumnos_inscritos ?? 0,
                'curso' => [
                    'id' => $grupo->curso?->id,
                    'nombre' => $grupo->curso?->nombre ?? 'Curso',
                ],
                'docente' => $docentePersona ? [
                    'id' => $docentePersona->id,
                    'nombre_completo' => trim($docentePersona->nombres . ' ' . $docentePersona->apellidos),
                ] : null,
                'horarios' => $grupo->horarios->map(fn ($h) => [
                    'id' => $h->id,
                    'dia_semana' => $h->dia_semana,
                    'hora_inicio' => substr($h->hora_inicio, 0, 5),
                    'hora_fin' => substr($h->hora_fin, 0, 5),
                    'sala' => $h->sala?->nombre ?? null,
                ])->values(),
            ],
            'resumen' => [
                'total_sesiones' => $totalSesiones,
                'total_presentes' => $totalPresentes,
                'total_faltas' => $totalFaltas,
                'total_licencias' => $totalLicencias,
                'total_atrasos' => $totalAtrasos,
                'total_calificados' => $granTotalCalificados,
                'promedio_asistencia' => $promedioAsistencia,
            ],
            'sesiones' => $sesionesHistorial,
        ];
    }
}
