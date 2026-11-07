<?php

namespace App\Http\Controllers;

use App\Http\Requests\Asistencia\GuardarAsistenciaRequest;
use App\Http\Requests\Asistencia\SeleccionarSesionRequest;
use App\Services\AsistenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsistenciaController extends Controller
{
    public function __construct(private AsistenciaService $service) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Asistencia/Index', ['grupos' => $this->service->listarGrupos($request->user())]);
    }

    public function seleccionar(SeleccionarSesionRequest $request): RedirectResponse
    {
        $sesion = $this->service->seleccionarSesion($request->validated(), $request->user());

        return to_route('asistencias.show', $sesion->id);
    }

    public function show(Request $request, int $sesion): Response
    {
        return Inertia::render('Asistencia/Show', $this->service->listarAlumnos($sesion, $request->user()));
    }

    public function guardar(GuardarAsistenciaRequest $request, int $sesion): RedirectResponse
    {
        $this->service->guardar($sesion, $request->validated()['asistencias'], $request->user());

        return to_route('asistencias.show', $sesion)->with('success', 'Asistencia guardada correctamente.');
    }
}
