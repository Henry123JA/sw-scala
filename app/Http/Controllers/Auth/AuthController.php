<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Muestra la vista de inicio de sesión.
     */
    public function showLogin(): InertiaResponse
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Procesa la solicitud de inicio de sesión.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $this->authService->login($request->validated());

        // Regenerar sesión para mitigar ataques de fijación de sesión
        $request->session()->regenerate();

        $user = $request->user();

        // Si es el primer inicio de sesión (aún no aceptó Política de Privacidad o Términos)
        if ($user && ($request->boolean('primer_ingreso') || is_null($user->politica_aceptada_at))) {
            return redirect()->route('portal.politica', ['revisar' => 1, 'onboarding' => 1]);
        }

        if ($user && is_null($user->terminos_aceptados_at)) {
            return redirect()->route('portal.terminos', ['revisar' => 1, 'onboarding' => 1]);
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('login');
    }
}
