<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\HorarioDocente;
use App\Models\HorarioGrupo;
use App\Models\Grupo;
use App\Models\Docente;
use App\Models\Sala;
use Illuminate\Support\Facades\DB;

class HorarioService
{
    /**
     * List all availability schedules of teachers.
     */
    public function listarHorariosDocentes(?int $docenteId = null)
    {
        return HorarioDocente::query()
            ->with(['docente.usuario'])
            ->when($docenteId, function ($query) use ($docenteId) {
                $query->where('docente_id', $docenteId);
            })
            ->get();
    }

    /**
     * List all room schedules (horario_grupo).
     */
    public function listarHorariosGrupos(?int $salaId = null, ?int $grupoId = null)
    {
        return HorarioGrupo::query()
            ->with(['grupo.docente.usuario', 'grupo.curso', 'sala'])
            ->when($salaId, function ($query) use ($salaId) {
                $query->where('sala_id', $salaId);
            })
            ->when($grupoId, function ($query) use ($grupoId) {
                $query->where('grupo_id', $grupoId);
            })
            ->get();
    }

    /**
     * Create teacher availability.
     *
     * @throws BusinessException
     */
    public function crearHorarioDocente(array $data): HorarioDocente
    {
        $inicio = $this->formatTime($data['hora_inicio']);
        $fin = $this->formatTime($data['hora_fin']);
        $this->verificarTraslapeDocente($data['docente_id'], $data['dia_semana'], $inicio, $fin);

        return HorarioDocente::create([
            'docente_id'  => $data['docente_id'],
            'dia_semana'  => $data['dia_semana'],
            'hora_inicio' => $inicio,
            'hora_fin'    => $fin,
        ]);
    }

    /**
     * Update teacher availability.
     *
     * @throws BusinessException
     */
    public function actualizarHorarioDocente(int $id, array $data): HorarioDocente
    {
        $horario = HorarioDocente::findOrFail($id);

        $docenteId = $data['docente_id'] ?? $horario->docente_id;
        $diaSemana = $data['dia_semana'] ?? $horario->dia_semana;
        $inicio    = $this->formatTime($data['hora_inicio'] ?? $horario->hora_inicio);
        $fin       = $this->formatTime($data['hora_fin'] ?? $horario->hora_fin);

        $this->verificarTraslapeDocente($docenteId, $diaSemana, $inicio, $fin, $id);

        // Before updating, verify if this leaves any existing group schedule for this teacher without containment.
        $horario->dia_semana = $diaSemana;
        $horario->hora_inicio = $inicio;
        $horario->hora_fin = $fin;

        // Perform the temporary checks on updated data
        $this->verificarContencionDocenteTemporal($docenteId, $diaSemana, $id, $inicio, $fin);

        $horario->save();

        return $horario->fresh();
    }

    /**
     * Delete teacher availability.
     *
     * @throws BusinessException
     */
    public function eliminarHorarioDocente(int $id): void
    {
        $horario = HorarioDocente::findOrFail($id);

        // Check if removing this block would leave any of the teacher's group schedules uncontained
        $this->verificarContencionDocenteAlEliminar($horario->docente_id, $horario->dia_semana, $id);

        $horario->delete();
    }

    /**
     * Create group schedule.
     *
     * @throws BusinessException
     */
    public function crearHorarioGrupo(array $data): HorarioGrupo
    {
        $grupo = Grupo::findOrFail($data['grupo_id']);
        $docenteId = $grupo->docente_id;
        $inicio = $this->formatTime($data['hora_inicio']);
        $fin = $this->formatTime($data['hora_fin']);

        $this->verificarTraslapeGrupoSala($data['sala_id'], $data['dia_semana'], $inicio, $fin);
        $this->verificarContencionGrupo($docenteId, $data['dia_semana'], $inicio, $fin);

        return HorarioGrupo::create([
            'grupo_id'    => $data['grupo_id'],
            'sala_id'     => $data['sala_id'],
            'dia_semana'  => $data['dia_semana'],
            'hora_inicio' => $inicio,
            'hora_fin'    => $fin,
            'tipo_sesion' => $data['tipo_sesion'] ?? null,
        ]);
    }

    /**
     * Update group schedule.
     *
     * @throws BusinessException
     */
    public function actualizarHorarioGrupo(int $id, array $data): HorarioGrupo
    {
        $horario = HorarioGrupo::findOrFail($id);

        $grupoId   = $data['grupo_id'] ?? $horario->grupo_id;
        $salaId    = $data['sala_id'] ?? $horario->sala_id;
        $diaSemana = $data['dia_semana'] ?? $horario->dia_semana;
        $inicio    = $this->formatTime($data['hora_inicio'] ?? $horario->hora_inicio);
        $fin       = $this->formatTime($data['hora_fin'] ?? $horario->hora_fin);

        $grupo = Grupo::findOrFail($grupoId);
        $docenteId = $grupo->docente_id;

        $this->verificarTraslapeGrupoSala($salaId, $diaSemana, $inicio, $fin, $id);
        $this->verificarContencionGrupo($docenteId, $diaSemana, $inicio, $fin);

        $horario->update([
            'grupo_id'    => $grupoId,
            'sala_id'     => $salaId,
            'dia_semana'  => $diaSemana,
            'hora_inicio' => $inicio,
            'hora_fin'    => $fin,
            'tipo_sesion' => $data['tipo_sesion'] ?? $horario->tipo_sesion,
        ]);

        return $horario->fresh();
    }

