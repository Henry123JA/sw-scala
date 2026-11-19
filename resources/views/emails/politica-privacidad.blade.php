<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Política de Privacidad - Academia SCALA</title>
</head>
<body style="margin:0;padding:24px;background-color:#F0FDF9;font-family:'Segoe UI',Arial,sans-serif;color:#1F2937;">
    <div style="max-width:640px;margin:0 auto;background:#FFFFFF;border:1px solid #DDEFEA;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(15,118,110,0.08);">
        <div style="background:#0F766E;color:#FFFFFF;padding:24px 28px;">
            <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#99F6E4;font-weight:bold;">
                Academia de Música y Arte SCALA
            </p>
            <h1 style="margin:6px 0 0;font-size:20px;font-weight:800;">
                Constancia de Aceptación: Política de Privacidad
            </h1>
        </div>

        <div style="padding:28px;font-size:14px;line-height:1.6;">
            <p style="margin-top:0;">
                Estimado(a) <strong>{{ $usuario->nombres }} {{ $usuario->apellidos }}</strong>,
            </p>
            <p>
                Por medio del presente correo institucional te enviamos una copia íntegra de la <strong>Política de Privacidad y Protección de Datos Académicos</strong> que acabas de aceptar en tu ingreso a la plataforma de la <strong>Academia de Música y Arte SCALA</strong>.
            </p>

            <div style="background:#F0FDF9;border:1px solid #99F6E4;border-radius:12px;padding:16px;margin:20px 0;">
                <p style="margin:4px 0;"><strong>Documento:</strong> Política de Privacidad SCALA v2.1 (Gestión 2026)</p>
                <p style="margin:4px 0;"><strong>Usuario:</strong> {{ $usuario->nombres }} {{ $usuario->apellidos }} ({{ $usuario->email }})</p>
                <p style="margin:4px 0;"><strong>Código / Rol:</strong> {{ $usuario->codigo ?? 'SCALA' }} · {{ $usuario->rol?->nombre ?? 'Usuario' }}</p>
                <p style="margin:4px 0;"><strong>Fecha y Hora de Aceptación:</strong> {{ $fechaAceptacion }} (Estado: ACEPTADO)</p>
            </div>

            <h3 style="color:#0F766E;font-size:15px;margin-bottom:8px;">Resumen de Cláusulas de Privacidad Aceptadas:</h3>
            <ol style="padding-left:20px;color:#374151;">
                <li style="margin-bottom:8px;">
                    <strong>Finalidad de la Recopilación de Datos Académicos:</strong> Los datos personales del estudiante y sus registros formativos (asistencias, calificaciones y bitácora docente) se utilizan exclusivamente para la gestión pedagógica y seguimiento del progreso musical.
                </li>
                <li style="margin-bottom:8px;">
                    <strong>Protección de Menores y Control de Visibilidad (Ley N.º 548):</strong> El acceso a los expedientes individuales está protegido mediante Control de Acceso Basado en Roles (RBAC), garantizando aislamiento de privacidad.
                </li>
                <li style="margin-bottom:8px;">
                    <strong>Uso Ético de Analítica Predictiva (XAI):</strong> Los indicadores del módulo de Seguimiento Académico Predictivo tienen carácter estrictamente preventivo y pedagógico, sin compartir datos con terceros.
                </li>
                <li style="margin-bottom:8px;">
                    <strong>Seguridad Transaccional y Bitácora:</strong> Las credenciales se almacenan bajo cifrado criptográfico unidireccional y toda operación queda registrada en la bitácora institucional.
                </li>
            </ol>

            <p style="margin-top:24px;font-size:12px;color:#6B7280;border-top:1px solid #E5E7EB;padding-top:16px;">
                Este correo ha sido enviado automáticamente desde la cuenta de Administración de la Academia de Música y Arte SCALA (Santa Cruz de la Sierra, Bolivia).
            </p>
        </div>
    </div>
</body>
</html>
