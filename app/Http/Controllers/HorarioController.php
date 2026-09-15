<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\Horario\StoreHorarioDocenteRequest;
use App\Http\Requests\Horario\StoreHorarioGrupoRequest;
use App\Services\HorarioService;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Sala;
use App\Models\HorarioGrupo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HorarioController extends Controller
{
    public function __construct(protected HorarioService $horarioService) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $rol = $user->rol->nombre;
        $userId = $user->id;

        $docenteIdFilter = $request->query('docente_id');
        $salaIdFilter = $request->query('sala_id');
        $grupoIdFilter = $request->query('grupo_id');

        $horariosDocentes = [];
        $horariosGrupos = [];

        if ($rol === 'Docente') {
            $horariosDocentes = $this->horarioService->listarHorariosDocentes($userId);
            // Filter group schedules where the teacher is this teacher
            $horariosGrupos = HorarioGrupo::query()
                ->with(['grupo.docente.usuario', 'grupo.curso', 'sala'])
                ->whereHas('grupo', function ($query) use ($userId) {
                    $query->where('docente_id', $userId);
                })
                ->get();
        } elseif ($rol === 'Alumno') {
            // Filter group schedules where the student has active enrollment
            $grupoIds = DB::table('inscripcion')
                ->where('alumno_id', $userId)
                ->where('estado', 'ACTIVA')
                ->pluck('grupo_id')
                ->toArray();

            $horariosGrupos = HorarioGrupo::query()
                ->with(['grupo.docente.usuario', 'grupo.curso', 'sala'])
                ->whereIn('grupo_id', $grupoIds)
                ->get();
        } else {
            // Propietario or Secretaria
            $horariosDocentes = $this->horarioService->listarHorariosDocentes($docenteIdFilter ? (int) $docenteIdFilter : null);
            $horariosGrupos = $this->horarioService->listarHorariosGrupos(
                $salaIdFilter ? (int) $salaIdFilter : null,
                $grupoIdFilter ? (int) $grupoIdFilter : null
            );
        }

        $docentes = Docente::with('usuario')->get()->map(function ($d) {
            return [
                'id' => $d->id,
                'nombre' => $d->usuario->nombres . ' ' . $d->usuario->apellidos,
            ];
        });

        $grupos = Grupo::with('docente.usuario', 'curso')->get()->map(function ($g) {
            return [
                'id' => $g->id,
                'codigo_grupo' => $g->codigo_grupo,
                'curso' => $g->curso->nombre,
                'docente' => $g->docente->usuario->nombres . ' ' . $g->docente->usuario->apellidos,
            ];
        });

        $salas = Sala::all()->map(function ($s) {
            return [
                'id' => $s->id,
                'nombre' => $s->nombre,
            ];
        });

        return Inertia::render('Horario/Index', [
            'horariosDocentes' => $horariosDocentes,
            'horariosGrupos'   => $horariosGrupos,
            'docentes'         => $docentes,
            'grupos'           => $grupos,
            'salas'            => $salas,
            'filters'          => [
                'docente_id' => $docenteIdFilter,
                'sala_id'    => $salaIdFilter,
                'grupo_id'   => $grupoIdFilter,
            ]
        ]);
    }

    public function storeDocente(StoreHorarioDocenteRequest $request): RedirectResponse
    {
        try {
            $this->horarioService->crearHorarioDocente($request->validated());
            return redirect()->route('horarios.index')->with('success', 'Disponibilidad docente registrada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function updateDocente(StoreHorarioDocenteRequest $request, int $id): RedirectResponse
    {
        try {
            $this->horarioService->actualizarHorarioDocente($id, $request->validated());
            return redirect()->route('horarios.index')->with('success', 'Disponibilidad docente actualizada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function destroyDocente(int $id): RedirectResponse
    {
        try {
            $this->horarioService->eliminarHorarioDocente($id);
            return redirect()->route('horarios.index')->with('success', 'Disponibilidad docente eliminada exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function storeGrupo(StoreHorarioGrupoRequest $request): RedirectResponse
    {
        try {
            $this->horarioService->crearHorarioGrupo($request->validated());
            return redirect()->route('horarios.index')->with('success', 'Horario de grupo registrado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function updateGrupo(StoreHorarioGrupoRequest $request, int $id): RedirectResponse
    {
        try {
            $this->horarioService->actualizarHorarioGrupo($id, $request->validated());
            return redirect()->route('horarios.index')->with('success', 'Horario de grupo actualizado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors([$e->getField() => $e->getMessage()]);
        }
    }

    public function destroyGrupo(int $id): RedirectResponse
    {
        try {
            $this->horarioService->eliminarHorarioGrupo($id);
            return redirect()->route('horarios.index')->with('success', 'Horario de grupo eliminado exitosamente.');
        } catch (BusinessException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }
}
