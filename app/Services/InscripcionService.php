<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Inscripcion;
use App\Models\Grupo;
use App\Models\Mensualidad;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InscripcionService
{
    /**
     * List enrollments with search.
     */
    public function listar(string $buscar = '', ?string $estado = null, ?int $alumnoId = null, ?int $docenteId = null): LengthAwarePaginator
    {
        return Inscripcion::query()
            ->with(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario', 'mensualidades'])
            ->when($buscar, function ($query) use ($buscar) {
                $query->whereHas('alumno.usuario', function ($q) use ($buscar) {
                    $q->where('nombres', 'like', "%{$buscar}%")
                      ->orWhere('apellidos', 'like', "%{$buscar}%");
                })->orWhereHas('grupo.curso', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%");
                });
            })
            ->when($estado, function ($query) use ($estado) {
                $query->where('estado', $estado);
            })
            ->when($alumnoId, function ($query) use ($alumnoId) {
                $query->where('alumno_id', $alumnoId);
            })
            ->when($docenteId, function ($query) use ($docenteId) {
                $query->whereHas('grupo', function ($q) use ($docenteId) {
                    $q->where('docente_id', $docenteId);
                });
            })
            ->orderBy('fecha', 'desc')
            ->paginate(15);
    }

    /**
     * Get enrollment by ID.
     */
    public function obtenerPorId(int $id): Inscripcion
    {
        return Inscripcion::with(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario', 'mensualidades'])->findOrFail($id);
    }

    /**
     * Create enrollment.
     * fecha_vencimiento = fecha_inicio_clases
     * No genera mensualidades por defecto.
     */
    public function crear(array $data): Inscripcion
    {
        return DB::transaction(function () use ($data) {
            $grupo = Grupo::findOrFail($data['grupo_id']);

            // 1. Group capacity check
            $activeCount = Inscripcion::where('grupo_id', $grupo->id)
                ->whereIn('estado', ['ACTIVA', 'PAUSADA', 'VENCIDA'])
                ->count();

            if ($activeCount >= $grupo->capacidad_maxima) {
                throw new BusinessException('El grupo ha alcanzado su capacidad máxima.', 'grupo_id');
            }

            // 2. Unique enrollment check
            $exists = Inscripcion::where('grupo_id', $grupo->id)
                ->where('alumno_id', $data['alumno_id'])
                ->whereIn('estado', ['ACTIVA', 'PAUSADA', 'VENCIDA'])
                ->exists();

            if ($exists) {
                throw new BusinessException('El alumno ya tiene una inscripción activa, vencida o pausada en este grupo.', 'alumno_id');
            }

            $montoMensual = $grupo->curso->precio;
            $fechaInicio = $data['fecha_inicio_clases'];

            // 3. Create Inscripcion — vencimiento = fecha de inicio
            $inscripcion = Inscripcion::create([
                'alumno_id'           => $data['alumno_id'],
                'grupo_id'            => $data['grupo_id'],
                'fecha'               => $data['fecha'] ?? now()->toDateString(),
                'fecha_inicio_clases' => $fechaInicio,
                'fecha_vencimiento'   => $fechaInicio,
                'monto_mensual'       => $montoMensual,
                'estado'              => 'ACTIVA',
                'observaciones'       => $data['observaciones'] ?? null,
            ]);

            return $inscripcion;
        });
    }

    /**
     * Update enrollment info.
     */
    public function actualizar(int $id, array $data): Inscripcion
    {
        $inscripcion = Inscripcion::findOrFail($id);

        $inscripcion->update(array_filter([
            'fecha_inicio_clases' => $data['fecha_inicio_clases'] ?? null,
            'observaciones'       => $data['observaciones'] ?? null,
        ], fn($v) => $v !== null));

        return $inscripcion->fresh();
    }

    /**
     * Pause enrollment.
     * Solo registra fechas. Los días de pausa se agregan al vencimiento al reanudar.
     */
    public function pausar(int $id, array $data): Inscripcion
    {
        return DB::transaction(function () use ($id, $data) {
            $inscripcion = Inscripcion::findOrFail($id);

            $estadosValidos = ['ACTIVA', 'VENCIDA'];
            if (!in_array($inscripcion->estado, $estadosValidos)) {
                throw new BusinessException('Solo se pueden pausar inscripciones activas o vencidas.', 'estado');
            }

            $fechaPausa = $data['fecha_pausa'] ?? now()->toDateString();

            $inscripcion->update([
                'estado'      => 'PAUSADA',
                'fecha_pausa' => $fechaPausa,
            ]);

            // Ya no anulamos mensualidades — porque no hay mensualidades pre-generadas.
            // Solo guardamos la fecha de pausa para calcular la extensión al reanudar.

            return $inscripcion->fresh();
        });
    }

    /**
     * Resume enrollment.
     * Extiende fecha_vencimiento por los días entre pausa y retorno.
     * Luego determina el estado según si la nueva fecha de vencimiento es futura o pasada.
     */
    public function reanudar(int $id, array $data): Inscripcion
    {
        return DB::transaction(function () use ($id, $data) {
            $inscripcion = Inscripcion::findOrFail($id);

            if ($inscripcion->estado !== 'PAUSADA') {
                throw new BusinessException('Solo se pueden reanudar inscripciones pausadas.', 'estado');
            }

            $fechaRetorno = Carbon::parse($data['fecha_retorno'] ?? now()->toDateString());
            $fechaPausa = Carbon::parse($inscripcion->fecha_pausa);

            // Días entre pausa y retorno
            $diasPausa = $fechaPausa->diffInDays($fechaRetorno);

            // Extender fecha_vencimiento por los días de pausa
            $nuevaFechaVencimiento = Carbon::parse($inscripcion->fecha_vencimiento)->addDays($diasPausa);

            // Determinar estado
            $hoy = Carbon::today();
            $nuevoEstado = $nuevaFechaVencimiento->greaterThanOrEqualTo($hoy) ? 'ACTIVA' : 'VENCIDA';

            $inscripcion->update([
                'estado'            => $nuevoEstado,
                'fecha_retorno'     => $fechaRetorno->toDateString(),
                'fecha_vencimiento' => $nuevaFechaVencimiento->toDateString(),
            ]);

            return $inscripcion->fresh();
        });
    }

    /**
     * Extender fecha_vencimiento al realizar un pago.
     */
    public function extenderVencimiento(Inscripcion $inscripcion, int $mesesPagados): Inscripcion
    {
        $vencimientoActual = Carbon::parse($inscripcion->fecha_vencimiento);
        $nuevaFecha = $vencimientoActual->addMonths($mesesPagados);

        $hoy = Carbon::today();
        $nuevoEstado = $nuevaFecha->greaterThanOrEqualTo($hoy) ? 'ACTIVA' : 'VENCIDA';

        $inscripcion->update([
            'fecha_vencimiento' => $nuevaFecha->toDateString(),
            'estado'            => $nuevoEstado,
        ]);

        return $inscripcion;
    }

    /**
     * Delete enrollment.
     */
    public function eliminar(int $id): void
    {
        DB::transaction(function () use ($id) {
            $inscripcion = Inscripcion::findOrFail($id);
            Mensualidad::where('inscripcion_id', $inscripcion->id)->delete();
            $inscripcion->delete();
        });
    }
}
