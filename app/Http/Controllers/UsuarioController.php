<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\Usuario\StoreAlumnoRequest;
use App\Http\Requests\Usuario\StoreDocenteRequest;
use App\Http\Requests\Usuario\StoreSecretariaRequest;
use App\Http\Requests\Usuario\UpdateAlumnoRequest;
use App\Http\Requests\Usuario\UpdateDocenteRequest;
use App\Http\Requests\Usuario\UpdateSecretariaRequest;
use App\Models\Alumno;
use App\Models\Especialidad;
use App\Models\Usuario;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function __construct(protected UsuarioService $usuarioService) {}

    public function index(Request $request): Response
    {
        $buscar = $request->query('buscar', '');
        $usuarios = $this->usuarioService->listarTodos($buscar);

        return Inertia::render('Usuario/Index', [
            'usuarios' => $usuarios,
            'buscar' => $buscar,
        ]);
    }

    // ─── Alumnos ─────────────────────────────────────────────────────────────

    public function indexAlumnos(Request $request): Response
    {
        $buscar  = $request->query('buscar', '');
        $alumnos = $this->usuarioService->listarAlumnos($buscar);

        return Inertia::render('Usuario/Alumno/Index', [
            'alumnos' => $alumnos,
            'buscar'  => $buscar,
        ]);
    }

    public function createAlumno(): Response
    {
        $alumnos = Alumno::with('usuario')->get()->map(fn($a) => [
            'id'     => $a->id,
            'nombre' => $a->usuario?->nombres . ' ' . $a->usuario?->apellidos,
        ]);

        return Inertia::render('Usuario/Alumno/Create', [
            'alumnos' => $alumnos,
        ]);
    }

    public function storeAlumno(StoreAlumnoRequest $request): RedirectResponse
    {
        try {
            $this->usuarioService->crearAlumno($request->validated());
            return redirect()->route('alumnos.index')->with('success', 'Alumno creado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function editAlumno(int $id): Response
    {
        $usuario = Usuario::with('alumno')->findOrFail($id);
        $alumnos = Alumno::with('usuario')->where('id', '!=', $id)->get()->map(fn($a) => [
            'id'     => $a->id,
            'nombre' => $a->usuario?->nombres . ' ' . $a->usuario?->apellidos,
        ]);

        return Inertia::render('Usuario/Alumno/Edit', [
            'usuario' => $usuario,
            'alumnos' => $alumnos,
        ]);
    }

    public function updateAlumno(UpdateAlumnoRequest $request, int $id): RedirectResponse
    {
        try {
            $data = $request->validated();
            $alumnoFields = [
                'codigo', 'estado', 'fecha_nacimiento', 'sexo',
                'telefono', 'telefono_alternativo', 'nivel',
                'referido_por', 'observaciones',
            ];

            $data['alumno'] = array_intersect_key($data, array_flip($alumnoFields));

            $this->usuarioService->actualizar($id, $data);
            return redirect()->route('alumnos.index')->with('success', 'Alumno actualizado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    // ─── Docentes ─────────────────────────────────────────────────────────────

    public function indexDocentes(Request $request): Response
    {
        $buscar   = $request->query('buscar', '');
        $docentes = $this->usuarioService->listarDocentes($buscar);

        return Inertia::render('Usuario/Docente/Index', [
            'docentes' => $docentes,
            'buscar'   => $buscar,
        ]);
    }

    public function createDocente(): Response
    {
        $especialidades = Especialidad::select('id', 'nombre')->get();

        return Inertia::render('Usuario/Docente/Create', [
            'especialidades' => $especialidades,
        ]);
    }

    public function storeDocente(StoreDocenteRequest $request): RedirectResponse
    {
        try {
            $data          = $request->validated();
            $especialidades = $data['especialidades'] ?? [];
            unset($data['especialidades']);

            $this->usuarioService->crearDocente($data, $especialidades);
            return redirect()->route('docentes.index')->with('success', 'Docente creado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function editDocente(int $id): Response
    {
        $usuario        = Usuario::with('docente.especialidades')->findOrFail($id);
        $especialidades = Especialidad::select('id', 'nombre')->get();

        return Inertia::render('Usuario/Docente/Edit', [
            'usuario'        => $usuario,
            'especialidades' => $especialidades,
        ]);
    }

    public function updateDocente(UpdateDocenteRequest $request, int $id): RedirectResponse
    {
        try {
            $data          = $request->validated();
            $especialidades = $data['especialidades'] ?? null;

            if ($especialidades !== null) {
                $data['especialidades'] = $especialidades;
            }

            $docenteFields = [
                'codigo', 'estado', 'fecha_nacimiento', 'fecha_incorporacion',
                'telefono', 'tarifa_horaria', 'observaciones',
            ];
            $data['docente'] = array_intersect_key($data, array_flip($docenteFields));

            $this->usuarioService->actualizar($id, $data);
            return redirect()->route('docentes.index')->with('success', 'Docente actualizado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    // ─── Secretarias ─────────────────────────────────────────────────────────

    public function indexSecretarias(Request $request): Response
    {
        $buscar      = $request->query('buscar', '');
        $secretarias = $this->usuarioService->listarSecretarias($buscar);

        return Inertia::render('Usuario/Secretaria/Index', [
            'secretarias' => $secretarias,
            'buscar'      => $buscar,
        ]);
    }

    public function createSecretaria(): Response
    {
        return Inertia::render('Usuario/Secretaria/Create');
    }

    public function storeSecretaria(StoreSecretariaRequest $request): RedirectResponse
    {
        try {
            $data                    = $request->validated();
            $data['_rol_solicitante'] = $request->user()->rol?->nombre;

            $this->usuarioService->crearSecretaria($data);
            return redirect()->route('secretarias.index')->with('success', 'Secretaria creada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function editSecretaria(int $id): Response
    {
        $usuario = Usuario::findOrFail($id);

        return Inertia::render('Usuario/Secretaria/Edit', [
            'usuario' => $usuario,
        ]);
    }

    public function updateSecretaria(UpdateSecretariaRequest $request, int $id): RedirectResponse
    {
        try {
            $this->usuarioService->actualizar($id, $request->validated());
            return redirect()->route('secretarias.index')->with('success', 'Secretaria actualizada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    // ─── Shared ───────────────────────────────────────────────────────────────

    public function destroy(int $id, Request $request): RedirectResponse
    {
        try {
            $rolSolicitante = $request->user()->rol?->nombre ?? '';
            $this->usuarioService->darDeBaja($id, $rolSolicitante);
            return back()->with('success', 'Usuario dado de baja exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }
}
