<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Alumno;
use App\Models\Docente;
use App\Models\Rol;
use App\Models\Tema;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UsuarioService
{
    private const ROL_SECRETARIA  = 'Secretaria';
    private const ROL_PROPIETARIO = 'Propietario';
    private const ROL_ALUMNO      = 'Alumno';
    private const ROL_DOCENTE     = 'Docente';

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function getRolId(string $nombreRol): int
    {
        $rol = Rol::where('nombre', $nombreRol)->first();

        if (!$rol) {
            throw new BusinessException("Rol '{$nombreRol}' no encontrado.", 'rol_id');
        }

        return $rol->id;
    }

    private function getTemaDefaultId(): ?int
    {
        return Tema::where('nombre', 'adultos')->value('id');
    }

    private function buildUsuarioData(array $data, string $rolNombre): array
    {
        $base = [
            'nombres'   => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'ci'        => $data['ci'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'rol_id'    => $this->getRolId($rolNombre),
            'tema_id'   => $data['tema_id'] ?? $this->getTemaDefaultId(),
            'eliminado' => false,
        ];

        if (isset($data['codigo'])) {
            $base['codigo'] = $data['codigo'];
        }

        return $base;
    }

    // ─── Crear Alumno ─────────────────────────────────────────────────────────

    /**
     * Creates a usuario + alumno in a single transaction.
     *
     * @throws BusinessException
     */
    public function crearAlumno(array $data): Alumno
    {
        return DB::transaction(function () use ($data) {
            $usuario = Usuario::create($this->buildUsuarioData($data, self::ROL_ALUMNO));

            $alumno = Alumno::create([
                'id'                   => $usuario->id,
                'codigo'               => $data['codigo'] ?? null,
                'estado'               => $data['estado'] ?? 'ACTIVO',
                'fecha_nacimiento'     => $data['fecha_nacimiento'] ?? null,
                'sexo'                 => $data['sexo'] ?? null,
                'telefono'             => $data['telefono'] ?? null,
                'telefono_alternativo' => $data['telefono_alternativo'] ?? null,
                'nivel'                => $data['nivel'] ?? null,
                'referido_por'         => $data['referido_por'] ?? null,
                'observaciones'        => $data['observaciones'] ?? null,
            ]);

            return $alumno;
        });
    }

    // ─── Crear Docente ────────────────────────────────────────────────────────

    /**
     * Creates a usuario + docente in a single transaction and syncs especialidades.
     *
     * @throws BusinessException
     */
    public function crearDocente(array $data, array $especialidades = []): Docente
    {
        return DB::transaction(function () use ($data, $especialidades) {
            $usuario = Usuario::create($this->buildUsuarioData($data, self::ROL_DOCENTE));

            $docente = Docente::create([
                'id'                  => $usuario->id,
                'codigo'              => $data['codigo'] ?? null,
                'estado'              => $data['estado'] ?? 'ACTIVO',
                'fecha_nacimiento'    => $data['fecha_nacimiento'] ?? null,
                'fecha_incorporacion' => $data['fecha_incorporacion'] ?? null,
                'telefono'            => $data['telefono'] ?? null,
                'tarifa_horaria'      => $data['tarifa_horaria'] ?? null,
                'observaciones'       => $data['observaciones'] ?? null,
            ]);

            if (!empty($especialidades)) {
                $docente->especialidades()->sync($especialidades);
            }

            return $docente;
        });
    }

    // ─── Crear Secretaria ─────────────────────────────────────────────────────

    /**
     * Creates a Secretaria user.
     * Only a Propietario may create Secretarias — enforced here.
     *
     * @throws BusinessException
     */
    public function crearSecretaria(array $data): Usuario
    {
        $rolSolicitante = $data['_rol_solicitante'] ?? null;

        if ($rolSolicitante === self::ROL_SECRETARIA) {
            throw new BusinessException(
                'Una Secretaria no puede crear otras Secretarias.',
                'general'
            );
        }

        return Usuario::create($this->buildUsuarioData($data, self::ROL_SECRETARIA));
    }

    // ─── Actualizar ───────────────────────────────────────────────────────────

    /**
     * Updates a usuario record (and optionally alumno/docente child fields).
     */
    public function actualizar(int $id, array $data): Usuario
    {
        $usuario = Usuario::findOrFail($id);

        $updateData = array_filter([
            'nombres'   => $data['nombres'] ?? null,
            'apellidos' => $data['apellidos'] ?? null,
            'ci'        => $data['ci'] ?? null,
            'email'     => $data['email'] ?? null,
            'tema_id'   => $data['tema_id'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $usuario->update($updateData);

        // Update alumno child if applicable
        if ($usuario->alumno && isset($data['alumno'])) {
            $usuario->alumno->update($data['alumno']);
        }

        // Update docente child if applicable
        if ($usuario->docente && isset($data['docente'])) {
            $usuario->docente->update($data['docente']);

            if (isset($data['especialidades'])) {
                $usuario->docente->especialidades()->sync($data['especialidades']);
            }
        }

        return $usuario->fresh();
    }

    // ─── Foto de perfil ──────────────────────────────────────────────────────

    /**
     * Sube y persiste la foto de perfil del usuario.
     *
     * Convención de almacenamiento:
     *   storage/app/public/usuarios/fotos/{user_id}/{uuid}.jpg
     *
     * El archivo SIEMPRE termina en `.jpg` independientemente del tipo de origen:
     * el navegador (usePhotoUpload) ya lo redimensionó a 200x200 JPEG.
     * Si el usuario ya tenía una foto, la anterior se borra del disco.
     *
     * @throws BusinessException Si el usuario no existe o falla el almacenamiento.
     */
    public function actualizarFoto(int $usuarioId, UploadedFile $file): string
    {
        return DB::transaction(function () use ($usuarioId, $file) {
            $usuario = Usuario::lockForUpdate()->find($usuarioId);
            if (!$usuario) {
                throw new BusinessException('Usuario no encontrado.', 'general');
            }

            $oldPath = $usuario->foto;
            $filename = Str::uuid()->toString() . '.jpg';
            $relativePath = "usuarios/fotos/{$usuarioId}/{$filename}";

            try {
                Storage::disk('public')->put($relativePath, $file->get());
            } catch (\Throwable $e) {
                throw new BusinessException(
                    'Error al guardar la imagen en el almacenamiento.',
                    'foto'
                );
            }

            $usuario->foto = $relativePath;
            $usuario->save();

            if (!empty($oldPath) && $oldPath !== $relativePath) {
                Storage::disk('public')->delete($oldPath);
            }

            return $relativePath;
        });
    }

    /**
     * Elimina el archivo de foto del usuario (si existe) y deja la columna en null.
     * Idempotente: no falla si el usuario nunca tuvo foto.
     *
     * @throws BusinessException Si el usuario no existe.
     */
    public function eliminarFoto(int $usuarioId): void
    {
        DB::transaction(function () use ($usuarioId) {
            $usuario = Usuario::lockForUpdate()->find($usuarioId);
            if (!$usuario) {
                throw new BusinessException('Usuario no encontrado.', 'general');
            }

            $oldPath = $usuario->foto;
            $usuario->foto = null;
            $usuario->save();

            if (!empty($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        });
    }

    /**
     * Resuelve la URL pública de un path almacenado o devuelve null.
     * Helper usado por Shared prop y por accesores.
     */
    public function obtenerFotoUrl(?string $foto): ?string
    {
        if (empty($foto)) {
            return null;
        }
        return asset('storage/' . $foto);
    }

    // ─── Dar de baja ─────────────────────────────────────────────────────────

    /**
     * Soft-deletes a usuario by setting eliminado = true.
     * Secretaria can only dar de baja Alumnos, NOT Docentes.
     *
     * @throws BusinessException
     */
    public function darDeBaja(int $id, string $rolSolicitante): void
    {
        $usuario = Usuario::findOrFail($id);
        $rolUsuario = $usuario->rol->nombre ?? null;

        if ($rolSolicitante === self::ROL_SECRETARIA && $rolUsuario === self::ROL_DOCENTE) {
            throw new BusinessException(
                'Una Secretaria no puede dar de baja a un Docente.',
                'general'
            );
        }

        $usuario->softDelete();
    }

    // ─── Listados ─────────────────────────────────────────────────────────────

    public function listarTodos(string $buscar = ''): LengthAwarePaginator
    {
        return Usuario::with(['rol'])
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $term = "%{$buscar}%";
                    $q->where('nombres', 'like', $term)
                      ->orWhere('apellidos', 'like', $term)
                      ->orWhere('ci', 'like', $term)
                      ->orWhere('email', 'like', $term)
                      ->orWhereHas('rol', function ($qRol) use ($term) {
                          $qRol->where('nombre', 'like', $term);
                      });
                });
            })
            ->orderBy('apellidos')
            ->paginate(15);
    }

    public function listarAlumnos(string $buscar = ''): LengthAwarePaginator
    {
        $rolAlumno = Rol::where('nombre', self::ROL_ALUMNO)->value('id');

        return Usuario::with(['alumno'])
            ->where('rol_id', $rolAlumno)
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $term = "%{$buscar}%";
                    $q->where('nombres', 'like', $term)
                      ->orWhere('apellidos', 'like', $term)
                      ->orWhere('ci', 'like', $term)
                      ->orWhere('codigo', 'like', $term);
                });
            })
            ->orderBy('apellidos')
            ->paginate(15);
    }

    public function listarDocentes(string $buscar = ''): LengthAwarePaginator
    {
        $rolDocente = Rol::where('nombre', self::ROL_DOCENTE)->value('id');

        return Usuario::with(['docente.especialidades'])
            ->where('rol_id', $rolDocente)
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $term = "%{$buscar}%";
                    $q->where('nombres', 'like', $term)
                      ->orWhere('apellidos', 'like', $term)
                      ->orWhere('ci', 'like', $term)
                      ->orWhere('codigo', 'like', $term);
                });
            })
            ->orderBy('apellidos')
            ->paginate(15);
    }

    public function listarSecretarias(string $buscar = ''): LengthAwarePaginator
    {
        $rolSecretaria = Rol::where('nombre', self::ROL_SECRETARIA)->value('id');

        return Usuario::where('rol_id', $rolSecretaria)
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $term = "%{$buscar}%";
                    $q->where('nombres', 'like', $term)
                      ->orWhere('apellidos', 'like', $term)
                      ->orWhere('ci', 'like', $term);
                });
            })
            ->orderBy('apellidos')
            ->paginate(15);
    }

    public function obtenerDocentesActivos(): \Illuminate\Database\Eloquent\Collection
    {
        return Docente::with('usuario')
            ->where('estado', 'ACTIVO')
            ->whereHas('usuario', function ($q) {
                $q->where('eliminado', false);
            })
            ->get();
    }

    public function obtenerAlumnosActivos(): \Illuminate\Database\Eloquent\Collection
    {
        return Alumno::with('usuario')
            ->where('estado', 'ACTIVO')
            ->whereHas('usuario', function ($q) {
                $q->where('eliminado', false);
            })
            ->get();
    }
}
