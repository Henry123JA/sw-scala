<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Services\InscripcionService;
use App\Services\MensualidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MensualidadController extends Controller
{
    protected MensualidadService $mensualidadService;
    protected InscripcionService $inscripcionService;

    public function __construct(MensualidadService $mensualidadService, InscripcionService $inscripcionService)
    {
        $this->mensualidadService = $mensualidadService;
        $this->inscripcionService = $inscripcionService;
    }

    /**
     * Display inscripciones (cada una con su fecha_vencimiento y pagos).
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['buscar', 'estado']);

        // QR payment success flash
        if ($request->query('qr_success')) {
            session()->flash('success', 'Pago con QR confirmado exitosamente.');
        }

        if (auth()->user()?->rol?->nombre === 'Alumno') {
            $filters['alumno_id'] = auth()->id();
        }

        $inscripciones = $this->inscripcionService->listar(
            $filters['buscar'] ?? '',
            $filters['estado'] ?? null,
            $filters['alumno_id'] ?? null
        );

        return Inertia::render('Mensualidad/Index', [
            'inscripciones' => $inscripciones,
            'filters'       => $request->all(['buscar', 'estado']),
        ]);
    }

    /**
     * Show payments for one inscription.
     */
    public function show(int $id): Response
    {
        $inscripcion = $this->inscripcionService->obtenerPorId($id);

        return Inertia::render('Mensualidad/Show', [
            'inscripcion' => $inscripcion,
        ]);
    }

    /**
     * Pay with Cash — Propietario and Secretaria only.
     */
    public function pagarEfectivo(Request $request, int $id): RedirectResponse
    {
        $rol = auth()->user()?->rol?->nombre;
        if (!in_array($rol, ['Propietario', 'Secretaria'])) {
            abort(403, 'No autorizado.');
        }

        $validated = $request->validate([
            'meses'          => ['required', 'integer', 'min:1', 'max:12'],
            'observaciones'  => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->mensualidadService->iniciarPagoEfectivo($id, $validated);
            return redirect()->route('mensualidades.index')
                ->with('success', "Pago en efectivo por {$validated['meses']} mes(es) registrado exitosamente.");
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    /**
     * Start QR payment — Propietario, Secretaria y Alumno.
     * Devuelve JSON con los datos del QR para mostrar en el modal sin recargar.
     */
    public function iniciarPagoQR(Request $request, int $id): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $rol = $user?->rol?->nombre;

        if (!in_array($rol, ['Propietario', 'Secretaria', 'Alumno'])) {
            abort(403, 'No autorizado.');
        }

        $validated = $request->validate([
            'meses' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        try {
            if ($rol === 'Alumno') {
                $inscripcion = $this->inscripcionService->obtenerPorId($id);
                if ((int) $inscripcion->alumno_id !== (int) $user->id) {
                    abort(403, 'No autorizado.');
                }
            }

            $mensualidad = $this->mensualidadService->iniciarPagoQR($id, $validated);

            // Si es una petición Inertia, devolver los datos del QR como flash
            if ($request->header('X-Inertia')) {
                return back()->with('qrMensualidad', [
                    'id' => $mensualidad->id,
                    'qrBase64' => $mensualidad->pagofacil_qr_base64,
                    'paymentNumber' => $mensualidad->pagofacil_payment_number,
                    'expira' => $mensualidad->pagofacil_qr_expira?->toISOString(),
                    'mesesPagados' => $mensualidad->meses_pagados,
                    'monto' => $mensualidad->monto_base,
                ]);
            }

            return response()->json([
                'id' => $mensualidad->id,
                'qrBase64' => $mensualidad->pagofacil_qr_base64,
                'paymentNumber' => $mensualidad->pagofacil_payment_number,
                'expira' => $mensualidad->pagofacil_qr_expira?->toISOString(),
                'mesesPagados' => $mensualidad->meses_pagados,
                'monto' => $mensualidad->monto_base,
            ]);
        } catch (BusinessException $e) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors([$e->getField() => $e->getMessage()]);
            }
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Verificar estado de un pago QR (polling desde el modal).
     */
    public function verificarEstadoQR(string $paymentNumber): \Illuminate\Http\JsonResponse
    {
        $mensualidad = \App\Models\Mensualidad::where('pagofacil_payment_number', $paymentNumber)->first();

        if (!$mensualidad) {
            return response()->json(['estado' => 'NO_ENCONTRADO'], 404);
        }

        return response()->json([
            'estado' => $mensualidad->estado,
            'recibo' => $mensualidad->numero_recibo,
        ]);
    }
}
