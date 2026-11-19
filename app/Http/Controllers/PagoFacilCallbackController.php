<?php

namespace App\Http\Controllers;

use App\Services\InscripcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagoFacilCallbackController extends Controller
{
    public function __construct(
        protected InscripcionService $inscripcionService
    ) {}

    /**
     * Recibe la notificación (Webhook Callback) desde PagoFácil MasterQR v2.
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('PagoFácil Webhook Callback recibido para Inscripción:', $request->all());

        $paymentNumber = $request->input('PedidoID')
            ?? $request->input('paymentNumber')
            ?? $request->input('companyTransactionId');

        $fecha = $request->input('Fecha');
        $hora  = $request->input('Hora');

        if (!$paymentNumber) {
            return response()->json([
                'error'   => 1,
                'status'  => 0,
                'message' => 'PedidoID o paymentNumber no proporcionado.',
                'values'  => false,
            ], 400);
        }

        try {
            $fechaPago = ($fecha && $hora) ? "{$fecha} {$hora}" : null;

            $confirmado = $this->inscripcionService->confirmarPagoQR((string) $paymentNumber, $fechaPago);

            if (!$confirmado) {
                Log::warning("PagoFácil Webhook: No se encontró inscripción con paymentNumber {$paymentNumber}");
            }

            // Respuesta estándar requerida por PagoFácil MasterQR
            return response()->json([
                'error'   => 0,
                'status'  => 1,
                'message' => 'Pago procesado correctamente por SCALA.',
                'values'  => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error procesando callback de PagoFácil: ' . $e->getMessage());

            return response()->json([
                'error'   => 1,
                'status'  => 0,
                'message' => 'Error interno al confirmar el pago.',
                'values'  => false,
            ], 500);
        }
    }
}
