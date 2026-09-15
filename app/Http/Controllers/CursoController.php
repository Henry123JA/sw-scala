<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\Curso\StoreCursoRequest;
use App\Http\Requests\Curso\UpdateCursoRequest;
use App\Services\CursoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CursoController extends Controller
{
    public function __construct(protected CursoService $cursoService) {}

    public function index(Request $request): Response
    {
        $buscar = $request->query('buscar', '');
        $tipoEnsenanza = $request->query('tipo_ensenanza', '');

        // Access control filter: Alumno can only see active courses
        $estado = null;
        if (auth()->user()?->rol?->nombre === 'Alumno') {
            $estado = 'ACTIVO';
        }

        $cursos = $this->cursoService->listar($buscar, $tipoEnsenanza, $estado);
        $tiposEnsenanza = $this->cursoService->obtenerTiposEnsenanza();

        return Inertia::render('Curso/Index', [
            'cursos'         => $cursos,
            'tiposEnsenanza' => $tiposEnsenanza,
            'buscar'         => $buscar,
            'tipoEnsenanza'  => $tipoEnsenanza,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Curso/Create');
    }

    public function store(StoreCursoRequest $request): RedirectResponse
    {
        try {
            $this->cursoService->crear($request->validated());
            return redirect()->route('cursos.index')->with('success', 'Curso creado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function edit(int $id): Response
    {
        $curso = $this->cursoService->obtenerPorId($id);

        return Inertia::render('Curso/Edit', [
            'curso' => $curso,
        ]);
    }

    public function update(UpdateCursoRequest $request, int $id): RedirectResponse
    {
        try {
            $this->cursoService->actualizar($id, $request->validated());
            return redirect()->route('cursos.index')->with('success', 'Curso actualizado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->cursoService->eliminar($id);
            return redirect()->route('cursos.index')->with('success', 'Curso eliminado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }
}
