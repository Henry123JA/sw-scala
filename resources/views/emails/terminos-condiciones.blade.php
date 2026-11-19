<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Términos y Condiciones - Academia SCALA</title>
</head>
<body style="margin:0;padding:24px;background-color:#F0FDF9;font-family:'Segoe UI',Arial,sans-serif;color:#1F2937;">
    <div style="max-width:640px;margin:0 auto;background:#FFFFFF;border:1px solid #DDEFEA;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(15,118,110,0.08);">
        <div style="background:#0F766E;color:#FFFFFF;padding:24px 28px;">
            <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#99F6E4;font-weight:bold;">
                Academia de Música y Arte SCALA
            </p>
            <h1 style="margin:6px 0 0;font-size:20px;font-weight:800;">
                Constancia de Aceptación: Términos y Condiciones de Uso
            </h1>
        </div>

        <div style="padding:28px;font-size:14px;line-height:1.6;">
            <p style="margin-top:0;">
                Estimado(a) <strong>{{ $usuario->nombres }} {{ $usuario->apellidos }}</strong>,
            </p>
            <p>
                Te remitimos para tu respaldo personal la copia de los <strong>Términos y Condiciones de Uso del Sistema Académico SCALA</strong> aceptados formalmente desde tu cuenta institucional.
            </p>

            <div style="background:#F0FDF9;border:1px solid #99F6E4;border-radius:12px;padding:16px;margin:20px 0;">
                <p style="margin:4px 0;"><strong>Documento:</strong> Términos y Condiciones SCALA v2.1 (Gestión 2026)</p>
                <p style="margin:4px 0;"><strong>Usuario:</strong> {{ $usuario->nombres }} {{ $usuario->apellidos }} ({{ $usuario->email }})</p>
                <p style="margin:4px 0;"><strong>Código / Rol:</strong> {{ $usuario->codigo ?? 'SCALA' }} · {{ $usuario->rol?->nombre ?? 'Usuario' }}</p>
                <p style="margin:4px 0;"><strong>Fecha y Hora de Aceptación:</strong> {{ $fechaAceptacion }} (Estado: ACEPTADO)</p>
            </div>

            <h3 style="color:#0F766E;font-size:15px;margin-bottom:8px;">Resumen de Condiciones Reglamentarias Aceptadas:</h3>
            <ol style="padding-left:20px;color:#374151;">
                <li style="margin-bottom:8px;">
                    <strong>Asignación y Custodia de Credenciales de Acceso:</strong> Las credenciales de acceso son personales e intransferibles, siendo responsabilidad del titular mantener su confidencialidad.
                </li>
                <li style="margin-bottom:8px;">
                    <strong>Régimen de Asistencia, Puntualidad y Licencias:</strong> La asistencia se registra por sesión (Presente, Atraso, Licencia justificada o Falta injustificada). Las solicitudes de pausa temporal deben gestionarse ante Secretaría Académica.
                </li>
                <li style="margin-bottom:8px;">
                    <strong>Evaluaciones Formativas, Audiciones y Seguimiento Pedagógico:</strong> Las calificaciones prácticas, teóricas, audiciones y recomendaciones docentes forman parte del expediente académico oficial del estudiante.
                </li>
                <li style="margin-bottom:8px;">
                    <strong>Uso Responsable de Recursos y Aulas:</strong> Los miembros de la comunidad académica se comprometen a respetar los horarios asignados, el aforo de las aulas acustizadas y el cuidado de los instrumentos musicales.
                </li>
            </ol>

            <p style="margin-top:24px;font-size:12px;color:#6B7280;border-top:1px solid #E5E7EB;padding-top:16px;">
                Este correo ha sido enviado automáticamente desde la cuenta de Administración de la Academia de Música y Arte SCALA (Santa Cruz de la Sierra, Bolivia).
            </p>
        </div>
    </div>
</body>
</html>
