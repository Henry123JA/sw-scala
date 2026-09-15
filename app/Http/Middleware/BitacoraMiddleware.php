<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\BitacoraService;

class BitacoraMiddleware
{
    public function __construct(protected BitacoraService $bitacoraService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Registrar acceso solo para usuarios autenticados
        $user = $request->user();
        if ($user) {
            $estado = ($response->getStatusCode() >= 400) ? 'FALLO' : 'EXITO';
            $path = $request->path();

            // Evitar duplicar registros para login y logout que se manejan en AuthService
            if ($path !== 'login' && $path !== 'logout') {
                $this->bitacoraService->registrar(
                    usuarioId: $user->id,
                    accion: $this->getAccion($request),
                    recurso: $path,
                    metodo: $request->method(),
                    estado: $estado,
                    ip: $request->ip()
                );
            }
        }

        return $response;
    }

    /**
     * Genera un texto descriptivo de la acción según el método HTTP.
     */
    protected function getAccion(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        switch (strtoupper($method)) {
            case 'GET':
                return 'Acceso a ' . $path;
            case 'POST':
                return 'Creación en ' . $path;
            case 'PUT':
            case 'PATCH':
                return 'Actualización en ' . $path;
            case 'DELETE':
                return 'Eliminación en ' . $path;
            default:
                return $method . ' en ' . $path;
        }
    }
}
