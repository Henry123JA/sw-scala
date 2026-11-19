<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido a la Academia de Música SCALA</title>
</head>
<body style="margin:0;padding:24px;background-color:#F0FDF9;font-family:'Segoe UI',Arial,sans-serif;color:#1F2937;">
    <div style="max-width:640px;margin:0 auto;background:#FFFFFF;border:1px solid #DDEFEA;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(15,118,110,0.08);">
        <div style="background:linear-gradient(135deg,#0F766E,#14B8A6);color:#FFFFFF;padding:28px;">
            <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#CCFBF1;font-weight:bold;">
                Onboarding Institucional Completado
            </p>
            <h1 style="margin:8px 0 0;font-size:22px;font-weight:900;">
                ¡Bienvenido(a) a la Academia de Música y Arte SCALA! 🎵
            </h1>
        </div>

        <div style="padding:28px;font-size:14px;line-height:1.6;">
            <p style="margin-top:0;">
                Hola <strong>{{ $usuario->nombres }} {{ $usuario->apellidos }}</strong>,
            </p>
            <p>
                ¡Te damos la más cordial bienvenida al <strong>Sistema Web de Gestión y Seguimiento Académico Predictivo SCALA</strong>! Has completado satisfactoriamente la validación de la Política de Privacidad y de los Términos y Condiciones de Uso.
            </p>

            <div style="background:#F8FFFD;border:1px solid #99F6E4;border-radius:12px;padding:18px;margin:20px 0;">
                <h3 style="margin:0 0 10px;color:#0F766E;font-size:15px;">Credenciales yFicha de tu Cuenta:</h3>
                <p style="margin:4px 0;"><strong>Nombre Completo:</strong> {{ $usuario->nombres }} {{ $usuario->apellidos }}</p>
                <p style="margin:4px 0;"><strong>Correo de Acceso:</strong> {{ $usuario->email }}</p>
                <p style="margin:4px 0;"><strong>Código Institucional:</strong> {{ $usuario->codigo ?? 'SCALA' }}</p>
                <p style="margin:4px 0;"><strong>Perfil / Rol Asignado:</strong> {{ $usuario->rol?->nombre ?? 'Estudiante' }}</p>
                <p style="margin:4px 0;"><strong>Fecha de Activación:</strong> {{ $fechaIngreso }}</p>
            </div>

            <p>
                Desde tu panel académico ya puedes consultar horarios, inscripciones, asistencias por sesión, evaluaciones, progreso musical y seguimiento académico.
            </p>

            <div style="text-align:center;margin:26px 0;">
                <a href="{{ config('app.url', 'https://techmusic.work') }}/dashboard"
                   style="display:inline-block;background:#0F766E;color:#FFFFFF;text-decoration:none;font-weight:bold;padding:12px 28px;border-radius:10px;font-size:14px;">
                    Ingresar al Panel Académico SCALA →
                </a>
            </div>

            <p style="margin-top:24px;font-size:12px;color:#6B7280;border-top:1px solid #E5E7EB;padding-top:16px;">
                Academia de Música y Arte SCALA · Santa Cruz de la Sierra, Bolivia · <code>techmusic.work</code>
            </p>
        </div>
    </div>
</body>
</html>
