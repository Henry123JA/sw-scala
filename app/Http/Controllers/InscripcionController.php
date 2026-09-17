<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\Inscripcion\StoreInscripcionRequest;
use App\Http\Requests\Inscripcion\UpdateInscripcionRequest;
use App\Services\InscripcionService;
use App\Services\UsuarioService;
use App\Services\GrupoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InscripcionController extends Controller
{
    public function __construct(
        protected InscripcionService $inscripcionService,
        protected UsuarioService $usuarioService,
        protected GrupoService $grupoService
    ) {}

    public function index(Request $request): Response
    {
        $buscar = $request->query('buscar', '');
        $estado = $request->query('estado', null);

        $alumnoId = null;
        $docenteId = null;

        $rol = auth()->user()?->rol?->nombre;
        if ($rol === 'Alumno') {
            $alumnoId = auth()->id();
        } elseif ($rol === 'Docente') {
            $docenteId = auth()->id();
        }

        $inscripciones = $this->inscripcionService->listar($buscar, $estado, $alumnoId, $docenteId);

        return Inertia::render('Inscripcion/Index', [
            'inscripciones' => $inscripciones,
            'buscar'        => $buscar,
            'estado'        => $estado,
        ]);
    }

    public function create(): Response
    {
        $alumnos = $this->usuarioService->obtenerAlumnosActivos();
        $grupos  = $this->grupoService->obtenerGruposActivos();

        return Inertia::render('Inscripcion/Create', [
            'alumnos' => $alumnos,
            'grupos'  => $grupos,
        ]);
    }

    public function store(StoreInscripcionRequest $request): RedirectResponse
    {
        try {
            $this->inscripcionService->crear($request->validated());
            return redirect()->route('inscripciones.index')->with('success', 'Inscripción creada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function show(int $id): Response
    {
        $inscripcion = $this->inscripcionService->obtenerPorId($id);

        // Access check: Alumno can only view their own enrollment
        if (auth()->user()?->rol?->nombre === 'Alumno' && $inscripcion->alumno_id !== auth()->id()) {
            abort(403, 'No autorizado.');
        }

        return Inertia::render('Inscripcion/Show', [
            'inscripcion' => $inscripcion,
        ]);
    }

    public function edit(int $id): Response
    {
        $inscripcion = $this->inscripcionService->obtenerPorId($id);
        $alumnos     = $this->usuarioService->obtenerAlumnosActivos();
        $grupos      = $this->grupoService->obtenerGruposActivos();

        return Inertia::render('Inscripcion/Edit', [
            'inscripcion' => $inscripcion,
            'alumnos'     => $alumnos,
            'grupos'      => $grupos,
        ]);
    }

    public function update(UpdateInscripcionRequest $request, int $id): RedirectResponse
    {
        try {
            $this->inscripcionService->actualizar($id, $request->validated());
            return redirect()->route('inscripciones.index')->with('success', 'Inscripción actualizada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->inscripcionService->cancelar($id, ['fecha_retiro' => now()->toDateString()]);
            return redirect()->route('inscripciones.index')->with('success', 'Inscripción retirada/cancelada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'fecha_retiro' => ['nullable', 'date'],
        ]);

        try {
            $this->inscripcionService->cancelar($id, $request->only('fecha_retiro'));
            return back()->with('success', 'Inscripción retirada/cancelada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function pause(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'fecha_pausa' => ['required', 'date'],
        ]);

        try {
            $this->inscripcionService->pausar($id, $request->only('fecha_pausa'));
            return back()->with('success', 'Inscripción pausada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function resume(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'fecha_retorno' => ['required', 'date'],
        ]);

        try {
            $this->inscripcionService->reanudar($id, $request->only('fecha_retorno'));
            return back()->with('success', 'Inscripción reanudada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }
}
