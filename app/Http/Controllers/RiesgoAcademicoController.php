<?php

namespace App\Http\Controllers;

use App\Models\AlertaSeguimiento;
use App\Models\IntervencionSeguimiento;
use App\Models\VersionModelo;
use App\Services\RiesgoPredictivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RiesgoAcademicoController extends Controller
{
    public function __construct(
        protected RiesgoPredictivoService $service
    ) {}

    /**
     * Panel principal de Seguimiento Académico Predictivo (HU15, HU16, HU17, HU18, HU22).
     */
    public function index(Request $request): Response|JsonResponse
    {
        $datos = $this->service->listarAlertas($request->all(), $request->user());

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($datos);
        }

        return Inertia::render('Riesgo/Index', $datos);
    }

    /**
     * Ejecuta el análisis predictivo masivo para la academia o un grupo específico (HU15, HU19).
     */
    public function analizar(Request $request): RedirectResponse
    {
        $rol = $request->user()->rol?->nombre;
        abort_unless(
            in_array($rol, ['Propietario', 'Secretaria', 'Docente'], true),
            403,
            'No autorizado para ejecutar el análisis predictivo.'
        );

        $grupoId = $request->filled('grupo_id') && $request->input('grupo_id') !== 'TODOS'
            ? (int) $request->input('grupo_id')
            : null;

        $total = $this->service->analizarAcademia($grupoId, $request->user(), 'MOTOR_LOCAL');

        return back()->with('success', "Análisis predictivo completado exitosamente sobre {$total} inscripciones.");
    }

    /**
     * Vista detallada de una alerta predictiva con explicabilidad XAI (HU17) e intervenciones (HU20).
     */
    public function show(Request $request, int $alerta): Response|JsonResponse
    {
        $datos = $this->service->obtenerDetalleAlerta($alerta, $request->user());

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($datos);
        }

        return Inertia::render('Riesgo/Show', $datos);
    }

    /**
     * Actualiza el estado de gestión de una alerta predictiva (HU16).
     */
    public function actualizarEstado(Request $request, int $alerta): RedirectResponse
    {
        $rol = $request->user()->rol?->nombre;
        abort_unless(
            in_array($rol, ['Propietario', 'Secretaria', 'Docente'], true),
            403,
            'No autorizado para cambiar el estado de la alerta.'
        );

        $validated = $request->validate([
            'estado' => ['required', 'string', Rule::in(AlertaSeguimiento::ESTADOS)],
        ]);

        $alertaModel = AlertaSeguimiento::with('inscripcion.grupo')->findOrFail($alerta);

        if ($rol === 'Docente' && (int) $alertaModel->inscripcion?->grupo?->docente_id !== (int) $request->user()->id) {
            abort(403, 'No autorizado para modificar alertas de otros grupos.');
        }

        $alertaModel->update([
            'estado' => $validated['estado'],
        ]);

        return back()->with('success', "Estado de la alerta actualizado a {$validated['estado']}.");
    }

    /**
     * Registra una nueva intervención pedagógica sobre una alerta (HU20).
     */
    public function storeIntervencion(Request $request, int $alerta): RedirectResponse
    {
        $rol = $request->user()->rol?->nombre;
        abort_unless(
            in_array($rol, ['Propietario', 'Secretaria', 'Docente'], true),
            403,
            'No autorizado para registrar intervenciones pedagógicas.'
        );

        $alertaModel = AlertaSeguimiento::with('inscripcion.grupo')->findOrFail($alerta);

        if ($rol === 'Docente' && (int) $alertaModel->inscripcion?->grupo?->docente_id !== (int) $request->user()->id) {
            abort(403, 'No autorizado para intervenir en alertas de otros grupos.');
        }

        $validated = $request->validate([
            'fecha'       => ['nullable', 'date'],
            'tipo'        => ['required', 'string', Rule::in(IntervencionSeguimiento::TIPOS)],
            'titulo'      => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string'],
            'compromiso'  => ['nullable', 'string'],
            'resultado'   => ['nullable', 'string', Rule::in(IntervencionSeguimiento::RESULTADOS)],
        ]);

        $resultado = $validated['resultado'] ?? 'EN_PROCESO';

        IntervencionSeguimiento::create([
            'alerta_id'      => $alertaModel->id,
            'inscripcion_id' => $alertaModel->inscripcion_id,
            'usuario_id'     => $request->user()->id,
            'fecha'          => $validated['fecha'] ?? now()->toDateString(),
            'tipo'           => $validated['tipo'],
            'titulo'         => $validated['titulo'],
            'descripcion'    => $validated['descripcion'],
            'compromiso'     => $validated['compromiso'] ?? null,
            'resultado'      => $resultado,
        ]);

        if ($resultado === 'MEJORA_EVIDENTE') {
            $alertaModel->update(['estado' => 'RESUELTA']);
        } elseif ($alertaModel->estado === 'PENDIENTE') {
            $alertaModel->update(['estado' => 'EN_ATENCION']);
        }

        return back()->with('success', 'Intervención pedagógica registrada correctamente.');
    }

    /**
     * Actualiza el resultado o seguimiento de una intervención pedagógica existente (HU20).
     */
    public function actualizarIntervencion(Request $request, int $intervencion): RedirectResponse
    {
        $rol = $request->user()->rol?->nombre;
        abort_unless(
            in_array($rol, ['Propietario', 'Secretaria', 'Docente'], true),
            403,
            'No autorizado para actualizar intervenciones.'
        );

        $intervencionModel = IntervencionSeguimiento::with(['alerta.inscripcion.grupo'])->findOrFail($intervencion);

        if ($rol === 'Docente' && (int) $intervencionModel->alerta?->inscripcion?->grupo?->docente_id !== (int) $request->user()->id) {
            abort(403, 'No autorizado para modificar intervenciones de otros grupos.');
        }

        $validated = $request->validate([
            'resultado'  => ['required', 'string', Rule::in(IntervencionSeguimiento::RESULTADOS)],
            'compromiso' => ['nullable', 'string'],
        ]);

        $datosUpdate = ['resultado' => $validated['resultado']];
        if ($request->has('compromiso')) {
            $datosUpdate['compromiso'] = $validated['compromiso'];
        }

        $intervencionModel->update($datosUpdate);

        if ($validated['resultado'] === 'MEJORA_EVIDENTE' && $intervencionModel->alerta) {
            $intervencionModel->alerta->update(['estado' => 'RESUELTA']);
        }

        return back()->with('success', 'Resultado de la intervención actualizado.');
    }

    /**
     * Registra una nueva versión del modelo predictivo ML (HU22).
     */
    public function storeVersionModelo(Request $request): RedirectResponse
    {
        $rol = $request->user()->rol?->nombre;
        abort_unless(
            in_array($rol, ['Propietario', 'Secretaria'], true),
            403,
            'Solo administración puede registrar nuevas versiones del modelo.'
        );

        $validated = $request->validate([
            'version'             => ['required', 'string', 'max:30', 'unique:version_modelo,version'],
            'nombre'              => ['required', 'string', 'max:120'],
            'algoritmo'           => ['required', 'string', 'max:80'],
            'accuracy'            => ['required', 'numeric', 'min:0', 'max:100'],
            'f1_score'            => ['required', 'numeric', 'min:0', 'max:100'],
            'fecha_entrenamiento' => ['nullable', 'date'],
            'url_endpoint'        => ['nullable', 'string', 'max:255'],
            'descripcion'         => ['nullable', 'string'],
            'activo'              => ['nullable', 'boolean'],
        ]);

        $activar = (bool) ($validated['activo'] ?? true);

        if ($activar) {
            VersionModelo::query()->update(['activo' => false]);
        }

        VersionModelo::create([
            'version'             => $validated['version'],
            'nombre'              => $validated['nombre'],
            'algoritmo'           => $validated['algoritmo'],
            'accuracy'            => $validated['accuracy'],
            'f1_score'            => $validated['f1_score'],
            'fecha_entrenamiento' => $validated['fecha_entrenamiento'] ?? now()->toDateString(),
            'url_endpoint'        => $validated['url_endpoint'] ?? 'http://127.0.0.1:8001/api/v1/predict',
            'activo'              => $activar,
            'descripcion'         => $validated['descripcion'] ?? null,
        ]);

        if ($activar) {
            $this->service->analizarAcademia(null, null, 'MOTOR_LOCAL');
        }

        return back()->with('success', "Versión {$validated['version']} registrada exitosamente.");
    }

    /**
     * Activa una versión específica del modelo predictivo y recalcula las alertas (HU22).
     */
    public function activarVersionModelo(Request $request, int $modelo): RedirectResponse
    {
        $rol = $request->user()->rol?->nombre;
        abort_unless(
            in_array($rol, ['Propietario', 'Secretaria'], true),
            403,
            'Solo administración puede cambiar la versión activa del modelo.'
        );

        $modeloTarget = VersionModelo::findOrFail($modelo);

        VersionModelo::query()->update(['activo' => false]);
        $modeloTarget->update(['activo' => true]);

        $this->service->analizarAcademia(null, null, 'MOTOR_LOCAL');

        return back()->with('success', "Versión {$modeloTarget->version} ({$modeloTarget->nombre}) activada y predicciones actualizadas.");
    }

    /**
     * Endpoint JSON para n8n y microservicio Python: entrega el dataset de alumnos y sus features (HU19).
     */
    public function apiDatasetAlumnos(Request $request): JsonResponse
    {
        return response()->json($this->service->generarDatasetAlumnos());
    }

    /**
     * Webhook JSON para recibir alertas automatizadas desde n8n o motor ML externo (HU19).
     */
    public function apiWebhookAlertas(Request $request): JsonResponse
    {
        // Contrato batch de FastAPI: tambien un lote vacio es una operacion sin cambios.
        if ($request->exists('resultados')) {
            $data = $request->validate([
                'total_registros' => ['required', 'integer', 'min:0', 'max:1000'],
                'predicciones_calculadas' => ['required', 'integer', 'min:0', 'max:1000'],
                'resultados' => ['present', 'array', 'max:1000'],
                'resultados.*.inscripcion_id' => ['required', 'integer', 'min:1', 'distinct'],
                'resultados.*.estado_prediccion' => ['required', 'in:CALCULADA,DATOS_INSUFICIENTES,NO_APLICA_RETIRO_CONFIRMADO'],
                'resultados.*.version_modelo' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9._-]+$/'],
                'resultados.*.score_riesgo' => ['present', 'nullable', 'numeric', 'between:0,100'],
                'resultados.*.nivel_riesgo' => ['present', 'nullable', 'in:BAJO,MEDIO,ALTO'],
                'resultados.*.factores_riesgo' => ['present', 'array'],
                'resultados.*.resumen_explicativo' => ['required', 'string', 'max:10000'],
                'resultados.*.explicacion_modelo' => ['nullable', 'array'],
                'resultados.*.acciones_sugeridas' => ['array', 'max:10'],
                'resultados.*.acciones_sugeridas.*' => ['string', 'max:2000'],
                'resultados.*.experimental' => ['required', 'boolean'],
                'resultados.*.entrenado_con' => ['required', 'string', 'max:100'],
                'resultados.*.estimacion_reprobo_pct' => ['nullable', 'numeric', 'between:0,100'],
                'resultados.*.estimacion_abandono_pct' => ['nullable', 'numeric', 'between:0,100'],
                'resultados.*.requiere_seguimiento_por_pausa' => ['boolean'],
                'resultados.*.aviso' => ['nullable', 'string', 'max:2000'],
            ]);
            $calculadas = collect($data['resultados'])->where('estado_prediccion', 'CALCULADA')->count();
            if (count($data['resultados']) !== $data['total_registros']
                || $calculadas !== $data['predicciones_calculadas']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'resultados' => 'Los totales del lote no coinciden.',
                ]);
            }
            return response()->json(['status' => 'ok', 'modo' => 'N8N_FASTAPI_BATCH']
                + $this->service->procesarResultadosMl($data['resultados']));
        }


        $predicciones = $request->input('predicciones', $request->input('alertas', []));
        $versionModelo = $request->input('version_modelo');

        // Si n8n envía trigger vacío o solicita ejecución directa en el servidor
        if (empty($predicciones) && $request->boolean('ejecutar_local', true)) {
            $total = $this->service->analizarAcademia(null, null, 'N8N_AUTO');
            return response()->json([
                'status'     => 'ok',
                'modo'       => 'N8N_AUTO_LOCAL',
                'procesadas' => $total,
                'timestamp'  => now()->toIso8601String(),
            ]);
        }

        $resultado = $this->service->procesarWebhookAlertas(
            is_array($predicciones) ? $predicciones : [],
            is_string($versionModelo) ? $versionModelo : null
        );

        return response()->json(array_merge(['status' => 'ok'], $resultado));
    }
}

