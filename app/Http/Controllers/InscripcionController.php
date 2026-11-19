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

        $observaciones = app(\App\Services\ObservacionAcademicaService::class)->listarPorInscripcion($id, auth()->user());

        return Inertia::render('Inscripcion/Show', [
            'inscripcion' => $inscripcion,
            'observaciones' => $observaciones,
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

    /**
     * Registrar pago de inscripción en efectivo (Propietario / Secretaria).
     */
    public function pagarEfectivo(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'monto_inscripcion' => ['nullable', 'numeric', 'min:1'],
        ]);

        try {
            $ins = $this->inscripcionService->iniciarPagoEfectivo($id, $validated);
            return back()->with('success', "Pago en efectivo registrado correctamente. Recibo: {$ins->numero_recibo}");
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    /**
     * Generar o recuperar QR de PagoFácil para una inscripción.
     */
    public function iniciarPagoQR(Request $request, int $id): RedirectResponse
    {
        $inscripcion = $this->inscripcionService->obtenerPorId($id);

        // Si es Alumno, solo puede generar QR de su propia inscripción
        if (auth()->user()?->rol?->nombre === 'Alumno' && $inscripcion->alumno_id !== auth()->id()) {
            abort(403, 'No autorizado para pagar esta inscripción.');
        }

        $validated = $request->validate([
            'monto_inscripcion' => ['nullable', 'numeric', 'min:1'],
            'regenerar'         => ['nullable', 'boolean'],
        ]);

        try {
            $this->inscripcionService->iniciarPagoQR($id, $validated);
            return back()->with('success', 'Código QR de PagoFácil generado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        } catch (\Throwable $e) {
            return back()->withErrors(['general' => 'Error al conectar con PagoFácil: ' . $e->getMessage()]);
        }
    }

    /**
     * Endpoint JSON de polling para verificar si el QR ya fue pagado.
     */
    public function verificarEstadoQR(string $paymentNumber): \Illuminate\Http\JsonResponse
    {
        $resultado = $this->inscripcionService->verificarEstadoQR($paymentNumber);

        return response()->json($resultado);
    }
}
