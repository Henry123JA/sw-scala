<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Inscripcion;
use App\Models\ObservacionAcademica;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;

class ObservacionAcademicaService
{
    /**
     * Listar observaciones académicas de una inscripción según los permisos del usuario.
     */
    public function listarPorInscripcion(int $inscripcionId, Usuario $usuario): Collection
    {
        $inscripcion = Inscripcion::findOrFail($inscripcionId);
        $rol = $usuario->rol?->nombre;

        // Si es Alumno, solo devuelve las observaciones donde visible_para_alumno === true y la inscripción pertenece a él
        if ($rol === 'Alumno') {
            if ($inscripcion->alumno_id !== $usuario->id) {
                throw new BusinessException('No está autorizado para ver las observaciones de esta inscripción.');
            }

            return ObservacionAcademica::with(['docente.usuario'])
                ->where('inscripcion_id', $inscripcionId)
                ->where('visible_para_alumno', true)
                ->orderBy('fecha', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        // Si es Docente o Admin (Propietario, Secretaria), devuelve todas las observaciones de la inscripción
        return ObservacionAcademica::with(['docente.usuario'])
            ->where('inscripcion_id', $inscripcionId)
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Crear una nueva observación académica.
     */
    public function crear(array $datos, Usuario $usuario): ObservacionAcademica
    {
        $inscripcion = Inscripcion::with('grupo')->findOrFail($datos['inscripcion_id']);
        $rol = $usuario->rol?->nombre;

        $esAdmin = in_array($rol, ['Propietario', 'Secretaria']);
        $esDocente = ($rol === 'Docente');

        if (! $esAdmin && ! $esDocente) {
            throw new BusinessException('No tiene permisos para registrar observaciones académicas.');
        }

        // Valida que el Docente esté asignado al grupo de la inscripción (o que sea Propietario/Secretaria)
        if ($esDocente) {
            if ($inscripcion->grupo?->docente_id !== $usuario->id) {
                throw new BusinessException('El docente no está asignado al grupo de esta inscripción.');
            }
            $docenteId = $usuario->id;
        } else {
            // Propietario o Secretaria
            $docenteId = $datos['docente_id'] ?? $inscripcion->grupo?->docente_id ?? ($usuario->docente ? $usuario->id : null);
        }

        if (! in_array($datos['tipo'], ObservacionAcademica::TIPOS, true)) {
            throw new BusinessException('El tipo de observación no es válido.');
        }

        return ObservacionAcademica::create([
            'inscripcion_id' => $inscripcion->id,
            'docente_id' => $docenteId,
            'fecha' => $datos['fecha'] ?? now()->toDateString(),
            'tipo' => $datos['tipo'],
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'visible_para_alumno' => isset($datos['visible_para_alumno']) ? (bool) $datos['visible_para_alumno'] : true,
        ]);
    }

    /**
     * Eliminar una observación académica.
     * Solo el docente creador o Propietario puede eliminarla.
     */
    public function eliminar(int $id, Usuario $usuario): bool
    {
        $observacion = ObservacionAcademica::findOrFail($id);
        $rol = $usuario->rol?->nombre;

        $esPropietario = ($rol === 'Propietario');
        $esDocenteCreador = ($rol === 'Docente' && $observacion->docente_id === $usuario->id);

        if (! $esPropietario && ! $esDocenteCreador) {
            throw new BusinessException('Solo el docente creador o el Propietario pueden eliminar esta observación.');
        }

        return (bool) $observacion->delete();
    }
}
