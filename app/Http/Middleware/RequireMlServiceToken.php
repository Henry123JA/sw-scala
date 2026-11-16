<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMlServiceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.ml.integration_token', '');
        $provided = $request->bearerToken();
        // Fail closed: una sesion de navegador no sustituye la credencial de servicio.
        if ($expected === '') {
            return response()->json(['message' => 'Integracion ML no configurada.'], 503);
        }
        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Credencial de servicio requerida.'], 401,
                ['WWW-Authenticate' => 'Bearer']);
        }
        return $next($request);
    }
}
