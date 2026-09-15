<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Menu;
use App\Models\PaginaVisitada;
use Inertia\Inertia;

class PaginaVisitadaMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo registrar visitas para peticiones GET y usuarios autenticados
        if ($request->isMethod('GET')) {
            $user = $request->user();
            if ($user) {
                // Obtener la ruta con un slash al principio para que coincida con el MenuSeeder
                $path = '/' . ltrim($request->path(), '/');

                // Buscar el ítem de menú correspondiente a la ruta
                $menu = Menu::where('ruta', $path)->first();

                if ($menu) {
                    // Realizar el UPSERT en la tabla pagina_visitada
                    $paginaVisitada = PaginaVisitada::updateOrCreate(
                        [
                            'menu_id' => $menu->id,
                            'usuario_id' => $user->id,
                        ],
                        [
                            'ultima_visita' => now(),
                        ]
                    );

                    if (!$paginaVisitada->wasRecentlyCreated) {
                        $paginaVisitada->increment('contador');
                    }

                    // Compartir el contador actual en la petición y con Inertia
                    Inertia::share('paginaContador', $paginaVisitada->contador);
                    $request->attributes->set('paginaContador', $paginaVisitada->contador);
                }
            }
        }

        return $next($request);
    }
}
