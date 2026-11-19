<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Código Temporal de Seguridad - Academia SCALA</title>
</head>
<body style="margin:0;padding:24px;background-color:#F0FDF9;font-family:'Segoe UI',Arial,sans-serif;color:#1F2937;">
    <div style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #DDEFEA;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(15,118,110,0.08);">
        <div style="background:#0F766E;color:#FFFFFF;padding:24px 28px;">
            <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#99F6E4;font-weight:bold;">
                Seguridad de Cuenta · Academia SCALA
            </p>
            <h1 style="margin:6px 0 0;font-size:20px;font-weight:800;">
                Código Temporal para Cambio de Contraseña
            </h1>
        </div>

        <div style="padding:28px;font-size:14px;line-height:1.6;">
            <p style="margin-top:0;">
                Hola <strong>{{ $usuario->nombres }} {{ $usuario->apellidos }}</strong>,
            </p>
            <p>
                Hemos recibido una solicitud para cambiar la contraseña de tu cuenta (<strong>{{ $usuario->email }}</strong>) en el Sistema Académico de la <strong>Academia de Música y Arte SCALA</strong>.
            </p>
            <p>
                Ingresa el siguiente <strong>código temporal de 6 dígitos</strong> en la pantalla de verificación:
            </p>

            <div style="text-align:center;margin:24px 0;">
                <div style="display:inline-block;background:#F0FDF9;border:2px dashed #14B8A6;border-radius:14px;padding:16px 32px;font-size:32px;font-weight:900;letter-spacing:8px;color:#0F766E;font-family:monospace;">
                    {{ $codigoTemporal }}
                </div>
                <p style="margin:8px 0 0;font-size:12px;color:#6B7280;">
                    ⏱ Este código es válido por <strong>{{ $minutosValidez }} minutos</strong>.
                </p>
            </div>

            <p style="font-size:13px;color:#4B5563;">
                Si tú no solicitaste este código, puedes ignorar este mensaje; tu contraseña actual seguirá siendo segura.
            </p>

            <p style="margin-top:24px;font-size:12px;color:#6B7280;border-top:1px solid #E5E7EB;padding-top:16px;">
                Enviado por Administración — Academia de Música y Arte SCALA · <code>techmusic.work</code>
            </p>
        </div>
    </div>
</body>
</html>
