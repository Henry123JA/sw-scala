<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Usuario;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected AuthFactory $auth,
        protected BitacoraService $bitacoraService
    ) {}

    /**
     * Autentica a un usuario con las credenciales provistas.
     *
     * @throws BusinessException
     */
    public function login(array $credentials): bool
    {
        $email = $credentials['email'] ?? '';
        $password = $credentials['password'] ?? '';
        $remember = (bool)($credentials['remember'] ?? false);

        // Buscar el usuario por email.
        // Dado que el modelo Usuario tiene un scope global 'activo', solo buscará usuarios no eliminados.
        $usuario = Usuario::where('email', $email)->first();

        // Verificar contraseña y existencia del usuario
        if (!$usuario || !Hash::check($password, $usuario->password)) {
            // Registrar intento fallido en bitácora
            $this->bitacoraService->registrar(
                usuarioId: $usuario?->id, // logs ID if user exists, otherwise null
                accion: 'Intento de inicio de sesión fallido',
                recurso: 'login',
                metodo: 'POST',
                estado: 'FALLO',
                ip: request()->ip()
            );

            throw new BusinessException('Credenciales incorrectas', 'password');
        }

        // Realizar la autenticación
        $guard = $this->auth->guard('web');
        $guard->login($usuario, $remember);

        // Registrar inicio de sesión exitoso
        $this->bitacoraService->registrar(
            usuarioId: $usuario->id,
            accion: 'Inicio de sesión exitoso',
            recurso: 'login',
            metodo: 'POST',
            estado: 'EXITO',
            ip: request()->ip()
        );

        return true;
    }

    /**
     * Cierra la sesión del usuario actual.
     */
    public function logout(): void
    {
        $guard = $this->auth->guard('web');
        $usuario = $guard->user();

        if ($usuario) {
            $this->bitacoraService->registrar(
                usuarioId: $usuario->id,
                accion: 'Cierre de sesión',
                recurso: 'logout',
                metodo: 'POST',
                estado: 'EXITO',
                ip: request()->ip()
            );
        }

        $guard->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
