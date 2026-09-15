<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\Aula\StoreAulaRequest;
use App\Http\Requests\Aula\UpdateAulaRequest;
use App\Services\AulaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AulaController extends Controller
{
    public function __construct(protected AulaService $aulaService) {}

    public function index(Request $request): Response
    {
        $buscar = $request->query('buscar', '');
        $tipo = $request->query('tipo', '');

        $aulas = $this->aulaService->listar($buscar, $tipo);
        $tipos = $this->aulaService->obtenerTipos();

        return Inertia::render('Aula/Index', [
            'aulas' => $aulas,
            'tipos' => $tipos,
            'buscar' => $buscar,
            'tipo' => $tipo,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Aula/Create');
    }

    public function store(StoreAulaRequest $request): RedirectResponse
    {
        try {
            $this->aulaService->crear($request->validated());
            return redirect()->route('aulas.index')->with('success', 'Aula creada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function edit(int $id): Response
    {
        $aula = $this->aulaService->obtenerPorId($id);

        return Inertia::render('Aula/Edit', [
            'aula' => $aula,
        ]);
    }

    public function update(UpdateAulaRequest $request, int $id): RedirectResponse
    {
        try {
            $this->aulaService->actualizar($id, $request->validated());
            return redirect()->route('aulas.index')->with('success', 'Aula actualizada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->aulaService->eliminar($id);
            return redirect()->route('aulas.index')->with('success', 'Aula eliminada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }
}
