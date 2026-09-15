<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\MenuService;
use App\Services\TemaService;
use Closure;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        protected MenuService $menuService,
        protected TemaService $temaService
    ) {}

    /**
     * Override handle to set view-accessible request attributes before share().
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $request->attributes->set('temaCategoria', $user?->tema?->nombre ?? 'adultos');

        return parent::handle($request, $next);
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? $user->only('id', 'nombres', 'apellidos', 'email', 'foto_url') : null,
                'rol' => $user?->rol?->nombre,
            ],
            'menuItems' => $user ? $this->menuService->getMenuPorRol($user->rol_id) : collect(),
            'temaActual' => $user?->tema?->nombre ?? 'adultos',
            'temasDisponibles' => $this->temaService->listarTemas()->map(fn($tema) => [
                'id'          => $tema->id,
                'nombre'      => $tema->nombre,
                'descripcion' => $tema->descripcion,
            ])->values(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'paginaContador' => fn () => $request->attributes->get('paginaContador'),
        ]);
    }
}
