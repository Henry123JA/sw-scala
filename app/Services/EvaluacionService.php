<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluacionService
{
    /**
     * Valida permisos sobre un grupo para Docente, Secretaria o Propietario.
     */
    private function autorizarGrupo(Grupo $grupo, Usuario $usuario): void
    {
        abort_unless(in_array($usuario->rol?->nombre, ['Propietario', 'Secretaria', 'Docente'], true), 403, 'No autorizado.');
        abort_if($usuario->rol->nombre === 'Docente' && (int) $grupo->docente_id !== (int) $usuario->id, 403, 'No tiene acceso a este grupo.');
    }

    /**
     * Lista los grupos disponibles para gestión de evaluaciones.
     * Docente: solo sus grupos asignados.
     * Propietario / Secretaria: todos los grupos de la academia.
     */
    public function listarGrupos(Usuario $usuario): Collection
    {
        return Grupo::with([
                'curso',
                'docente.usuario:id,nombres,apellidos',
                'horarios.sala',
                'evaluaciones' => function ($q) {
                    $q->withCount('calificaciones')->orderByDesc('fecha')->orderByDesc('id');
                },
            ])
            ->withCount([
                'inscripciones as alumnos_inscritos' => function ($q) {
                    $q->where('estado', 'ACTIVA');
                },
                'evaluaciones as total_evaluaciones',
            ])
            ->when($usuario->rol?->nombre === 'Docente', fn ($q) => $q->where('docente_id', $usuario->id))
            ->orderBy('codigo_grupo')
            ->get();
    }

    /**
     * Lista las evaluaciones de un grupo específico.
     */
    public function listarEvaluacionesGrupo(int $grupoId, Usuario $usuario): Collection
    {
        $grupo = Grupo::findOrFail($grupoId);
        $this->autorizarGrupo($grupo, $usuario);

        return Evaluacion::where('grupo_id', $grupo->id)
            ->withCount('calificaciones')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Crea una nueva evaluación para un grupo.
     */
    public function crearEvaluacion(int $grupoId, array $datos, Usuario $usuario): Evaluacion
    {
        $grupo = Grupo::findOrFail($grupoId);
        $this->autorizarGrupo($grupo, $usuario);

        return Evaluacion::create([
            'grupo_id' => $grupo->id,
            'nombre' => trim($datos['nombre']),
            'tipo' => $datos['tipo'],
            'fecha' => $datos['fecha'],
            'ponderacion' => $datos['ponderacion'] ?? 100.00,
            'descripcion' => $datos['descripcion'] ?? null,
        ]);
    }

    /**
     * Obtiene la planilla de calificaciones para ingresar notas de una evaluación.
     * Incluye todos los alumnos inscritos en el grupo y su nota actual si existe.
     */
    public function obtenerPlanillaCalificaciones(int $evaluacionId, Usuario $usuario): array
    {
        $evaluacion = Evaluacion::with([
            'grupo.curso',
            'grupo.docente.usuario:id,nombres,apellidos',
        ])->findOrFail($evaluacionId);

        abort_unless($evaluacion->grupo, 404, 'Grupo no encontrado.');
        $this->autorizarGrupo($evaluacion->grupo, $usuario);

        $calificaciones = Calificacion::where('evaluacion_id', $evaluacion->id)
            ->get()
            ->keyBy('inscripcion_id');

        $inscripciones = Inscripcion::with('alumno.usuario')
            ->where('grupo_id', $evaluacion->grupo_id)
            ->get();

        $alumnos = $inscripciones->map(function ($inscripcion) use ($calificaciones) {
            $registro = $calificaciones->get($inscripcion->id);
            $persona = $inscripcion->alumno?->usuario;

            return [
                'inscripcion_id' => $inscripcion->id,
                'codigo_alumno' => $inscripcion->alumno?->codigo ?? 'N/A',
                'nombre' => $persona ? trim($persona->nombres . ' ' . $persona->apellidos) : 'Inscripción #' . $inscripcion->id,
                'estado_inscripcion' => $inscripcion->estado,
                'nota' => $registro?->nota !== null ? (float) $registro->nota : null,
                'observaciones' => $registro?->observaciones ?? '',
            ];
        })->sortBy('nombre')->values();

        return [
            'evaluacion' => $evaluacion,
            'alumnos' => $alumnos,
        ];
    }

    /**
     * Guarda en lote las calificaciones de los alumnos mediante upsert transaccional.
     */
    public function guardarCalificaciones(int $evaluacionId, array $calificaciones, Usuario $usuario): void
    {
        DB::transaction(function () use ($evaluacionId, $calificaciones, $usuario) {
            $evaluacion = Evaluacion::with('grupo')->findOrFail($evaluacionId);
            $this->autorizarGrupo($evaluacion->grupo, $usuario);

            $inscripcionIds = array_column($calificaciones, 'inscripcion_id');
            $inscripcionesValidas = Inscripcion::where('grupo_id', $evaluacion->grupo_id)
                ->whereIn('id', $inscripcionIds)
                ->pluck('id');

            foreach ($calificaciones as $indice => $fila) {
                if (! $inscripcionesValidas->contains($fila['inscripcion_id'])) {
                    throw ValidationException::withMessages([
                        "calificaciones.$indice.inscripcion_id" => 'La inscripción no pertenece al grupo de esta evaluación.',
                    ]);
                }
            }

            $ahora = now();
            $filas = [];

            foreach ($calificaciones as $fila) {
                // Se guardan calificaciones con nota válida numérica
                if (isset($fila['nota']) && $fila['nota'] !== '' && $fila['nota'] !== null) {
                    $filas[] = [
                        'evaluacion_id' => $evaluacion->id,
                        'inscripcion_id' => $fila['inscripcion_id'],
                        'nota' => round((float) $fila['nota'], 2),
                        'observaciones' => ! empty($fila['observaciones']) ? substr($fila['observaciones'], 0, 255) : null,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ];
                }
            }

            if (! empty($filas)) {
                Calificacion::upsert(
                    $filas,
                    ['evaluacion_id', 'inscripcion_id'],
                    ['nota', 'observaciones', 'updated_at']
                );
            }
        });
    }

    /**
     * Obtiene la libreta de calificaciones personales por curso para el alumno autenticado.
     */
    public function obtenerCalificacionesAlumno(Usuario $usuario): array
    {
        $inscripciones = Inscripcion::with([
                'grupo.curso',
                'grupo.docente.usuario:id,nombres,apellidos',
            ])
            ->where('alumno_id', $usuario->id)
            ->get();

        $inscripcionIds = $inscripciones->pluck('id');

        $calificaciones = Calificacion::with('evaluacion.grupo.curso')
            ->whereIn('inscripcion_id', $inscripcionIds)
            ->join('evaluacion', 'calificacion.evaluacion_id', '=', 'evaluacion.id')
            ->select('calificacion.*')
            ->orderByDesc('evaluacion.fecha')
            ->get();

        $resumenCursos = $inscripciones->map(function ($inscripcion) use ($calificaciones) {
            $grupo = $inscripcion->grupo;
            $evaluaciones = Evaluacion::where('grupo_id', $inscripcion->grupo_id)
                ->orderBy('fecha')
                ->get();

            $misNotas = $calificaciones->where('inscripcion_id', $inscripcion->id)->keyBy('evaluacion_id');

            $evaluacionesDetalle = $evaluaciones->map(function ($ev) use ($misNotas) {
                $registro = $misNotas->get($ev->id);

                return [
                    'id' => $ev->id,
                    'nombre' => $ev->nombre,
                    'tipo' => $ev->tipo,
                    'fecha' => $ev->fecha?->toDateString(),
                    'ponderacion' => (float) $ev->ponderacion,
                    'nota' => $registro?->nota !== null ? (float) $registro->nota : null,
                    'observaciones' => $registro?->observaciones,
                ];
            });

            $notasRegistradas = $evaluacionesDetalle->whereNotNull('nota');
            $promedio = $notasRegistradas->count() > 0 
                ? round($notasRegistradas->avg('nota'), 1) 
                : null;

            $docentePersona = $grupo?->docente?->usuario;

            return [
                'inscripcion_id' => $inscripcion->id,
                'curso' => $grupo?->curso?->nombre ?? 'Curso',
                'codigo_grupo' => $grupo?->codigo_grupo ?? 'N/A',
                'docente' => $docentePersona ? trim($docentePersona->nombres . ' ' . $docentePersona->apellidos) : 'Sin asignar',
                'estado_inscripcion' => $inscripcion->estado,
                'total_evaluaciones' => $evaluaciones->count(),
                'evaluaciones_rendidas' => $notasRegistradas->count(),
                'promedio' => $promedio,
                'evaluaciones' => $evaluacionesDetalle->values(),
            ];
        })->values();

        // Historial cronológico de notas obtenidas
        $historial = $calificaciones->map(function ($cal) {
            $ev = $cal->evaluacion;
            $grupo = $ev?->grupo;

            return [
                'id' => $cal->id,
                'evaluacion' => $ev?->nombre ?? 'Evaluación',
                'tipo' => $ev?->tipo ?? 'PRACTICA',
                'fecha' => $ev?->fecha?->toDateString(),
                'curso' => $grupo?->curso?->nombre ?? 'Curso',
                'codigo_grupo' => $grupo?->codigo_grupo ?? 'N/A',
                'nota' => (float) $cal->nota,
                'ponderacion' => $ev?->ponderacion ? (float) $ev->ponderacion : 100,
                'observaciones' => $cal->observaciones,
            ];
        })->values();

        return [
            'es_alumno' => true,
            'resumen_cursos' => $resumenCursos,
            'historial' => $historial,
        ];
    }
}
