<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Inscripcion;
use App\Models\Grupo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InscripcionService
{
    /**
     * List enrollments with search and filters.
     */
    public function listar(string $buscar = '', ?string $estado = null, ?int $alumnoId = null, ?int $docenteId = null): LengthAwarePaginator
    {
        return Inscripcion::query()
            ->with(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario'])
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($sub) use ($buscar) {
                    $sub->whereHas('alumno.usuario', function ($q) use ($buscar) {
                        $q->where('nombres', 'like', "%{$buscar}%")
                          ->orWhere('apellidos', 'like', "%{$buscar}%");
                    })->orWhereHas('grupo.curso', function ($q) use ($buscar) {
                        $q->where('nombre', 'like', "%{$buscar}%");
                    });
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
        return Inscripcion::with(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario'])->findOrFail($id);
    }

    /**
     * Create enrollment.
     * Solo considera ocupantes a inscripciones ACTIVA y PAUSADA.
     */
    public function crear(array $data): Inscripcion
    {
        return DB::transaction(function () use ($data) {
            $grupo = Grupo::findOrFail($data['grupo_id']);

            // 1. Capacidad del grupo: ocupantes únicamente ACTIVA y PAUSADA
            $activeCount = Inscripcion::where('grupo_id', $grupo->id)
                ->whereIn('estado', ['ACTIVA', 'PAUSADA'])
                ->count();

            if ($activeCount >= $grupo->capacidad_maxima) {
                throw new BusinessException('El grupo ha alcanzado su capacidad máxima.', 'grupo_id');
            }

            // 2. Prevención de duplicados: únicamente si ya tiene inscripción ACTIVA o PAUSADA
            $exists = Inscripcion::where('grupo_id', $grupo->id)
                ->where('alumno_id', $data['alumno_id'])
                ->whereIn('estado', ['ACTIVA', 'PAUSADA'])
                ->exists();

            if ($exists) {
                throw new BusinessException('El alumno ya tiene una inscripción activa o pausada en este grupo.', 'alumno_id');
            }

            $fechaInicio = $data['fecha_inicio_clases'];

            return Inscripcion::create([
                'alumno_id'           => $data['alumno_id'],
                'grupo_id'            => $data['grupo_id'],
                'fecha'               => $data['fecha'] ?? now()->toDateString(),
                'fecha_inicio_clases' => $fechaInicio,
                'estado'              => 'ACTIVA',
                'observaciones'       => $data['observaciones'] ?? null,
                'monto_inscripcion'   => $data['monto_inscripcion'] ?? 150.00,
                'estado_pago'         => 'PENDIENTE',
            ]);
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
            'monto_inscripcion'   => $data['monto_inscripcion'] ?? null,
        ], fn($v) => $v !== null));

        return $inscripcion->fresh();
    }

    /**
     * Pause enrollment (suspensión académica temporal).
     */
    public function pausar(int $id, array $data): Inscripcion
    {
        return DB::transaction(function () use ($id, $data) {
            $inscripcion = Inscripcion::findOrFail($id);

            if ($inscripcion->estado !== 'ACTIVA') {
                throw new BusinessException('Solo se pueden pausar inscripciones activas.', 'estado');
            }

            $fechaPausa = $data['fecha_pausa'] ?? now()->toDateString();

            $inscripcion->update([
                'estado'      => 'PAUSADA',
                'fecha_pausa' => $fechaPausa,
            ]);

            return $inscripcion->fresh();
        });
    }

    /**
     * Resume enrollment (reactivación académica).
     */
    public function reanudar(int $id, array $data): Inscripcion
    {
        return DB::transaction(function () use ($id, $data) {
            $inscripcion = Inscripcion::findOrFail($id);

            if ($inscripcion->estado !== 'PAUSADA') {
                throw new BusinessException('Solo se pueden reanudar inscripciones pausadas.', 'estado');
            }

            $fechaRetorno = $data['fecha_retorno'] ?? now()->toDateString();

            $inscripcion->update([
                'estado'        => 'ACTIVA',
                'fecha_retorno' => $fechaRetorno,
            ]);

            return $inscripcion->fresh();
        });
    }

    /**
     * Cancel / withdraw enrollment.
     * Registra fecha_retiro, cambia a CANCELADA y preserva el registro en el historial.
     */
    public function cancelar(int $id, array $data = []): Inscripcion
    {
        return DB::transaction(function () use ($id, $data) {
            $inscripcion = Inscripcion::findOrFail($id);

            if ($inscripcion->estado === 'CANCELADA') {
                throw new BusinessException('La inscripción ya se encuentra cancelada.', 'estado');
            }

            $inscripcion->update([
                'estado'       => 'CANCELADA',
                'fecha_retiro' => $data['fecha_retiro'] ?? now()->toDateString(),
            ]);

            return $inscripcion->fresh();
        });
    }

    /**
     * Delete enrollment adapter — conserva el registro como historial realizando retiro lógico.
     */
    public function eliminar(int $id): void
    {
        $this->cancelar($id, ['fecha_retiro' => now()->toDateString()]);
    }

    /**
     * Registrar pago en efectivo de la inscripción (Propietario / Secretaria).
     */
    public function iniciarPagoEfectivo(int $inscripcionId, array $data = []): Inscripcion
    {
        return DB::transaction(function () use ($inscripcionId, $data) {
            $inscripcion = Inscripcion::with(['alumno.usuario', 'grupo.curso'])->findOrFail($inscripcionId);

            if ($inscripcion->estado_pago === 'PAGADO') {
                throw new BusinessException('Esta inscripción ya se encuentra pagada.', 'general');
            }

            $monto = isset($data['monto_inscripcion']) && (float) $data['monto_inscripcion'] > 0
                ? (float) $data['monto_inscripcion']
                : ((float) ($inscripcion->monto_inscripcion ?: 150.00));

            $recibo = $this->generarCorrelativoRecibo();

            $inscripcion->update([
                'monto_inscripcion' => $monto,
                'estado_pago'       => 'PAGADO',
                'metodo_pago'       => 'EFECTIVO',
                'fecha_pago'        => now(),
                'numero_recibo'     => $recibo,
            ]);

            return $inscripcion->fresh(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario']);
        });
    }

    /**
     * Iniciar o reutilizar QR de PagoFácil para el pago de la inscripción.
     */
    public function iniciarPagoQR(int $inscripcionId, array $data = []): Inscripcion
    {
        return DB::transaction(function () use ($inscripcionId, $data) {
            $inscripcion = Inscripcion::with(['alumno.usuario', 'grupo.curso'])->findOrFail($inscripcionId);

            if ($inscripcion->estado_pago === 'PAGADO') {
                throw new BusinessException('Esta inscripción ya se encuentra pagada.', 'general');
            }

            $montoNuevo = isset($data['monto_inscripcion']) && (float) $data['monto_inscripcion'] > 0
                ? (float) $data['monto_inscripcion']
                : ((float) ($inscripcion->monto_inscripcion ?: 150.00));

            $forzarRegenerar = !empty($data['regenerar']) || ((float) $inscripcion->monto_inscripcion !== $montoNuevo);

            // Reutilizar QR vigente si aún no expiró y no se pidió regenerar
            if (
                !$forzarRegenerar
                && $inscripcion->estado_pago === 'PENDIENTE_QR'
                && $inscripcion->pagofacil_qr_expira
                && $inscripcion->pagofacil_qr_expira > now()
                && !empty($inscripcion->pagofacil_qr_base64)
            ) {
                return $inscripcion;
            }

            $usuario = $inscripcion->alumno?->usuario;
            if (!$usuario) {
                throw new BusinessException('No se encontraron los datos del alumno para generar el QR.', 'general');
            }

            $cursoNombre = $inscripcion->grupo?->curso?->nombre ?? 'Curso Musical';
            $grupoNombre = $inscripcion->grupo?->codigo_grupo ?? $inscripcion->grupo?->nombre ?? 'Grupo';
            $paymentNumber = 'SCALA-INS-' . $inscripcion->id . '-' . time();

            /** @var PagoFacilService $pagoFacil */
            $pagoFacil = app(PagoFacilService::class);

            $qrData = $pagoFacil->generarQR(
                paymentNumber: $paymentNumber,
                monto: $montoNuevo,
                concept: "Inscripción SCALA - {$cursoNombre} ({$grupoNombre})",
                email: (string) ($usuario->email ?: $usuario->correo ?: 'pagos@techmusic.work'),
                clientName: trim($usuario->nombres . ' ' . $usuario->apellidos),
                phone: (string) ($usuario->telefono ?: '70000000'),
                documentId: (string) ($usuario->ci ?: '0000000'),
                clientCode: (int) $usuario->id
            );

            $inscripcion->update([
                'monto_inscripcion'        => $montoNuevo,
                'estado_pago'              => 'PENDIENTE_QR',
                'metodo_pago'              => 'QR',
                'pagofacil_transaction_id' => $qrData['transactionId'],
                'pagofacil_payment_number' => $paymentNumber,
                'pagofacil_qr_base64'      => $qrData['qrBase64'],
                'pagofacil_qr_expira'      => $qrData['expirationDate'],
            ]);

            return $inscripcion->fresh(['alumno.usuario', 'grupo.curso', 'grupo.docente.usuario']);
        });
    }

    /**
     * Confirmar el pago QR de una inscripción (vía Callback de PagoFácil o consulta activa).
     */
    public function confirmarPagoQR(string $paymentNumber, ?string $fechaPago = null): bool
    {
        return DB::transaction(function () use ($paymentNumber, $fechaPago) {
            $inscripcion = Inscripcion::where('pagofacil_payment_number', $paymentNumber)
                ->lockForUpdate()
                ->first();

            if (!$inscripcion) {
                return false;
            }

            if ($inscripcion->estado_pago === 'PAGADO') {
                return true;
            }

            $recibo = $this->generarCorrelativoRecibo();

            $inscripcion->update([
                'estado_pago'   => 'PAGADO',
                'metodo_pago'   => 'QR',
                'fecha_pago'    => $fechaPago ? Carbon::parse($fechaPago) : now(),
                'numero_recibo' => $recibo,
            ]);

            return true;
        });
    }

    /**
     * Verificar estado de pago QR para el polling desde el modal Vue.
     */
    public function verificarEstadoQR(string $paymentNumber): array
    {
        $inscripcion = Inscripcion::where('pagofacil_payment_number', $paymentNumber)->first();

        if (!$inscripcion) {
            return [
                'estado' => 'NO_ENCONTRADO',
                'recibo' => null,
            ];
        }

        // Si sigue PENDIENTE_QR y tenemos transaction_id, consultamos a PagoFácil por si el callback aún no llegó
        if ($inscripcion->estado_pago === 'PENDIENTE_QR' && !empty($inscripcion->pagofacil_transaction_id)) {
            try {
                /** @var PagoFacilService $pagoFacil */
                $pagoFacil = app(PagoFacilService::class);
                $txResponse = $pagoFacil->queryTransaction((string) $inscripcion->pagofacil_transaction_id);
                $txValues = $txResponse['values'] ?? $txResponse;
                if (is_string($txValues)) {
                    $txValues = json_decode($txValues, true) ?: [];
                }

                $paymentStatus = $txValues['paymentStatus'] ?? $txValues['status'] ?? null;
                // En PagoFácil MasterQR v2, paymentStatus == 2 o 5 indica pagado/completado
                if (in_array((int) $paymentStatus, [2, 5], true)) {
                    $this->confirmarPagoQR($paymentNumber);
                    $inscripcion->refresh();
                }
            } catch (\Throwable $e) {
                // Ignorar error de red en polling y devolver estado local
            }
        }

        return [
            'estado'     => $inscripcion->estado_pago,
            'recibo'     => $inscripcion->numero_recibo,
            'fecha_pago' => $inscripcion->fecha_pago?->toIso8601String(),
        ];
    }

    /**
     * Genera un correlativo único de recibo para inscripciones: REC-INS-YYYY-0001
     */
    private function generarCorrelativoRecibo(): string
    {
        $year = date('Y');
        $prefix = "REC-INS-{$year}-";

        $lastInscripcion = Inscripcion::where('numero_recibo', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastInscripcion || !$lastInscripcion->numero_recibo) {
            return $prefix . '0001';
        }

        $lastNumber = (int) str_replace($prefix, '', $lastInscripcion->numero_recibo);
        $nextNumber = $lastNumber + 1;

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
