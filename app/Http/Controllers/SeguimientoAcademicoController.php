<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Services\SeguimientoAcademicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeguimientoAcademicoController extends Controller
{
    public function __construct(
        protected SeguimientoAcademicoService $service
    ) {}

    /**
     * Vista principal del Seguimiento Académico:
     * - Si es Alumno:
     *   - Con 1 sola inscripción activa (o en total): redirige directamente a su ficha integral.
     *   - Con múltiples inscripciones: muestra su catálogo de cursos/inscripciones para elegir.
     * - Si es Docente / Admin (Propietario, Secretaria):
     *   - Directorio de estudiantes para seguimiento con buscador y filtros reactivos.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $rol = $user->rol?->nombre;

        // Comportamiento para rol Alumno
        if ($rol === 'Alumno') {
            $inscripciones = Inscripcion::where('alumno_id', $user->id)
                ->orderByDesc('id')
                ->get();

            // Si tiene exactamente 1 inscripción activa, redirigir a su ficha
            $activas = $inscripciones->where('estado', 'ACTIVA');
            if ($activas->count() === 1) {
                return redirect()->route('seguimiento.show', $activas->first()->id);
            }

            // Si no tiene activas pero tiene exactamente 1 inscripción en total
            if ($inscripciones->count() === 1) {
                return redirect()->route('seguimiento.show', $inscripciones->first()->id);
            }

            // Si tiene varias inscripciones o ninguna, listarlas para que seleccione
            $estudiantes = $this->service->listarEstudiantesSeguimiento($request->all(), $user);

            return Inertia::render('Seguimiento/Index', [
                'es_alumno' => true,
                'inscripciones' => $estudiantes->items(),
                'estudiantes' => $estudiantes,
                'filtros' => $request->only(['buscar', 'estado']),
                'rol_usuario' => $rol,
            ]);
        }

        // Comportamiento para Docentes y Administradores (Propietario, Secretaria)
        $estudiantes = $this->service->listarEstudiantesSeguimiento($request->all(), $user);

        return Inertia::render('Seguimiento/Index', [
            'es_alumno' => false,
            'inscripciones' => $estudiantes->items(),
            'estudiantes' => $estudiantes,
            'filtros' => $request->only(['buscar', 'estado', 'grupo_id', 'curso_id']),
            'rol_usuario' => $rol,
        ]);
    }

    /**
     * Ficha Integral de Seguimiento Académico.
     * Reúne expediente digital completo: datos del alumno, curso, grupo, docente,
     * indicadores de progreso (HU11), asistencias (HU8/HU9), calificaciones (HU10)
     * y bitácora pedagógica (HU12).
     */
    public function show(Request $request, int $inscripcion): Response
    {
        $ficha = $this->service->obtenerFichaIntegral($inscripcion, $request->user());
        $rol = $request->user()->rol?->nombre;
        $esAlumno = ($rol === 'Alumno');
        $puedeEditar = in_array($rol, ['Propietario', 'Secretaria', 'Docente'], true);

        // Fusión estructurada para Ficha.vue
        $inscripcionCompleta = array_merge($ficha['inscripcion'], [
            'alumno' => $ficha['alumno'],
            'grupo' => array_merge($ficha['grupo'], [
                'curso' => $ficha['curso'],
                'docente' => $ficha['docente'],
            ]),
        ]);

        return Inertia::render('Seguimiento/Ficha', [
            'ficha' => $ficha,
            'inscripcion' => $inscripcionCompleta,
            'progreso' => $ficha['progreso'],
            'calificaciones' => $ficha['calificaciones'],
            'asistencias' => $ficha['asistencias'],
            'observaciones' => $ficha['observaciones'],
            'es_alumno' => $esAlumno,
            'puede_editar' => $puedeEditar,
            'rol_usuario' => $rol,
        ]);
    }
}
