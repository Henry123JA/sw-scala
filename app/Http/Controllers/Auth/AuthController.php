<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\CodigoTemporalPasswordMail;
use App\Models\Alumno;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AuthService;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        return Inertia::render('Auth/Login', [
            'google_enabled' => !empty(config('services.google.client_id')),
        ]);
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

        // Si marcó la casilla "Primer inicio de sesión", reiniciamos su aceptación legal para que pase por el flujo completo
        if ($user && $request->boolean('primer_ingreso')) {
            try {
                if (Schema::hasColumn('usuario', 'politica_aceptada_at')) {
                    $user->update([
                        'politica_aceptada_at'  => null,
                        'terminos_aceptados_at' => null,
                    ]);
                }
            } catch (\Throwable $e) {
                // Continuar
            }
            $request->session()->forget(['politica_aceptada', 'terminos_aceptados']);
        }

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
     * Redirige al selector de cuentas de Google OAuth 2.0.
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $clientId = trim((string) config('services.google.client_id'));
        if (empty($clientId)) {
            return redirect()->route('login')->withErrors([
                'general' => 'La autenticación con Google no está configurada en el servidor.',
            ]);
        }

        $redirectUri = $this->resolveGoogleRedirectUri($request);
        $state = Str::random(32);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
            'state'         => $state,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Recibe el callback OAuth 2.0 de Google, autentica o registra al usuario y verifica aceptación legal.
     */
    public function handleGoogleCallback(Request $request, BitacoraService $bitacoraService): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'general' => 'Se canceló el inicio de sesión con Google.',
            ]);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('login')->withErrors([
                'general' => 'Código de autorización de Google no recibido.',
            ]);
        }

        $clientId     = trim((string) config('services.google.client_id'));
        $clientSecret = trim((string) config('services.google.client_secret'));
        $redirectUri  = $this->resolveGoogleRedirectUri($request);

        try {
            $tokenResponse = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
                'code'          => $code,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code',
            ]);

            if ($tokenResponse->failed() || empty($tokenResponse->json('access_token'))) {
                Log::error('Google OAuth Token Error', ['body' => $tokenResponse->body()]);
                return redirect()->route('login')->withErrors([
                    'general' => 'No se pudo validar la sesión con Google. Verifica que la URI de redirección esté autorizada en Google Cloud.',
                ]);
            }

            $accessToken = $tokenResponse->json('access_token');

            $userResponse = Http::withoutVerifying()->withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if ($userResponse->failed() || empty($userResponse->json('email'))) {
                return redirect()->route('login')->withErrors([
                    'general' => 'No se pudo obtener el correo electrónico desde la cuenta de Google.',
                ]);
            }

            $googleUser = $userResponse->json();
            $email      = strtolower(trim((string) $googleUser['email']));

            // Buscar únicamente usuarios previamente registrados en la Academia SCALA
            $usuario = Usuario::whereRaw('LOWER(email) = ?', [$email])->first();

            if (!$usuario) {
                $bitacoraService->registrar(
                    usuarioId: null,
                    accion: "Intento de inicio de sesión con Google rechazado (correo no registrado: {$email})",
                    recurso: 'login/google',
                    metodo: 'GET',
                    estado: 'FALLO',
                    ip: $request->ip()
                );

                return redirect()->route('login')->withErrors([
                    'general' => "La cuenta de Google ({$email}) no se encuentra registrada en el sistema. Recuerda que todo registro en la Academia SCALA se realiza de forma presencial en Secretaría presentando la documentación correspondiente.",
                ]);
            }

            Auth::guard('web')->login($usuario, true);
            $request->session()->regenerate();

            $bitacoraService->registrar(
                usuarioId: $usuario->id,
                accion: 'Inicio de sesión con Google OAuth 2.0',
                recurso: 'login/google',
                metodo: 'GET',
                estado: 'EXITO',
                ip: $request->ip()
            );

            // Si no ha aceptado Política de Privacidad o Términos, redirigir obligatoriamente
            if (is_null($usuario->politica_aceptada_at)) {
                return redirect()->route('portal.politica', ['revisar' => 1, 'onboarding' => 1]);
            }

            if (is_null($usuario->terminos_aceptados_at)) {
                return redirect()->route('portal.terminos', ['revisar' => 1, 'onboarding' => 1]);
            }

            return redirect()->intended(route('dashboard'));
        } catch (\Throwable $e) {
            Log::error('Google OAuth Exception: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'general' => 'Error al autenticar con Google: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Vista para solicitar y validar un código temporal (OTP) enviado al correo para cambiar la contraseña.
     */
    public function showForgotPassword(Request $request): InertiaResponse
    {
        return Inertia::render('Auth/RecuperarPassword', [
            'email_inicial'  => (string) $request->query('email', session('email_destino', '')),
            'codigo_enviado' => (bool) session('codigo_enviado', false),
        ]);
    }

    /**
     * Genera un código temporal de 6 dígitos y lo envía al correo del usuario.
     */
    public function sendResetCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email   = strtolower(trim($validated['email']));
        $usuario = Usuario::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$usuario) {
            return back()->withErrors([
                'email' => 'No existe ningún usuario activo registrado con ese correo electrónico.',
            ]);
        }

        $codigo = (string) random_int(100000, 999999);

        Cache::put('scala_pwd_otp_' . $email, [
            'codigo'     => $codigo,
            'usuario_id' => $usuario->id,
            'intentos'   => 0,
        ], now()->addMinutes(10));

        try {
            Mail::to($usuario->email)->send(new CodigoTemporalPasswordMail($usuario, $codigo, 10));
        } catch (\Throwable $e) {
            Log::error('Error enviando código temporal por correo: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'No se pudo enviar el correo con el código temporal: ' . $e->getMessage(),
            ]);
        }

        return back()
            ->with('codigo_enviado', true)
            ->with('email_destino', $usuario->email)
            ->with('success', "Se envió un código temporal de 6 dígitos a {$usuario->email}.");
    }

    /**
     * Verifica el código temporal de 6 dígitos y actualiza la contraseña del usuario.
     */
    public function resetPasswordWithCode(Request $request, BitacoraService $bitacoraService): RedirectResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'codigo'   => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'codigo.size'        => 'El código temporal debe tener exactamente 6 dígitos.',
            'password.min'       => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $email    = strtolower(trim($validated['email']));
        $cacheKey = 'scala_pwd_otp_' . $email;
        $otpData  = Cache::get($cacheKey);

        if (!$otpData) {
            return back()
                ->with('codigo_enviado', true)
                ->withErrors([
                    'codigo' => 'El código temporal ha expirado o no fue solicitado. Solicita uno nuevo.',
                ]);
        }

        if ($otpData['codigo'] !== trim($validated['codigo'])) {
            $otpData['intentos'] = ($otpData['intentos'] ?? 0) + 1;
            if ($otpData['intentos'] >= 5) {
                Cache::forget($cacheKey);
                return back()->withErrors([
                    'codigo' => 'Demasiados intentos fallidos. Por seguridad, solicita un nuevo código.',
                ]);
            }
            Cache::put($cacheKey, $otpData, now()->addMinutes(10));

            return back()
                ->with('codigo_enviado', true)
                ->withErrors([
                    'codigo' => 'El código ingresado es incorrecto. Verifica los 6 dígitos en tu correo.',
                ]);
        }

        $usuario = Usuario::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$usuario) {
            return back()->withErrors([
                'email' => 'Usuario no encontrado.',
            ]);
        }

        $usuario->update([
            'password' => $validated['password'],
        ]);

        Cache::forget($cacheKey);

        $bitacoraService->registrar(
            usuarioId: $usuario->id,
            accion: 'Cambio de contraseña mediante código temporal enviado al correo',
            recurso: 'password/verificar-cambiar',
            metodo: 'POST',
            estado: 'EXITO',
            ip: $request->ip()
        );

        return redirect()->route('login')->with(
            'success',
            '¡Contraseña actualizada exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.'
        );
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('login');
    }

    /**
     * Determina dinámicamente la URI de callback de Google según el dominio desde donde se accede.
     */
    private function resolveGoogleRedirectUri(Request $request): string
    {
        $host = $request->getHost();
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return $request->getSchemeAndHttpHost() . '/auth/google/callback';
        }

        $configured = trim((string) config('services.google.redirect'));
        if (!empty($configured)) {
            return $configured;
        }

        return $request->getSchemeAndHttpHost() . '/auth/google/callback';
    }
}
