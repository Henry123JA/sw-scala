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
        $user = $request->user();

        // Si el usuario autenticado tiene el rol Alumno, mostramos exclusivamente su vista personal
        if ($user->rol?->nombre === 'Alumno') {
            return Inertia::render('Asistencia/Index', $this->service->obtenerAsistenciasAlumno($user));
        }

        // Si es Docente, Secretaria o Propietario, mostramos las tarjetas de grupos para gestión y toma de asistencia
        return Inertia::render('Asistencia/Index', [
            'es_alumno' => false,
            'grupos' => $this->service->listarGrupos($user),
            'rol_usuario' => $user->rol?->nombre,
        ]);
    }

    public function seleccionar(SeleccionarSesionRequest $request): RedirectResponse
    {
        $sesion = $this->service->seleccionarSesion($request->validated(), $request->user());

        return to_route('asistencias.show', $sesion->id);
    }

    public function historialGrupo(Request $request, int $grupo): Response
    {
        return Inertia::render('Asistencia/HistorialGrupo', $this->service->obtenerHistorialGrupo($grupo, $request->user()));
    }

    public function show(Request $request, int $sesion): Response
    {
        return Inertia::render('Asistencia/Show', $this->service->listarAlumnos($sesion, $request->user()));
    }

    public function guardar(GuardarAsistenciaRequest $request, int $sesion): RedirectResponse
    {
        $this->service->guardar($sesion, $request->validated()['asistencias'], $request->user());

        return to_route('asistencias.index')->with('success', 'Asistencia registrada correctamente.');
    }
}
