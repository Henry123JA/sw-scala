<?php

namespace App\Services;

use App\Models\Inscripcion;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SeguimientoService
{
    /**
     * Actualiza inscripciones ACTIVA con fecha_vencimiento < hoy a VENCIDA.
     * Luego lista las VENCIDAS.
     */
    public function alumnosAtrasados(?int $docenteId = null, ?string $buscar = ''): LengthAwarePaginator
    {
        $today = Carbon::today()->toDateString();

        // 1. Actualizar ACTIVAS vencidas a VENCIDA
        Inscripcion::where('estado', 'ACTIVA')
            ->where('fecha_vencimiento', '<', $today)
            ->update(['estado' => 'VENCIDA']);

        // 2. Listar todas las VENCIDAS
        $query = Inscripcion::query()
            ->with(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario'])
            ->where('estado', 'VENCIDA');

        if ($docenteId !== null) {
            $query->whereHas('grupo', function ($q) use ($docenteId) {
                $q->where('docente_id', $docenteId);
            });
        }

        if ($buscar) {
            $query->whereHas('alumno.usuario', function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%");
            });
        }

        return $query->orderBy('fecha_vencimiento', 'asc')->paginate(15);
    }

    /**
     * Alumnos próximos a vencer.
     */
    public function proximosVencer(int $dias = 7, ?int $docenteId = null, ?string $buscar = ''): LengthAwarePaginator
    {
        $today = Carbon::today()->toDateString();
        $proximosDias = max(1, $dias);

        $query = Inscripcion::query()
            ->with(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario'])
            ->where('estado', 'ACTIVA')
            ->where('fecha_vencimiento', '>=', $today)
            ->where('fecha_vencimiento', '<=', Carbon::today()->addDays($proximosDias)->toDateString());

        if ($docenteId !== null) {
            $query->whereHas('grupo', function ($q) use ($docenteId) {
                $q->where('docente_id', $docenteId);
            });
        }

        if ($buscar) {
            $query->whereHas('alumno.usuario', function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%");
            });
        }

        return $query->orderBy('fecha_vencimiento', 'asc')->paginate(15);
    }

    /**
     * Verificar si un docente tiene acceso a un alumno.
     */
    public function docenteTieneAccesoAAlumno(int $docenteId, int $alumnoId): bool
    {
        return Inscripcion::where('alumno_id', $alumnoId)
            ->whereHas('grupo', fn($q) => $q->where('docente_id', $docenteId))
            ->exists();
    }

    /**
     * Obtener datos del alumno para la vista show.
     */
    public function obtenerAlumno(int $alumnoId): \App\Models\Alumno
    {
        return \App\Models\Alumno::with('usuario')->findOrFail($alumnoId);
    }

    /**
     * Estado completo de un alumno: sus inscripciones con pagos.
     */
    public function estadoAlumno(int $alumnoId)
    {
        return Inscripcion::with(['grupo.curso', 'mensualidades'])
            ->where('alumno_id', $alumnoId)
            ->orderBy('fecha', 'desc')
            ->get();
    }
}
