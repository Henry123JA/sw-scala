<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\BitacoraService;

class CheckRole
{
    public function __construct(protected BitacoraService $bitacoraService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->rol?->nombre, $roles)) {
            // Acceso denegado. Registrar en bitácora
            $this->bitacoraService->registrar(
                usuarioId: $user?->id,
                accion: 'Acceso denegado a ' . $request->path(),
                recurso: $request->path(),
                metodo: $request->method(),
                estado: 'FALLO',
                ip: $request->ip()
            );

            abort(403, 'No autorizado.');
        }

        return $next($request);
    }
}
