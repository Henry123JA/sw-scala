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
    public function listarGrupos(Usuario $usuario): Collection
    {
        return Grupo::with('curso')
            ->when($usuario->rol->nombre === 'Docente', fn ($q) => $q->where('docente_id', $usuario->id))
            ->orderBy('codigo_grupo')->get();
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
}
