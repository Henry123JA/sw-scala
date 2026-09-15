<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Tema;
use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Collection;

class TemaService
{
    /**
     * Actualiza el tema seleccionado para un usuario.
     */
    public function actualizarTemaUsuario(int $usuarioId, int $temaId): void
    {
        $usuario = Usuario::find($usuarioId);
        if (!$usuario) {
            throw new BusinessException('Usuario no encontrado.', 'usuario_id');
        }

        $tema = Tema::find($temaId);
        if (!$tema) {
            throw new BusinessException('Tema no encontrado.', 'tema_id');
        }

        $usuario->update(['tema_id' => $temaId]);
    }

    /**
     * Get all themes from database.
     */
    public function listarTemas(): Collection
    {
        return Tema::all();
    }

    /**
     * Update user theme and return updated model.
     */
    public function cambiarTema(int $usuarioId, int $temaId): Usuario
    {
        $usuario = Usuario::find($usuarioId);
        if (!$usuario) {
            throw new BusinessException('Usuario no encontrado.', 'usuario_id');
        }

        $tema = Tema::find($temaId);
        if (!$tema) {
            throw new BusinessException('Tema no encontrado.', 'tema_id');
        }

        $usuario->update(['tema_id' => $temaId]);
        return $usuario->load('tema');
    }
}
