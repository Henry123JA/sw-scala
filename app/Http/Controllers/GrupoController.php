<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\Grupo\StoreGrupoRequest;
use App\Http\Requests\Grupo\UpdateGrupoRequest;
use App\Services\GrupoService;
use App\Services\CursoService;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GrupoController extends Controller
{
    public function __construct(
        protected GrupoService $grupoService,
        protected CursoService $cursoService,
        protected UsuarioService $usuarioService
    ) {}

    public function index(Request $request): Response
    {
        $buscar = $request->query('buscar', '');
        $cursoId = (int)$request->query('curso_id', 0);

        // Access control: Docentes can only view their assigned groups
        $docenteId = null;
        if (auth()->user()?->rol?->nombre === 'Docente') {
            $docenteId = auth()->id();
        }

        $grupos = $this->grupoService->listar($buscar, $cursoId, $docenteId);
        $cursos = $this->cursoService->obtenerCursosActivos();

        return Inertia::render('Grupo/Index', [
            'grupos'  => $grupos,
            'cursos'  => $cursos,
            'buscar'  => $buscar,
            'cursoId' => $cursoId,
        ]);
    }

    public function create(): Response
    {
        $cursos   = $this->cursoService->obtenerCursosActivos();
        $docentes = $this->usuarioService->obtenerDocentesActivos();

        return Inertia::render('Grupo/Create', [
            'cursos'   => $cursos,
            'docentes' => $docentes,
        ]);
    }

    public function store(StoreGrupoRequest $request): RedirectResponse
    {
        try {
            $this->grupoService->crear($request->validated());
            return redirect()->route('grupos.index')->with('success', 'Grupo creado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function edit(int $id): Response
    {
        $grupo    = $this->grupoService->obtenerPorId($id);
        $cursos   = $this->cursoService->obtenerCursosActivos();
        $docentes = $this->usuarioService->obtenerDocentesActivos();

        return Inertia::render('Grupo/Edit', [
            'grupo'    => $grupo,
            'cursos'   => $cursos,
            'docentes' => $docentes,
        ]);
    }

    public function update(UpdateGrupoRequest $request, int $id): RedirectResponse
    {
        try {
            $this->grupoService->actualizar($id, $request->validated());
            return redirect()->route('grupos.index')->with('success', 'Grupo actualizado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->grupoService->eliminar($id);
            return redirect()->route('grupos.index')->with('success', 'Grupo eliminado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }
}
