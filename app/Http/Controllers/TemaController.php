<?php

namespace App\Http\Controllers;

use App\Services\TemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemaController extends Controller
{
    public function __construct(protected TemaService $temaService) {}

    /**
     * Actualiza el tema seleccionado para el usuario autenticado.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'tema_id' => ['required', 'integer', 'exists:tema,id'],
        ]);

        $this->temaService->actualizarTemaUsuario(
            usuarioId: $request->user()->id,
            temaId: (int) $request->input('tema_id')
        );

        return back()->with('success', 'Tema actualizado correctamente.');
    }
}