    /**
     * Delete group schedule.
     */
    public function eliminarHorarioGrupo(int $id): void
    {
        $horario = HorarioGrupo::findOrFail($id);
        $horario->delete();
    }

    // ─── Verification Helpers ────────────────────────────────────────────────

    private function verificarTraslapeDocente(int $docenteId, string $diaSemana, string $inicio, string $fin, ?int $excluirId = null): void
    {
        $existeTraslape = HorarioDocente::where('docente_id', $docenteId)
            ->where('dia_semana', $diaSemana)
            ->when($excluirId, function ($query) use ($excluirId) {
                $query->where('id', '!=', $excluirId);
            })
            ->where(function ($query) use ($inicio, $fin) {
                $query->where('hora_inicio', '<', $fin)
                      ->where('hora_fin', '>', $inicio);
            })
            ->exists();

        if ($existeTraslape) {
            throw new BusinessException('El docente ya tiene un horario registrado que se traslapa con las horas especificadas.', 'hora_inicio');
        }
    }

    private function verificarTraslapeGrupoSala(int $salaId, string $diaSemana, string $inicio, string $fin, ?int $excluirId = null): void
    {
        $existeTraslape = HorarioGrupo::where('sala_id', $salaId)
            ->where('dia_semana', $diaSemana)
            ->when($excluirId, function ($query) use ($excluirId) {
                $query->where('id', '!=', $excluirId);
            })
            ->where(function ($query) use ($inicio, $fin) {
                $query->where('hora_inicio', '<', $fin)
                      ->where('hora_fin', '>', $inicio);
            })
            ->exists();

        if ($existeTraslape) {
            throw new BusinessException('La sala ya tiene un grupo asignado en ese horario.', 'hora_inicio');
        }
    }

    private function verificarContencionGrupo(int $docenteId, string $diaSemana, string $inicio, string $fin): void
    {
        $contencionValida = HorarioDocente::where('docente_id', $docenteId)
            ->where('dia_semana', $diaSemana)
            ->where('hora_inicio', '<=', $inicio)
            ->where('hora_fin', '>=', $fin)
            ->exists();

        if (!$contencionValida) {
            throw new BusinessException('El horario seleccionado no coincide con la disponibilidad del docente asignado al grupo.', 'hora_inicio');
        }
    }

    private function verificarContencionDocenteAlEliminar(int $docenteId, string $diaSemana, int $excluirHorarioDocenteId): void
    {
        // Get all active group schedules for this teacher on this day
        $horariosGrupo = HorarioGrupo::whereHas('grupo', function ($query) use ($docenteId) {
                $query->where('docente_id', $docenteId);
            })
            ->where('dia_semana', $diaSemana)
            ->get();

        foreach ($horariosGrupo as $hg) {
            $contencionValida = HorarioDocente::where('docente_id', $docenteId)
                ->where('dia_semana', $diaSemana)
                ->where('id', '!=', $excluirHorarioDocenteId)
                ->where('hora_inicio', '<=', $hg->hora_inicio)
                ->where('hora_fin', '>=', $hg->hora_fin)
                ->exists();

            if (!$contencionValida) {
                throw new BusinessException(
                    "No se puede eliminar este horario de disponibilidad porque el grupo {$hg->grupo->codigo_grupo} está programado en ese horario.",
                    'general'
                );
            }
        }
    }

    private function verificarContencionDocenteTemporal(int $docenteId, string $diaSemana, int $idParaModificar, string $nuevoInicio, string $nuevoFin): void
    {
        // Get all active group schedules for this teacher on this day
        $horariosGrupo = HorarioGrupo::whereHas('grupo', function ($query) use ($docenteId) {
                $query->where('docente_id', $docenteId);
            })
            ->where('dia_semana', $diaSemana)
            ->get();

        foreach ($horariosGrupo as $hg) {
            // Check containment either by other blocks OR by the newly modified block
            $otrosValidos = HorarioDocente::where('docente_id', $docenteId)
                ->where('dia_semana', $diaSemana)
                ->where('id', '!=', $idParaModificar)
                ->where('hora_inicio', '<=', $hg->hora_inicio)
                ->where('hora_fin', '>=', $hg->hora_fin)
                ->exists();

            $modificadoValido = ($nuevoInicio <= $hg->hora_inicio && $nuevoFin >= $hg->hora_fin);

            if (!$otrosValidos && !$modificadoValido) {
                throw new BusinessException(
                    "No se puede modificar este horario de disponibilidad porque el grupo {$hg->grupo->codigo_grupo} está programado en ese horario.",
                    'hora_inicio'
                );
            }
        }
    }

    private function formatTime(string $time): string
    {
        return date('H:i:s', strtotime($time));
    }
}
