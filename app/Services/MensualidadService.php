<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Inscripcion;
use App\Models\Mensualidad;
use App\Models\MetodoPago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MensualidadService
{
    protected PagoFacilService $pagoFacil;
    protected InscripcionService $inscripcionService;

    public function __construct(PagoFacilService $pagoFacil, InscripcionService $inscripcionService)
    {
        $this->pagoFacil = $pagoFacil;
        $this->inscripcionService = $inscripcionService;
    }

    /**
     * List mensualidades (solo pagos realizados y QR pendientes).
     */
    public function listar(array $filters = [])
    {
        $query = Mensualidad::query()
            ->with(['inscripcion.alumno.usuario', 'inscripcion.grupo.curso', 'metodoPago']);

        if (!empty($filters['buscar'])) {
            $buscar = $filters['buscar'];
            $query->whereHas('inscripcion.alumno.usuario', function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%");
            });
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['alumno_id'])) {
            $query->whereHas('inscripcion', function ($q) use ($filters) {
                $q->where('alumno_id', $filters['alumno_id']);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Get details of a single mensualidad.
     */
    public function obtenerPorId(int $id): Mensualidad
    {
        return Mensualidad::with(['inscripcion.alumno.usuario', 'inscripcion.grupo.curso', 'metodoPago'])->findOrFail($id);
    }

    /**
     * Process cash payment (Efectivo).
     * Crea UN registro y extiende fecha_vencimiento de la inscripción.
     *
     * @param int $id ID de la inscripción (no mensualidad)
     * @param array $data Incluye 'meses' (cuántos meses paga), 'observaciones'
     */
    public function iniciarPagoEfectivo(int $inscripcionId, array $data): Inscripcion
    {
        return DB::transaction(function () use ($inscripcionId, $data) {
            $inscripcion = Inscripcion::where('id', $inscripcionId)->lockForUpdate()->firstOrFail();
            $meses = max(1, (int) ($data['meses'] ?? 1));

            $montoTotal = $inscripcion->monto_mensual * $meses;

            // Get payment method
            $metodo = MetodoPago::where('id', 1)->orWhere('nombre', 'like', 'Efectivo')->first();
            $metodoId = $metodo ? $metodo->id : 1;

            $recibo = $this->generarCorrelativoRecibo();

            // Crear un registro de pago
            Mensualidad::create([
                'inscripcion_id'  => $inscripcion->id,
                'metodo_pago_id'  => $metodoId,
                'numero_mes'      => $this->siguienteNumeroMes($inscripcion),
                'meses_pagados'   => $meses,
                'monto_base'      => $montoTotal,
                'fecha_pago'      => now()->toDateString(),
                'fecha_vencimiento'=> now()->toDateString(),
                'estado'          => 'PAGADO',
                'tipo_comprobante'=> 'EFECTIVO',
                'numero_recibo'   => $recibo,
                'observaciones'   => $data['observaciones'] ?? null,
            ]);

            // Extender vencimiento
            $this->inscripcionService->extenderVencimiento($inscripcion, $meses);

            return $inscripcion->fresh();
        });
    }

    /**
     * Start QR payment process.
     * Crea un registro PENDIENTE_PAGO_QR y genera el QR con PagoFácil.
     *
     * @param int $inscripcionId ID de la inscripción
     * @param array $data Incluye 'meses' (cuántos meses paga)
     */
    public function iniciarPagoQR(int $inscripcionId, array $data): Mensualidad
    {
        return DB::transaction(function () use ($inscripcionId, $data) {
            $inscripcion = Inscripcion::where('id', $inscripcionId)->lockForUpdate()->firstOrFail();
            $meses = max(1, (int) ($data['meses'] ?? 1));
            $montoTotal = $inscripcion->monto_mensual * $meses;

            // Buscar QR activo existente para esta inscripción
            $existente = Mensualidad::where('inscripcion_id', $inscripcion->id)
                ->where('estado', 'PENDIENTE_PAGO_QR')
                ->where('pagofacil_qr_expira', '>', now())
                ->whereNotNull('pagofacil_qr_base64')
                ->first();

            if ($existente) {
                return $existente;
            }

            // Si hay QR expirados sin pagar, limpiarlos
            Mensualidad::where('inscripcion_id', $inscripcion->id)
                ->where('estado', 'PENDIENTE_PAGO_QR')
                ->where(function ($q) {
                    $q->whereNull('pagofacil_qr_expira')
                      ->orWhere('pagofacil_qr_expira', '<=', now());
                })
                ->delete();

            $paymentNumber = 'PAG-' . strtoupper(Str::random(10));
            $concepto = "Pago de " . $meses . " mensualidad(es) - " . $inscripcion->grupo->curso->nombre;
            $usuario = $inscripcion->alumno->usuario;

            $qrData = $this->pagoFacil->generarQR(
                $paymentNumber,
                $montoTotal,
                $concepto,
                $usuario->email,
                $usuario->nombres . ' ' . $usuario->apellidos,
                $usuario->telefono ?? '',
                $usuario->ci ?? '',
                (int) $inscripcion->alumno_id
            );

            $mensualidad = Mensualidad::create([
                'inscripcion_id'           => $inscripcion->id,
                'numero_mes'               => $this->siguienteNumeroMes($inscripcion),
                'meses_pagados'            => $meses,
                'monto_base'               => $montoTotal,
                'estado'                   => 'PENDIENTE_PAGO_QR',
                'pagofacil_transaction_id' => $qrData['transactionId'],
                'pagofacil_payment_number' => $paymentNumber,
                'pagofacil_qr_base64'      => $qrData['qrBase64'],
                'pagofacil_qr_expira'      => $qrData['expirationDate'],
            ]);

            return $mensualidad;
        });
    }

    /**
     * Confirm QR payment (from webhook or polling).
     */
    public function confirmarPagoQR(string $paymentNumber, ?string $fechaPago = null): bool
    {
        return DB::transaction(function () use ($paymentNumber, $fechaPago) {
            $mensualidad = Mensualidad::where('pagofacil_payment_number', $paymentNumber)
                ->lockForUpdate()
                ->first();

            if (!$mensualidad) {
                Log::warning("confirmarPagoQR: Mensualidad matching payment number '{$paymentNumber}' not found.");
                return false;
            }

            // Idempotencia
            if ($mensualidad->estado === 'PAGADO') {
                return true;
            }

            if ($mensualidad->estado !== 'PENDIENTE_PAGO_QR') {
                Log::warning("confirmarPagoQR: Mensualidad '{$mensualidad->id}' tiene estado '{$mensualidad->estado}'.");
                return false;
            }

            $metodo = MetodoPago::where('id', 2)->orWhere('nombre', 'like', 'QR')->first();
            $metodoId = $metodo ? $metodo->id : 2;

            $recibo = $this->generarCorrelativoRecibo();
            $payDate = $fechaPago ? Carbon::parse($fechaPago)->toDateString() : now()->toDateString();

            $mensualidad->update([
                'estado'           => 'PAGADO',
                'fecha_pago'       => $payDate,
                'fecha_vencimiento'=> $payDate,
                'metodo_pago_id'   => $metodoId,
                'tipo_comprobante' => 'QR',
                'numero_recibo'    => $recibo,
            ]);

            // Extender vencimiento de la inscripción
            $inscripcion = Inscripcion::find($mensualidad->inscripcion_id);
            if ($inscripcion) {
                $this->inscripcionService->extenderVencimiento($inscripcion, $mensualidad->meses_pagados);
            }

            // Bitácora
            try {
                if (class_exists(\App\Services\BitacoraService::class)) {
                    $bitacoraService = app(\App\Services\BitacoraService::class);
                    $bitacoraService->registrar(
                        null,
                        "CONFIRMACION PAGO QR: Inscripcion #{$mensualidad->inscripcion_id} - Recibo {$recibo}",
                        "mensualidades/{$mensualidad->id}",
                        "WEBHOOK",
                        "EXITO",
                        request()->ip() ?? '127.0.0.1'
                    );
                }
            } catch (\Exception $e) {
                Log::error('confirmarPagoQR Bitacora logging failed: ' . $e->getMessage());
            }

            return true;
        });
    }

    /**
     * Revert expired QR payments back — elimina el registro PENDIENTE_PAGO_QR expirado.
     */
    public function revertirQRExpirado(Mensualidad $mensualidad): void
    {
        $mensualidad->delete();
    }

    /**
     * Helper: calcular el siguiente numero_mes a asignar según los pagos ya realizados.
     */
    private function siguienteNumeroMes(Inscripcion $inscripcion): int
    {
        $ultimo = Mensualidad::where('inscripcion_id', $inscripcion->id)
            ->where('estado', 'PAGADO')
            ->max('numero_mes');

        return ($ultimo ?? 0) + 1;
    }

    /**
     * Helper: generar número de recibo correlativo.
     */
    private function generarCorrelativoRecibo(): string
    {
        $prefix = 'REC-' . now()->format('Ym');
        $count = DB::table('mensualidad')->whereNotNull('numero_recibo')->count();
        $next = $count + 1;

        while (true) {
            $candidate = $prefix . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            if (!DB::table('mensualidad')->where('numero_recibo', $candidate)->exists()) {
                return $candidate;
            }
            $next++;
        }
    }
}
