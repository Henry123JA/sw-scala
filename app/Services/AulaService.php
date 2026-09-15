<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Sala;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AulaService
{
    /**
     * List rooms with search and type filters.
     */
    public function listar(string $buscar = '', string $tipo = ''): LengthAwarePaginator
    {
        return Sala::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where('nombre', 'like', "%{$buscar}%");
            })
            ->when($tipo, function ($query) use ($tipo) {
                $query->where('tipo', $tipo);
            })
            ->orderBy('nombre')
            ->paginate(15);
    }

    /**
     * Get distinct room types.
     */
    public function obtenerTipos(): array
    {
        return Sala::query()
            ->distinct()
            ->whereNotNull('tipo')
            ->where('tipo', '!=', '')
            ->pluck('tipo')
            ->toArray();
    }

    /**
     * Get a room by ID.
     */
    public function obtenerPorId(int $id): Sala
    {
        return Sala::findOrFail($id);
    }

    /**
     * Create a room.
     *
     * @throws BusinessException
     */
    public function crear(array $data): Sala
    {
        if (Sala::where('nombre', $data['nombre'])->exists()) {
            throw new BusinessException('El nombre de la sala ya está en uso.', 'nombre');
        }

        if (isset($data['capacidad']) && $data['capacidad'] <= 0) {
            throw new BusinessException('La capacidad debe ser un número entero mayor a 0.', 'capacidad');
        }

        return Sala::create([
            'nombre'         => $data['nombre'],
            'tipo'           => $data['tipo'] ?? null,
            'capacidad'      => $data['capacidad'],
            'ubicacion_piso' => $data['ubicacion_piso'] ?? null,
            'equipamiento'   => $data['equipamiento'] ?? null,
            'estado'         => $data['estado'] ?? 'ACTIVO',
            'eliminado'      => false,
        ]);
    }

    /**
     * Update a room.
     *
     * @throws BusinessException
     */
    public function actualizar(int $id, array $data): Sala
    {
        $sala = Sala::findOrFail($id);

        if (isset($data['nombre']) && Sala::where('nombre', $data['nombre'])->where('id', '!=', $id)->exists()) {
            throw new BusinessException('El nombre de la sala ya está en uso.', 'nombre');
        }

        if (isset($data['capacidad']) && $data['capacidad'] <= 0) {
            throw new BusinessException('La capacidad debe ser un número entero mayor a 0.', 'capacidad');
        }

        $sala->update(array_filter([
            'nombre'         => $data['nombre'] ?? null,
            'tipo'           => $data['tipo'] ?? null,
            'capacidad'      => $data['capacidad'] ?? null,
            'ubicacion_piso' => $data['ubicacion_piso'] ?? null,
            'equipamiento'   => $data['equipamiento'] ?? null,
            'estado'         => $data['estado'] ?? null,
        ], fn($v) => $v !== null));

        return $sala->fresh();
    }

    /**
     * Delete (soft-delete) a room.
     *
     * @throws BusinessException
     */
    public function eliminar(int $id): void
    {
        $sala = Sala::findOrFail($id);

        // Check if there are active scheduled groups in this room
        $activeGroupsCount = DB::table('horario_grupo')
            ->join('grupo', 'horario_grupo.grupo_id', '=', 'grupo.id')
            ->where('horario_grupo.sala_id', $id)
            ->where('grupo.estado', 'ACTIVO')
            ->where('grupo.eliminado', false)
            ->count();

        if ($activeGroupsCount > 0) {
            throw new BusinessException('No se puede eliminar la sala porque tiene grupos activos programados.', 'general');
        }

        $sala->softDelete();
    }
}
