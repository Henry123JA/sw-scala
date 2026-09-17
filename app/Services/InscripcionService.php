<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Inscripcion;
use App\Models\Grupo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InscripcionService
{
    /**
     * List enrollments with search and filters.
     */
    public function listar(string $buscar = '', ?string $estado = null, ?int $alumnoId = null, ?int $docenteId = null): LengthAwarePaginator
    {
        return Inscripcion::query()
            ->with(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario'])
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($sub) use ($buscar) {
                    $sub->whereHas('alumno.usuario', function ($q) use ($buscar) {
                        $q->where('nombres', 'like', "%{$buscar}%")
                          ->orWhere('apellidos', 'like', "%{$buscar}%");
                    })->orWhereHas('grupo.curso', function ($q) use ($buscar) {
                        $q->where('nombre', 'like', "%{$buscar}%");
                    });
                });
            })
            ->when($estado, function ($query) use ($estado) {
                $query->where('estado', $estado);
            })
            ->when($alumnoId, function ($query) use ($alumnoId) {
                $query->where('alumno_id', $alumnoId);
            })
            ->when($docenteId, function ($query) use ($docenteId) {
                $query->whereHas('grupo', function ($q) use ($docenteId) {
                    $q->where('docente_id', $docenteId);
                });
            })
            ->orderBy('fecha', 'desc')
            ->paginate(15);
    }

    /**
     * Get enrollment by ID.
     */
    public function obtenerPorId(int $id): Inscripcion
    {
        return Inscripcion::with(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario'])->findOrFail($id);
    }

    /**
     * Create enrollment.
     * Solo considera ocupantes a inscripciones ACTIVA y PAUSADA.
     */
    public function crear(array $data): Inscripcion
    {
        return DB::transaction(function () use ($data) {
            $grupo = Grupo::findOrFail($data['grupo_id']);

            // 1. Capacidad del grupo: ocupantes únicamente ACTIVA y PAUSADA
            $activeCount = Inscripcion::where('grupo_id', $grupo->id)
                ->whereIn('estado', ['ACTIVA', 'PAUSADA'])
                ->count();

            if ($activeCount >= $grupo->capacidad_maxima) {
                throw new BusinessException('El grupo ha alcanzado su capacidad máxima.', 'grupo_id');
            }

            // 2. Prevención de duplicados: únicamente si ya tiene inscripción ACTIVA o PAUSADA
            $exists = Inscripcion::where('grupo_id', $grupo->id)
                ->where('alumno_id', $data['alumno_id'])
                ->whereIn('estado', ['ACTIVA', 'PAUSADA'])
                ->exists();

            if ($exists) {
                throw new BusinessException('El alumno ya tiene una inscripción activa o pausada en este grupo.', 'alumno_id');
            }

            $fechaInicio = $data['fecha_inicio_clases'];

            return Inscripcion::create([
                'alumno_id'           => $data['alumno_id'],
                'grupo_id'            => $data['grupo_id'],
                'fecha'               => $data['fecha'] ?? now()->toDateString(),
                'fecha_inicio_clases' => $fechaInicio,
                'estado'              => 'ACTIVA',
                'observaciones'       => $data['observaciones'] ?? null,
            ]);
        });
    }

    /**
     * Update enrollment info.
     */
    public function actualizar(int $id, array $data): Inscripcion
    {
        $inscripcion = Inscripcion::findOrFail($id);

        $inscripcion->update(array_filter([
            'fecha_inicio_clases' => $data['fecha_inicio_clases'] ?? null,
            'observaciones'       => $data['observaciones'] ?? null,
        ], fn($v) => $v !== null));

        return $inscripcion->fresh();
    }

    /**
     * Pause enrollment (suspensión académica temporal).
     */
    public function pausar(int $id, array $data): Inscripcion
    {
        return DB::transaction(function () use ($id, $data) {
            $inscripcion = Inscripcion::findOrFail($id);

            if ($inscripcion->estado !== 'ACTIVA') {
                throw new BusinessException('Solo se pueden pausar inscripciones activas.', 'estado');
            }

            $fechaPausa = $data['fecha_pausa'] ?? now()->toDateString();

            $inscripcion->update([
                'estado'      => 'PAUSADA',
                'fecha_pausa' => $fechaPausa,
            ]);

            return $inscripcion->fresh();
        });
    }

    /**
     * Resume enrollment (reactivación académica).
     */
    public function reanudar(int $id, array $data): Inscripcion
    {
        return DB::transaction(function () use ($id, $data) {
            $inscripcion = Inscripcion::findOrFail($id);

            if ($inscripcion->estado !== 'PAUSADA') {
                throw new BusinessException('Solo se pueden reanudar inscripciones pausadas.', 'estado');
            }

            $fechaRetorno = $data['fecha_retorno'] ?? now()->toDateString();

            $inscripcion->update([
                'estado'        => 'ACTIVA',
                'fecha_retorno' => $fechaRetorno,
            ]);

            return $inscripcion->fresh();
        });
    }

    /**
     * Cancel / withdraw enrollment.
     * Registra fecha_retiro, cambia a CANCELADA y preserva el registro en el historial.
     */
    public function cancelar(int $id, array $data = []): Inscripcion
    {
        return DB::transaction(function () use ($id, $data) {
            $inscripcion = Inscripcion::findOrFail($id);

            if ($inscripcion->estado === 'CANCELADA') {
                throw new BusinessException('La inscripción ya se encuentra cancelada.', 'estado');
            }

            $inscripcion->update([
                'estado'       => 'CANCELADA',
                'fecha_retiro' => $data['fecha_retiro'] ?? now()->toDateString(),
            ]);

            return $inscripcion->fresh();
        });
    }

    /**
     * Delete enrollment adapter — conserva el registro como historial realizando retiro lógico.
     */
    public function eliminar(int $id): void
    {
        $this->cancelar($id, ['fecha_retiro' => now()->toDateString()]);
    }
}
