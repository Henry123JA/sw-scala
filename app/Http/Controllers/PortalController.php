<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Grupo;
use App\Services\AgenteAtencionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    /**
     * Página principal del sitio corporativo SCALA (Figuras 7.1 a 7.7 y 7.16).
     */
    public function index(Request $request): Response
    {
        $cursos = collect();
        $metricas = [
            'cursos_activos'   => 6,
            'docentes_activos' => 5,
            'alumnos_activos'  => 10,
            'grupos_abiertos'  => 8,
        ];

        try {
            if (Schema::hasTable('curso')) {
                $cursos = Curso::where('eliminado', false)
                    ->orderBy('id')
                    ->get()
                    ->map(fn (Curso $c) => [
                        'id'                => $c->id,
                        'nombre'            => $c->nombre,
                        'descripcion'       => $c->descripcion,
                        'tipo_ensenanza'    => $c->tipo_ensenanza,
                        'duracion_estandar' => $c->duracion_estandar,
                        'estado'            => $c->estado,
                    ]);

                $metricas = [
                    'cursos_activos'   => $cursos->count() ?: 6,
                    'docentes_activos' => Schema::hasTable('docente') ? (Docente::count() ?: 5) : 5,
                    'alumnos_activos'  => Schema::hasTable('alumno') ? (Alumno::count() ?: 10) : 10,
                    'grupos_abiertos'  => Schema::hasTable('grupo') ? (Grupo::where('eliminado', false)->count() ?: 8) : 8,
                ];
            }
        } catch (\Throwable $e) {
            // Fallback seguro si no hay conexión a BD en algún entorno
        }

        return Inertia::render('Portal/Welcome', [
            'cursos'   => $cursos,
            'metricas' => $metricas,
            'redes'    => [
                'facebook'  => 'https://www.facebook.com/p/Academia-De-Musica-y-Arte-Scala-100064673172866/',
                'instagram' => 'https://www.instagram.com/academia.scala/',
                'maps_url'  => 'https://maps.app.goo.gl/Qrk9fpKYiQyFAHyi9',
            ],
            'contacto_enviado' => session('contacto_enviado'),
            'agente_config' => [
                'openai_configurado' => trim((string) config('services.openai.api_key', env('OPENAI_API_KEY', ''))) !== '',
                'modelo'             => trim((string) config('services.openai.model', env('OPENAI_MODEL', 'gpt-4o-mini'))) ?: 'gpt-4o-mini',
            ],
        ]);
    }

    /**
     * Endpoint de chat en tiempo real del Agente de Atención SCALA (Figura 7.7 - Opción A3 + B1).
     */
    public function chatAgente(Request $request, AgenteAtencionService $agenteService): JsonResponse
    {
        $validated = $request->validate([
            'mensaje'   => ['required', 'string', 'max:1000'],
            'historial' => ['nullable', 'array', 'max:12'],
        ]);

        $resultado = $agenteService->responder(
            $validated['mensaje'],
            $validated['historial'] ?? [],
            $request->user()
        );

        return response()->json($resultado);
    }

    /**
     * Procesa el formulario de contacto público (Figura 7.6).
     */
    public function enviarContacto(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'        => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'max:120'],
            'telefono'      => ['required', 'string', 'max:40'],
            'curso_interes' => ['nullable', 'string', 'max:100'],
            'mensaje'       => ['required', 'string', 'max:1000'],
        ]);

        return back()->with('contacto_enviado', [
            'nombre'        => $validated['nombre'],
            'email'         => $validated['email'],
            'curso_interes' => $validated['curso_interes'] ?? 'Información General',
            'fecha'         => now()->format('d/m/Y H:i'),
            'codigo_ticket' => 'SCALA-' . rand(1000, 9999),
        ]);
    }

    /**
     * Vista independiente de la Política de Privacidad (Figuras 7.10 y 7.11).
     */
    public function politicaPrivacidad(Request $request): Response
    {
        $user = $request->user();
        $aceptadaEnSesion = (bool) $request->session()->get('politica_aceptada', false);
        $aceptadaEnBd = $user && $user->politica_aceptada_at !== null;
        $confirmado = $request->boolean('confirmado') || session('politica_confirmada') || ($aceptadaEnBd && ! $request->boolean('revisar'));

        return Inertia::render('Portal/PoliticaPrivacidad', [
            'confirmado'      => (bool) $confirmado,
            'fecha_aceptacion'=> $user?->politica_aceptada_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
            'onboarding'      => $request->boolean('onboarding', true),
            'usuario'         => $user ? [
                'id'              => $user->id,
                'nombre_completo' => trim($user->nombres . ' ' . $user->apellidos),
                'email'           => $user->email,
                'codigo'          => $user->codigo,
                'rol'             => $user->rol?->nombre ?? 'Usuario',
            ] : null,
        ]);
    }

    /**
     * Registra la aceptación de la Política de Privacidad y muestra la confirmación (Figura 7.11).
     */
    public function aceptarPolitica(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            try {
                if (Schema::hasColumn('usuario', 'politica_aceptada_at')) {
                    $user->update(['politica_aceptada_at' => now()]);
                }
            } catch (\Throwable $e) {
                // Continuar usando sesión si la columna aún no fue migrada
            }
        }

        $request->session()->put('politica_aceptada', true);

        return redirect()
            ->route('portal.politica', ['confirmado' => 1, 'onboarding' => 1])
            ->with('politica_confirmada', true);
    }

    /**
     * Vista independiente de los Términos y Condiciones (Figuras 7.12 y 7.13).
     */
    public function terminosCondiciones(Request $request): Response
    {
        $user = $request->user();
        $aceptadosEnBd = $user && $user->terminos_aceptados_at !== null;
        $confirmado = $request->boolean('confirmado') || session('terminos_confirmados') || ($aceptadosEnBd && ! $request->boolean('revisar'));

        return Inertia::render('Portal/TerminosCondiciones', [
            'confirmado'       => (bool) $confirmado,
            'fecha_aceptacion' => $user?->terminos_aceptados_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
            'onboarding'       => $request->boolean('onboarding', true),
            'usuario'          => $user ? [
                'id'              => $user->id,
                'nombre_completo' => trim($user->nombres . ' ' . $user->apellidos),
                'email'           => $user->email,
                'codigo'          => $user->codigo,
                'rol'             => $user->rol?->nombre ?? 'Usuario',
            ] : null,
        ]);
    }

    /**
     * Registra la aceptación de los Términos y Condiciones y muestra la confirmación (Figura 7.13).
     */
    public function aceptarTerminos(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            try {
                if (Schema::hasColumn('usuario', 'terminos_aceptados_at')) {
                    $user->update(['terminos_aceptados_at' => now()]);
                }
            } catch (\Throwable $e) {
                // Continuar usando sesión
            }
        }

        $request->session()->put('terminos_aceptados', true);

        return redirect()
            ->route('portal.terminos', ['confirmado' => 1, 'onboarding' => 1])
            ->with('terminos_confirmados', true);
    }

    /**
     * Vista de Mensaje de Bienvenida tras completar ambas aceptaciones (Figura 7.14).
     */
    public function bienvenida(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Portal/Bienvenida', [
            'fecha_proceso' => now()->format('d/m/Y H:i'),
            'usuario'       => $user ? [
                'id'              => $user->id,
                'nombre_completo' => trim($user->nombres . ' ' . $user->apellidos),
                'nombres'         => $user->nombres,
                'apellidos'       => $user->apellidos,
                'email'           => $user->email,
                'codigo'          => $user->codigo,
                'rol'             => $user->rol?->nombre ?? 'Usuario',
            ] : [
                'id'              => 0,
                'nombre_completo' => 'Estudiante / Usuario SCALA',
                'nombres'         => 'Usuario',
                'apellidos'       => 'SCALA',
                'email'           => 'usuario@academia.com',
                'codigo'          => 'ALU-001',
                'rol'             => 'Alumno',
            ],
        ]);
    }

    /**
     * Reinicia el estado de aceptación legal para poder repetir el flujo de primer inicio de sesión.
     */
    public function reiniciarOnboarding(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            try {
                if (Schema::hasColumn('usuario', 'politica_aceptada_at')) {
                    $user->update([
                        'politica_aceptada_at'  => null,
                        'terminos_aceptados_at' => null,
                    ]);
                }
            } catch (\Throwable $e) {
                // Ignorar si no existe columna
            }
        }

        $request->session()->forget(['politica_aceptada', 'terminos_aceptados']);

        return redirect()->route('portal.politica', ['revisar' => 1, 'onboarding' => 1]);
    }
}
