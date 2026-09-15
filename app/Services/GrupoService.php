<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Grupo;
use App\Models\Curso;
use App\Models\Docente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GrupoService
{
    /**
     * List groups with search and course filters.
     */
    public function listar(string $buscar = '', int $cursoId = 0, ?int $docenteId = null): LengthAwarePaginator
    {
        return Grupo::query()
            ->with(['curso', 'docente.usuario'])
            ->when($buscar, function ($query) use ($buscar) {
                $query->where('codigo_grupo', 'like', "%{$buscar}%");
            })
            ->when($cursoId, function ($query) use ($cursoId) {
                $query->where('curso_id', $cursoId);
            })
            ->when($docenteId, function ($query) use ($docenteId) {
                $query->where('docente_id', $docenteId);
            })
            ->orderBy('codigo_grupo')
            ->paginate(15);
    }

    /**
     * Get a group by ID.
     */
    public function obtenerPorId(int $id): Grupo
    {
        return Grupo::findOrFail($id);
    }

    /**
     * Create a group.
     */
    public function crear(array $data): Grupo
    {
        // 1. Ensure capacity > 0
        if (isset($data['capacidad_maxima']) && $data['capacidad_maxima'] <= 0) {
            throw new BusinessException('La capacidad máxima debe ser mayor a 0.', 'capacidad_maxima');
        }

        // 2. Ensure codigo_grupo is unique
        if (Grupo::where('codigo_grupo', $data['codigo_grupo'])->exists()) {
            throw new BusinessException('El código de grupo ya está en uso.', 'codigo_grupo');
        }

        // 3. Ensure Curso exists and is ACTIVO
        $curso = Curso::find($data['curso_id']);
        if (!$curso) {
            throw new BusinessException('El curso seleccionado no existe.', 'curso_id');
        }
        if ($curso->estado !== 'ACTIVO') {
            throw new BusinessException('El curso seleccionado no está activo.', 'curso_id');
        }

        // 4. Ensure Docente exists and is ACTIVO (based on Docente and Usuario state)
        $docente = Docente::find($data['docente_id']);
        if (!$docente) {
            throw new BusinessException('El docente seleccionado no existe.', 'docente_id');
        }
        if ($docente->estado !== 'ACTIVO') {
            throw new BusinessException('El docente seleccionado no está activo.', 'docente_id');
        }
        if ($docente->usuario->eliminado) {
            throw new BusinessException('El usuario del docente está eliminado.', 'docente_id');
        }

        return Grupo::create([
            'codigo_grupo'     => $data['codigo_grupo'],
            'curso_id'         => $data['curso_id'],
            'docente_id'       => $data['docente_id'],
            'capacidad_maxima' => $data['capacidad_maxima'],
            'nivel'            => $data['nivel'] ?? null,
            'estado'           => $data['estado'] ?? 'ACTIVO',
            'eliminado'        => false,
        ]);
    }

    /**
     * Update a group.
     */
    public function actualizar(int $id, array $data): Grupo
    {
        $grupo = Grupo::findOrFail($id);

        if (isset($data['capacidad_maxima']) && $data['capacidad_maxima'] <= 0) {
            throw new BusinessException('La capacidad máxima debe ser mayor a 0.', 'capacidad_maxima');
        }

        if (isset($data['codigo_grupo']) && Grupo::where('codigo_grupo', $data['codigo_grupo'])->where('id', '!=', $id)->exists()) {
            throw new BusinessException('El código de grupo ya está en uso.', 'codigo_grupo');
        }

        if (isset($data['curso_id'])) {
            $curso = Curso::find($data['curso_id']);
            if (!$curso) {
                throw new BusinessException('El curso seleccionado no existe.', 'curso_id');
            }
            if ($curso->estado !== 'ACTIVO') {
                throw new BusinessException('El curso seleccionado no está activo.', 'curso_id');
            }
        }

        if (isset($data['docente_id'])) {
            $docente = Docente::find($data['docente_id']);
            if (!$docente) {
                throw new BusinessException('El docente seleccionado no existe.', 'docente_id');
            }
            if ($docente->estado !== 'ACTIVO') {
                throw new BusinessException('El docente seleccionado no está activo.', 'docente_id');
            }
            if ($docente->usuario->eliminado) {
                throw new BusinessException('El usuario del docente está eliminado.', 'docente_id');
            }
        }

        $grupo->update(array_filter([
            'codigo_grupo'     => $data['codigo_grupo'] ?? null,
            'curso_id'         => $data['curso_id'] ?? null,
            'docente_id'       => $data['docente_id'] ?? null,
            'capacidad_maxima' => $data['capacidad_maxima'] ?? null,
            'nivel'            => $data['nivel'] ?? null,
            'estado'           => $data['estado'] ?? null,
        ], fn($v) => $v !== null));

        return $grupo->fresh();
    }

    /**
     * Delete a group.
     */
    public function eliminar(int $id): void
    {
        $grupo = Grupo::findOrFail($id);

        $hasActiveEnrollments = $grupo->inscripciones()->where('eliminado', false)->exists();
        if ($hasActiveEnrollments) {
            throw new BusinessException('No se puede eliminar el grupo porque tiene alumnos inscritos.', 'general');
        }

        $grupo->softDelete();
    }

    public function obtenerGruposActivos(): \Illuminate\Database\Eloquent\Collection
    {
        return Grupo::with(['curso', 'docente.usuario'])
            ->where('estado', 'ACTIVO')
            ->get();
    }
}
