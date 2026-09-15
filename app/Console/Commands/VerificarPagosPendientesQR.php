<?php

namespace App\Console\Commands;

use App\Models\Mensualidad;
use App\Services\MensualidadService;
use App\Services\PagoFacilService;
use App\Services\BitacoraService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerificarPagosPendientesQR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pagofacil:verificar-pendientes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies pending QR payments against PagoFácil and handles expirations';

    protected MensualidadService $mensualidadService;
    protected PagoFacilService $pagoFacilService;

    public function __construct(MensualidadService $mensualidadService, PagoFacilService $pagoFacilService)
    {
        parent::__construct();
        $this->mensualidadService = $mensualidadService;
        $this->pagoFacilService = $pagoFacilService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting verification of pending QR payments...');

        $pendientes = Mensualidad::where('estado', 'PENDIENTE_PAGO_QR')
            // No consultar QR recién generados — esperar al menos 30s para no llamar en vano
            ->where('created_at', '<=', now()->subSeconds(30))
            ->get();

        foreach ($pendientes as $mensualidad) {
            $this->info("Processing Mensualidad ID: {$mensualidad->id} (Payment Number: {$mensualidad->pagofacil_payment_number})");

            // 1. Expiration check
            if ($mensualidad->pagofacil_qr_expira && $mensualidad->pagofacil_qr_expira->isPast()) {
                $this->info("QR is expired. Reverting monthly payment to unpaid state.");
                $this->mensualidadService->revertirQRExpirado($mensualidad);
                $this->registrarBitacora(null, 'QR expirado sin confirmar', "mensualidades/{$mensualidad->id}", 'SCHEDULER', 'EXITO');
                continue;
            }

            // 2. Poll transaction from PagoFácil if transaction ID is present
            if ($mensualidad->pagofacil_transaction_id) {
                try {
                    $response = $this->pagoFacilService->queryTransaction($mensualidad->pagofacil_transaction_id);
                    
                    $values = $response['values'] ?? null;
                    if (is_string($values)) {
                        $values = json_decode($values, true);
                    }

                    $isPaid = false;
                    
                    if (isset($response['error']) && $response['error'] === 0) {
                        $statusDesc = $values['paymentStatusDescription'] ?? $values['status'] ?? '';
                        $statusCode = $values['paymentStatus'] ?? 0;
                        // Status 2 = Pagado/Completado
                        if ($statusCode === 2 || stripos($statusDesc, 'PAGADO') !== false || stripos($statusDesc, 'COMPLETED') !== false || stripos($statusDesc, 'SUCCESS') !== false) {
                            $isPaid = true;
                        }
                    }

                    if ($isPaid) {
                        $this->info("Transaction confirmed paid via query API. Setting state=PAGADO.");
                        $fechaPago = $values['FechaPago'] ?? $values['Fecha'] ?? now()->toDateTimeString();
                        $this->mensualidadService->confirmarPagoQR($mensualidad->pagofacil_payment_number, $fechaPago);
                        $this->registrarBitacora(null, 'Pago QR confirmado por polling', "mensualidades/{$mensualidad->id}", 'SCHEDULER', 'EXITO');
                    } else {
                        $this->info("Transaction not completed yet. Current status: " . json_encode($values));
                    }
                } catch (\Exception $e) {
                    $this->error("Error querying transaction for Monthly Payment {$mensualidad->id}: " . $e->getMessage());
                }
            }
        }

        $this->info('Completed verifying pending QR payments.');
    }

    /**
     * Register a bitacora entry safely.
     */
    private function registrarBitacora(?int $usuarioId, string $accion, string $recurso, string $metodo, string $estado): void
    {
        try {
            if (class_exists(BitacoraService::class)) {
                app(BitacoraService::class)->registrar(
                    $usuarioId,
                    $accion,
                    $recurso,
                    $metodo,
                    $estado,
                    '127.0.0.1'
                );
            }
        } catch (\Exception $e) {
            Log::error('Bitacora logging failed in VerificarPagosPendientesQR: ' . $e->getMessage());
        }
    }
}
