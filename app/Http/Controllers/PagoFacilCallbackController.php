<?php

namespace App\Http\Controllers;

use App\Services\MensualidadService;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagoFacilCallbackController extends Controller
{
    protected MensualidadService $mensualidadService;

    public function __construct(MensualidadService $mensualidadService)
    {
        $this->mensualidadService = $mensualidadService;
    }

    /**
     * Webhook endpoint callback from PagoFácil.
     * Must return the strict response: {"error": 0, "status": 1, "message": "OK", "values": true}
     */
    public function handle(Request $request)
    {
        $paymentNumber = $request->input('PedidoID');
        $fechaPago = $request->input('FechaPago') ?? $request->input('Fecha') ?? now()->toDateTimeString();

        Log::info('PagoFácil Webhook Callback received.', $request->all());

        // Register bitacora event: Callback PagoFácil recibido
        $this->registrarBitacora(null, 'Callback PagoFácil recibido', "pagofacil/callback/{$paymentNumber}", 'WEBHOOK', 'EXITO', $request->ip());

        if ($paymentNumber) {
            try {
                $confirmed = $this->mensualidadService->confirmarPagoQR($paymentNumber, $fechaPago);
                if (!$confirmed) {
                    $this->registrarBitacora(null, 'Callback PagoFácil recibido sin cambios', "pagofacil/callback/{$paymentNumber}", 'WEBHOOK', 'FALLO', $request->ip());
                }
            } catch (\Exception $e) {
                Log::error('PagoFácil Webhook confirmation failed: ' . $e->getMessage(), ['paymentNumber' => $paymentNumber]);
                $this->registrarBitacora(null, 'Callback PagoFácil recibido con error', "pagofacil/callback/{$paymentNumber}", 'WEBHOOK', 'FALLO', $request->ip());
            }
        } else {
            Log::warning('PagoFácil Webhook Callback missing PedidoID parameter.', $request->all());
            $this->registrarBitacora(null, 'Callback PagoFácil recibido sin PedidoID', 'pagofacil/callback', 'WEBHOOK', 'FALLO', $request->ip());
        }

        return response()->json([
            'error'   => 0,
            'status'  => 1,
            'message' => 'OK',
            'values'  => true,
        ]);
    }

    /**
     * Register a bitacora entry safely.
     */
    private function registrarBitacora(?int $usuarioId, string $accion, string $recurso, string $metodo, string $estado, ?string $ip): void
    {
        try {
            if (class_exists(BitacoraService::class)) {
                app(BitacoraService::class)->registrar(
                    $usuarioId,
                    $accion,
                    $recurso,
                    $metodo,
                    $estado,
                    $ip ?? '127.0.0.1'
                );
            }
        } catch (\Exception $e) {
            Log::error('Bitacora logging failed in PagoFacilCallback: ' . $e->getMessage());
        }
    }
}
