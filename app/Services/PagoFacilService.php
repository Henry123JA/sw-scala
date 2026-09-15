<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PagoFacilService
{
    protected string $baseUrl;
    protected string $tokenService;
    protected string $tokenSecret;
    protected string $paymentMethodId;
    protected string $callbackUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.pagofacil.base_url');
        $this->tokenService = config('services.pagofacil.token_service');
        $this->tokenSecret = config('services.pagofacil.token_secret');
        $this->paymentMethodId = (string) config('services.pagofacil.payment_method_id', '34');
        $configuredUrl = config('services.pagofacil.callback_url', '');
        // Si la URL contiene localhost/127.0.0.1 PagoFácil la rechaza,
        // así que usamos un placeholder que la API acepta.
        if (!$configuredUrl || str_contains($configuredUrl, 'localhost') || str_contains($configuredUrl, '127.0.0.1')) {
            $configuredUrl = 'https://masterqr.pagofacil.com.bo/callback/placeholder';
        }
        $this->callbackUrl = $configuredUrl;
    }

    /**
     * Get or refresh Auth Token with PagoFácil.
     */
    public function getAccessToken(): string
    {
        return Cache::remember('pagofacil_access_token', 600, function () {
            $response = Http::withHeaders([
                'tcTokenService' => $this->tokenService,
                'tcTokenSecret'  => $this->tokenSecret,
            ])->post("{$this->baseUrl}/login");

            if ($response->failed() || empty($response->json('values.accessToken'))) {
                Log::error('PagoFácil Auth Failed', ['response' => $response->body()]);
                throw new \RuntimeException('Failed to authenticate with PagoFácil');
            }

            return $response->json('values.accessToken');
        });
    }

    /**
     * Generate QR Base64 code for a payment transaction.
     */
    public function generarQR(
        string $paymentNumber,
        float $monto,
        string $concept,
        string $email,
        string $clientName,
        string $phone,
        string $documentId = '',
        int $clientCode = 0
    ): array {
        $token = $this->getAccessToken();

        $montoFinal = $monto / 10000; // Dividido entre 10000 para pruebas — montos pequeños

        $payload = [
            'paymentMethod'  => $this->paymentMethodId,
            'clientName'     => $clientName,
            'documentType'   => 1,
            'documentId'     => $documentId ?: '0000000',
            'phoneNumber'    => $phone ?: '00000000',
            'email'          => $email ?: 'sin-correo@academia.com',
            'paymentNumber'  => $paymentNumber,
            'amount'         => number_format($montoFinal, 2, '.', ''),
            'currency'       => 2,
            'clientCode'     => $clientCode,
            'callbackUrl'    => $this->callbackUrl,
            'orderDetail'    => [
                [
                    'serial'   => 1,
                    'product'  => $concept,
                    'quantity' => 1,
                    'price'    => $monto,
                    'discount' => 0,
                    'total'    => $monto,
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/generate-qr", $payload);

        if ($response->failed() || $response->json('error') !== 0) {
            Log::error('PagoFácil QR Generation Failed', ['response' => $response->body(), 'payload' => $payload]);
            throw new \RuntimeException('Failed to generate QR via PagoFácil: ' . ($response->json('message') ?? 'Unknown error'));
        }

        $values = $response->json('values');
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            if ($decoded) {
                $values = $decoded;
            }
        }

        $qrCode = $values['qrBase64'] ?? null;
        $transactionId = $values['transactionId'] ?? null;

        $expString = $values['expirationDate'] ?? null;
        if ($expString) {
            // PagoFácil envía la fecha de expiración en hora local de Bolivia
            // (America/La_Paz, UTC-4) sin indicador de zona horaria. La app corre
            // en UTC, así que interpretamos la cadena en su tz original y luego
            // convertimos a UTC; de lo contrario Carbon la leería 4 h en el pasado
            // y el modal mostraría "Expirado" apenas se genera el QR.
            // Si la cadena trajera offset/Z explícito, Carbon lo respeta y el
            // segundo argumento se ignora, así que es safe en ambos casos.
            $expDate = \Carbon\Carbon::parse($expString, 'America/La_Paz')->setTimezone('UTC');
            Log::info('PagoFácil QR expirationDate parsed', [
                'raw'       => $expString,
                'parsed_utc' => $expDate->toIso8601String(),
            ]);
        } else {
            $expDate = now()->addMinutes(15);
        }

        return [
            'transactionId'  => $transactionId,
            'qrBase64'       => $qrCode,
            'expirationDate' => $expDate,
        ];
    }

    /**
     * Query transaction status.
     */
    public function queryTransaction(string $transactionId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'tcTokenSecret' => $this->tokenSecret,
        ])->post("{$this->baseUrl}/query-transaction", [
            'tnTransaccionPF' => (int) $transactionId,
        ]);

        if ($response->failed()) {
            Log::error('PagoFácil Query Transaction Failed', ['transactionId' => $transactionId, 'response' => $response->body()]);
            return ['success' => false, 'message' => 'HTTP request failed'];
        }

        return $response->json();
    }
}
