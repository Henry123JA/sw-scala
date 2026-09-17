<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Curso;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CursoService
{
    /**
     * List courses with search and teaching type filters.
     */
    public function listar(string $buscar = '', string $tipoEnsenanza = '', ?string $estado = null): LengthAwarePaginator
    {
        return Curso::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                      ->orWhere('descripcion', 'like', "%{$buscar}%");
                });
            })
            ->when($tipoEnsenanza, function ($query) use ($tipoEnsenanza) {
                $query->where('tipo_ensenanza', $tipoEnsenanza);
            })
            ->when($estado, function ($query) use ($estado) {
                $query->where('estado', $estado);
            })
            ->orderBy('nombre')
            ->paginate(15);
    }

    /**
     * Get distinct teaching types.
     */
    public function obtenerTiposEnsenanza(): array
    {
        return Curso::query()
            ->distinct()
            ->whereNotNull('tipo_ensenanza')
            ->where('tipo_ensenanza', '!=', '')
            ->pluck('tipo_ensenanza')
            ->toArray();
    }

    /**
     * Get a course by ID.
     */
    public function obtenerPorId(int $id): Curso
    {
        return Curso::findOrFail($id);
    }

    /**
     * Create a course.
     */
    public function crear(array $data): Curso
    {
        return Curso::create([
            'nombre'            => $data['nombre'],
            'descripcion'       => $data['descripcion'] ?? null,
            'tipo_ensenanza'    => $data['tipo_ensenanza'] ?? null,
            'duracion_estandar' => $data['duracion_estandar'] ?? null,
            'estado'            => $data['estado'] ?? 'ACTIVO',
            'eliminado'         => false,
        ]);
    }

    /**
     * Update a course.
     */
    public function actualizar(int $id, array $data): Curso
    {
        $curso = Curso::findOrFail($id);

        // Deactivation validation:
        if (isset($data['estado']) && $data['estado'] === 'INACTIVO' && $curso->estado !== 'INACTIVO') {
            $hasActiveGroups = $curso->grupos()->where('estado', 'ACTIVO')->exists();
            if ($hasActiveGroups) {
                throw new BusinessException('No se puede desactivar el curso porque tiene grupos activos.', 'estado');
            }
        }

        $curso->update(array_filter([
            'nombre'            => $data['nombre'] ?? null,
            'descripcion'       => $data['descripcion'] ?? null,
            'tipo_ensenanza'    => $data['tipo_ensenanza'] ?? null,
            'duracion_estandar' => $data['duracion_estandar'] ?? null,
            'estado'            => $data['estado'] ?? null,
        ], fn($v) => $v !== null));

        return $curso->fresh();
    }

    /**
     * Delete a course.
     */
    public function eliminar(int $id): void
    {
        $curso = Curso::findOrFail($id);

        $hasActiveGroups = $curso->grupos()->where('estado', 'ACTIVO')->exists();
        if ($hasActiveGroups) {
            throw new BusinessException('No se puede eliminar el curso porque tiene grupos activos.', 'general');
        }

        $curso->softDelete();
    }

    public function obtenerCursosActivos(): \Illuminate\Database\Eloquent\Collection
    {
        return Curso::where('estado', 'ACTIVO')->orderBy('nombre')->get();
    }
}
