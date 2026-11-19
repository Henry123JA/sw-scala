<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);

    // Autenticación OAuth 2.0 con Google
    Route::get('auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Cambio / Recuperación de contraseña mediante código temporal de 6 dígitos enviado al correo
Route::get('password/codigo', [AuthController::class, 'showForgotPassword'])->name('password.codigo');
Route::post('password/enviar-codigo', [AuthController::class, 'sendResetCode'])->name('password.codigo.send');
Route::post('password/verificar-cambiar', [AuthController::class, 'resetPasswordWithCode'])->name('password.codigo.reset');

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
