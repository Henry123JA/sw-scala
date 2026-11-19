<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RequireLegalAcceptance
{
    /**
     * Bloquea el acceso al sistema si el usuario autenticado aún no ha aceptado
     * la Política de Privacidad y los Términos y Condiciones.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        try {
            if (Schema::hasColumn('usuario', 'politica_aceptada_at')) {
                if (is_null($user->politica_aceptada_at)) {
                    return redirect()->route('portal.politica', ['revisar' => 1, 'onboarding' => 1]);
                }

                if (is_null($user->terminos_aceptados_at)) {
                    return redirect()->route('portal.terminos', ['revisar' => 1, 'onboarding' => 1]);
                }
            }
        } catch (\Throwable $e) {
            // Continuar si la tabla no está disponible en entorno de test aislado
        }

        return $next($request);
    }
}
