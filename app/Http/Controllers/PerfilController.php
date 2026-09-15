<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\Perfil\UpdatePerfilFotoRequest;
use App\Http\Requests\Perfil\UpdatePerfilRequest;
use App\Services\TemaService;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function __construct(
        protected UsuarioService $usuarios,
        protected TemaService $temas
    ) {}

    /**
     * Muestra la página de perfil del usuario autenticado.
     */
    public function index(Request $request): Response
    {
        $user  = $request->user()->load('tema');
        $temas = $this->temas->listarTemas();

        return Inertia::render('Perfil/Index', [
            'user'  => $user,
            'temas' => $temas,
        ]);
    }

    /**
     * Actualiza los datos personales del usuario autenticado.
     */
    public function update(UpdatePerfilRequest $request): RedirectResponse
    {
        try {
            $this->usuarios->actualizar($request->user()->id, $request->validated());
            return back()->with('success', 'Perfil actualizado correctamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    /**
     * Sube/reemplaza la foto de perfil del usuario autenticado.
     * El navegador ya envió la imagen redimensionada a 200x200 JPEG.
     */
    public function updateFoto(UpdatePerfilFotoRequest $request): RedirectResponse
    {
        try {
            $this->usuarios->actualizarFoto(
                $request->user()->id,
                $request->file('foto')
            );
            return back()->with('success', 'Foto actualizada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }
}
