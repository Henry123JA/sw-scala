<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Services\ObservacionAcademicaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ObservacionAcademicaController extends Controller
{
    public function __construct(
        protected ObservacionAcademicaService $service
    ) {}

    /**
     * Listado de observaciones de la inscripción en formato JSON o Inertia.
     */
    public function index(Request $request, int $inscripcion)
    {
        try {
            $observaciones = $this->service->listarPorInscripcion($inscripcion, $request->user());

            if ($request->wantsJson() || $request->ajax() || ! $request->header('X-Inertia')) {
                return response()->json($observaciones);
            }

            return back()->with('observaciones', $observaciones);
        } catch (BusinessException $e) {
            if ($request->wantsJson() || ! $request->header('X-Inertia')) {
                return response()->json(['message' => $e->getMessage()], 403);
            }

            abort(403, $e->getMessage());
        }
    }

    /**
     * Registra una nueva observación académica.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inscripcion_id'      => 'required|integer|exists:inscripcion,id',
            'fecha'               => 'nullable|date',
            'tipo'                => 'required|string|in:PROGRESO_TECNICO,RECOMENDACION,DISCIPLINA,FELICITACION',
            'titulo'              => 'required|string|max:150',
            'descripcion'         => 'required|string',
            'visible_para_alumno' => 'nullable|boolean',
        ]);

        try {
            $observacion = $this->service->crear($validated, $request->user());

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Observación académica registrada exitosamente.',
                    'data'    => $observacion,
                ], 201);
            }

            return back()->with('success', 'Observación académica registrada exitosamente.');
        } catch (BusinessException $e) {
            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    /**
     * Elimina una observación académica.
     */
    public function destroy(Request $request, int $observacion)
    {
        try {
            $this->service->eliminar($observacion, $request->user());

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Observación académica eliminada exitosamente.',
                ]);
            }

            return back()->with('success', 'Observación académica eliminada exitosamente.');
        } catch (BusinessException $e) {
            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['message' => $e->getMessage()], 403);
            }

            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }
}
