<?php

namespace App\Services;

use App\Models\AlertaSeguimiento;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Usuario;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AgenteAtencionService
{
    /**
     * Procesa un mensaje del visitante o usuario autenticado utilizando
     * datos en tiempo real de PostgreSQL + OpenAI API (con fallback local).
     */
    public function responder(string $mensaje, array $historial = [], ?Usuario $usuario = null): array
    {
        $datosAcademia = $this->construirContextoBaseDatos($usuario);

        $apiKey = trim((string) config('services.openai.api_key', env('OPENAI_API_KEY', '')));
        $modelo = trim((string) config('services.openai.model', env('OPENAI_MODEL', 'gpt-4o-mini'))) ?: 'gpt-4o-mini';

        if ($apiKey !== '') {
            try {
                $respuestaOpenAi = $this->consultarOpenAi($apiKey, $modelo, $mensaje, $historial, $datosAcademia, $usuario);
                if ($respuestaOpenAi !== null) {
                    return [
                        'respuesta' => $respuestaOpenAi,
                        'motor' => 'OPENAI',
                        'modelo' => $modelo,
                        'contexto_bd' => true,
                        'resumen_bd' => [
                            'cursos_activos' => count($datosAcademia['cursos']),
                            'grupos_abiertos' => count($datosAcademia['grupos']),
                        ],
                    ];
                }
            } catch (Throwable $e) {
                Log::warning('AgenteAtencionService: Fallo llamada a OpenAI, usando motor local con BD.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback inteligente conectado a la misma base de datos PostgreSQL en tiempo real
        $respuestaLocal = $this->responderConMotorLocal($mensaje, $datosAcademia, $usuario);

        return [
            'respuesta' => $respuestaLocal,
            'motor' => 'LOCAL_BD',
            'modelo' => 'SCALA-RAG-PostgreSQL',
            'contexto_bd' => true,
            'resumen_bd' => [
                'cursos_activos' => count($datosAcademia['cursos']),
                'grupos_abiertos' => count($datosAcademia['grupos']),
            ],
        ];
    }

    /**
     * Extrae en tiempo real el catálogo de cursos, grupos abiertos, horarios,
     * cupos disponibles, docentes y contexto del usuario desde PostgreSQL.
     */
    public function construirContextoBaseDatos(?Usuario $usuario = null): array
    {
        $cursos = [];
        $grupos = [];
        $metricas = [
            'cursos_activos' => 0,
            'grupos_abiertos' => 0,
            'docentes_activos' => 0,
            'inscripciones_activas' => 0,
            'alertas_riesgo_pendientes' => 0,
        ];
        $contextoUsuario = null;

        try {
            $cursosDb = Curso::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'descripcion', 'tipo_ensenanza', 'duracion_estandar', 'estado']);

            foreach ($cursosDb as $c) {
                $cursos[] = [
                    'nombre' => $c->nombre,
                    'descripcion' => $c->descripcion,
                    'modalidad' => $c->tipo_ensenanza,
                    'duracion_semanas' => $c->duracion_estandar,
                ];
            }

            $gruposDb = Grupo::query()
                ->with(['curso', 'docente.usuario', 'horarios.sala'])
                ->withCount(['inscripciones as inscritos_activos'])
                ->orderBy('codigo_grupo')
                ->take(15)
                ->get();

            foreach ($gruposDb as $g) {
                $horariosTexto = [];
                foreach ($g->horarios as $h) {
                    $sala = $h->sala ? $h->sala->nombre : 'Sala asignada';
                    $inicio = substr((string) $h->hora_inicio, 0, 5);
                    $fin = substr((string) $h->hora_fin, 0, 5);
                    $horariosTexto[] = "{$h->dia_semana} {$inicio}-{$fin} ({$sala})";
                }

                $capacidad = (int) $g->capacidad_maxima;
                $inscritos = (int) ($g->inscritos_activos ?? 0);
                $cupos = max(0, $capacidad - $inscritos);

                $docenteNombre = 'Docente por asignar';
                if ($g->docente && $g->docente->usuario) {
                    $docenteNombre = trim($g->docente->usuario->nombres . ' ' . $g->docente->usuario->apellidos);
                }

                $grupos[] = [
                    'codigo' => $g->codigo_grupo,
                    'curso' => $g->curso ? $g->curso->nombre : 'Curso Musical',
                    'nivel' => $g->nivel,
                    'docente' => $docenteNombre,
                    'capacidad_maxima' => $capacidad,
                    'inscritos' => $inscritos,
                    'cupos_disponibles' => $cupos,
                    'horarios' => implode(', ', $horariosTexto) ?: 'Horario a coordinar en Secretaría',
                ];
            }

            $metricas = [
                'cursos_activos' => count($cursos),
                'grupos_abiertos' => count($grupos),
                'docentes_activos' => Docente::query()->count(),
            ];

            if ($usuario) {
                $rolNombre = is_object($usuario->rol) ? ($usuario->rol->nombre ?? 'Usuario') : ((string) ($usuario->rol ?? 'Usuario'));
                $contextoUsuario = [
                    'nombre' => trim($usuario->nombres . ' ' . $usuario->apellidos),
                    'email' => $usuario->email,
                    'rol' => $rolNombre,
                ];

                if (in_array($rolNombre, ['Propietario', 'Secretaria'], true)) {
                    // Acceso Ejecutivo Completo para Administración
                    $metricas['inscripciones_activas'] = Inscripcion::query()->count();
                    $metricas['alertas_riesgo_alto'] = AlertaSeguimiento::query()->where('nivel_riesgo', 'ALTO')->count();
                    $metricas['alertas_riesgo_medio'] = AlertaSeguimiento::query()->where('nivel_riesgo', 'MEDIO')->count();
                    $metricas['alertas_riesgo_bajo'] = AlertaSeguimiento::query()->where('nivel_riesgo', 'BAJO')->count();
                    $metricas['alertas_pendientes_atencion'] = AlertaSeguimiento::query()
                        ->whereIn('estado', ['PENDIENTE', 'EN_ATENCION'])
                        ->count();

                    $alertasCriticas = AlertaSeguimiento::query()
                        ->with(['inscripcion.alumno.usuario', 'inscripcion.grupo.curso', 'intervenciones'])
                        ->orderByDesc('score_riesgo')
                        ->take(10)
                        ->get();

                    $contextoUsuario['alumnos_monitoreados_ia'] = $alertasCriticas->map(function ($alerta) {
                        $alumnoUser = $alerta->inscripcion?->alumno?->usuario;
                        $nombreAlumno = $alumnoUser
                            ? trim($alumnoUser->nombres . ' ' . $alumnoUser->apellidos)
                            : 'Estudiante';
                        return [
                            'alumno' => $nombreAlumno,
                            'codigo' => $alerta->inscripcion?->alumno?->codigo ?? ($alumnoUser?->codigo ?? '-'),
                            'curso' => $alerta->inscripcion?->grupo?->curso?->nombre ?? 'Curso',
                            'grupo' => $alerta->inscripcion?->grupo?->codigo_grupo ?? '-',
                            'nivel_riesgo' => $alerta->nivel_riesgo,
                            'score_riesgo' => $alerta->score_riesgo,
                            'estado_alerta' => $alerta->estado,
                            'explicacion_xai' => $alerta->resumen_explicativo,
                            'metricas_base' => $alerta->metricas_base,
                            'intervenciones_registradas' => $alerta->intervenciones->count(),
                        ];
                    })->toArray();
                } elseif ($rolNombre === 'Docente') {
                    // Acceso exclusivo a los grupos y alumnos propios del Docente
                    $misGrupos = Grupo::query()
                        ->with(['curso', 'inscripciones.alumno.usuario', 'inscripciones.alertasSeguimiento'])
                        ->withCount(['inscripciones as alumnos_activos'])
                        ->where('docente_id', $usuario->id)
                        ->get();

                    $contextoUsuario['grupos_asignados'] = $misGrupos->map(function ($g) {
                        return [
                            'codigo' => $g->codigo_grupo,
                            'curso' => $g->curso?->nombre ?? 'Curso',
                            'nivel' => $g->nivel,
                            'alumnos_activos' => $g->alumnos_activos,
                            'alumnos' => $g->inscripciones->map(function ($ins) {
                                $u = $ins->alumno?->usuario;
                                $alerta = $ins->alertasSeguimiento->first();
                                return [
                                    'nombre' => $u ? trim($u->nombres . ' ' . $u->apellidos) : 'Alumno',
                                    'estado_inscripcion' => $ins->estado,
                                    'nivel_riesgo_ia' => $alerta ? $alerta->nivel_riesgo : 'BAJO',
                                    'score_riesgo_ia' => $alerta ? $alerta->score_riesgo : 0,
                                ];
                            })->toArray(),
                        ];
                    })->toArray();
                } elseif ($rolNombre === 'Alumno') {
                    // Acceso exclusivo a su propio expediente estudiantil
                    $misInscripciones = Inscripcion::query()
                        ->with(['grupo.curso', 'grupo.docente.usuario', 'alertasSeguimiento'])
                        ->where('alumno_id', $usuario->id)
                        ->get();

                    $contextoUsuario['mis_cursos'] = $misInscripciones->map(function ($ins) {
                        $alerta = $ins->alertasSeguimiento->first();
                        return [
                            'curso' => $ins->grupo?->curso?->nombre ?? 'Curso',
                            'grupo' => $ins->grupo?->codigo_grupo ?? '-',
                            'estado_inscripcion' => $ins->estado,
                            'nivel_riesgo_ia' => $alerta ? $alerta->nivel_riesgo : 'BAJO',
                            'score_riesgo_ia' => $alerta ? $alerta->score_riesgo : 0,
                            'metricas_personales' => $alerta ? $alerta->metricas_base : null,
                        ];
                    })->toArray();
                }
            }
        } catch (Throwable $e) {
            Log::warning('AgenteAtencionService: Error consultando BD para contexto.', [
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'academia' => [
                'nombre' => 'Academia de Música y Arte SCALA',
                'dominio' => 'techmusic.work',
                'ciudad' => 'Santa Cruz de la Sierra, Bolivia',
                'telefono' => '+591 700-45890',
                'horarios_atencion' => 'Lunes a Viernes de 08:00 a 20:00 y Sábados de 09:00 a 18:00',
                'facebook' => 'https://www.facebook.com/p/Academia-De-Musica-y-Arte-Scala-100064673172866/',
                'instagram' => 'https://www.instagram.com/academia.scala/',
                'google_maps' => 'https://maps.app.goo.gl/Qrk9fpKYiQyFAHyi9',
                'mision' => 'Brindar una formación musical y artística integral de alta calidad a niños, jóvenes y adultos, combinando práctica instrumental, teoría y expresión escénica con seguimiento continuo.',
                'vision' => 'Consolidarse como la academia referente en educación musical e innovación educativa en Bolivia con analítica predictiva e inteligencia artificial explicable (XAI).',
            ],
            'metricas' => $metricas,
            'cursos' => $cursos,
            'grupos' => $grupos,
            'usuario_actual' => $contextoUsuario,
        ];
    }

    /**
     * Realiza la petición a OpenAI Chat Completions con el contexto en vivo de PostgreSQL.
     */
    protected function consultarOpenAi(
        string $apiKey,
        string $modelo,
        string $mensaje,
        array $historial,
        array $datosAcademia,
        ?Usuario $usuario
    ): ?string {
        $jsonContexto = json_encode($datosAcademia, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $rolNombre = $usuario ? (is_object($usuario->rol) ? ($usuario->rol->nombre ?? 'Usuario') : ((string) ($usuario->rol ?? 'Usuario'))) : '';
        if (!$usuario) {
            $rolInstruccion = "MODO VISITANTE PÚBLICO (Sin autenticar en techmusic.work): Estás atendiendo a un visitante o postulante. RESTRICCIÓN ESTRICTA DE SEGURIDAD Y PRIVACIDAD: Solo puedes informar sobre la oferta académica pública (cursos, modalidades, horarios de grupos, aulas, cupos disponibles, docentes que dictan cada curso, misión, visión, ubicación, contacto y proceso de inscripción). TIENES ESTRICTAMENTE PROHIBIDO revelar nombres de alumnos, calificaciones, asistencias, reportes internos o alertas de riesgo académico. Si el visitante pide datos de alumnos o información administrativa interna, rechaza amablemente indicando que esos datos están protegidos por la Política de Privacidad de SCALA y requieren iniciar sesión con un rol autorizado.";
        } elseif (in_array($rolNombre, ['Propietario', 'Secretaria'], true)) {
            $rolInstruccion = "MODO ADMINISTRATIVO EJECUTIVO ({$usuario->nombres} {$usuario->apellidos} - Rol: {$rolNombre}): Tienes autorización total para informar sobre métricas globales de la academia, listado nominal de alumnos en riesgo predictivo (ALTO, MEDIO, BAJO), scores de riesgo, explicaciones XAI (asistencia, promedio de notas, faltas), intervenciones pedagógicas, ocupación de grupos y docentes.";
        } elseif ($rolNombre === 'Docente') {
            $rolInstruccion = "MODO DOCENTE ({$usuario->nombres} {$usuario->apellidos} - Rol: Docente): Puedes informar sobre la oferta general y sobre los grupos y alumnos asignados a este docente (incluyendo el estado de riesgo e indicadores de sus propios alumnos). No puedes revelar información privada de grupos de otros docentes.";
        } else {
            $rolInstruccion = "MODO ESTUDIANTE ({$usuario->nombres} {$usuario->apellidos} - Rol: Alumno): Puedes informar sobre la oferta de la academia y EXCLUSIVAMENTE sobre el expediente propio de este estudiante (sus cursos inscritos, su grupo, sus métricas personales y su estado de seguimiento). No puedes revelar notas ni alertas de otros compañeros.";
        }

        $systemPrompt = <<<PROMPT
Eres el "Agente de Atención SCALA", el asistente virtual oficial con Inteligencia Artificial de la Academia de Música y Arte SCALA (Santa Cruz de la Sierra, Bolivia - dominio techmusic.work).
{$rolInstruccion}

REGLAS DE RESPUESTA:
1. Responde siempre en español, con tono amable, institucional, claro y conciso (máximo 4 a 6 líneas o viñetas breves para que sea cómodo de leer en la ventana de chat).
2. Usa EXCLUSIVAMENTE los datos reales de la base de datos PostgreSQL que te proporciono abajo (cursos reales, grupos abiertos, horarios, salas, docentes, cupos disponibles, métricas y seguimiento predictivo XAI).
3. Si preguntan por inscripción o registro: explica que el registro lo realiza el personal autorizado en Secretaría Académica (o dejando sus datos en el Formulario de Contacto del portal), tras lo cual reciben sus credenciales para su Primer Inicio de Sesión en SCALA-Digital (donde aceptan la Política de Privacidad y los Términos y Condiciones).
4. Si preguntan por ubicación o redes: proporciona el teléfono +591 700-45890, horarios de atención y menciona que pueden abrir el enlace directo de Google Maps, Facebook o Instagram en el portal.

DATOS EN TIEMPO REAL DE LA BASE DE DATOS POSTGRESQL (SCALA):
{$jsonContexto}
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach (array_slice($historial, -6) as $item) {
            $emisor = ($item['emisor'] ?? '') === 'usuario' ? 'user' : 'assistant';
            $texto = trim((string) ($item['texto'] ?? ''));
            if ($texto !== '') {
                $messages[] = [
                    'role' => $emisor,
                    'content' => $texto,
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $mensaje,
        ];

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $modelo,
                'messages' => $messages,
                'temperature' => 0.4,
                'max_tokens' => 380,
            ]);

        if ($response->successful()) {
            $content = data_get($response->json(), 'choices.0.message.content');
            if (is_string($content) && trim($content) !== '') {
                return trim($content);
            }
        } else {
            Log::warning('OpenAI API respondió con error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return null;
    }

    /**
     * Motor inteligente de respaldo conectado en tiempo real a PostgreSQL (RAG Local).
     */
    protected function responderConMotorLocal(string $mensaje, array $datos, ?Usuario $usuario): string
    {
        $q = mb_strtolower($mensaje, 'UTF-8');
        $cursos = $datos['cursos'] ?? [];
        $grupos = $datos['grupos'] ?? [];
        $metricas = $datos['metricas'] ?? [];

        // 1. Consultas sobre grupos, horarios o cupos reales de la BD
        if (str_contains($q, 'horario') || str_contains($q, 'grupo') || str_contains($q, 'cupo') || str_contains($q, 'docente') || str_contains($q, 'profesor')) {
            if (!empty($grupos)) {
                $lineas = array_map(function ($g) {
                    return "• {$g['codigo']} ({$g['curso']} - Nivel {$g['nivel']}): {$g['horarios']} | Docente: {$g['docente']} | Cupos libres: {$g['cupos_disponibles']}/{$g['capacidad_maxima']}";
                }, array_slice($grupos, 0, 5));

                return "Actualmente tenemos {$metricas['grupos_abiertos']} grupos abiertos en nuestra base de datos:\n"
                    . implode("\n", $lineas)
                    . "\nAtendemos de Lunes a Viernes (08:00 a 20:00) y Sábados (09:00 a 18:00).";
            }
        }

        // 2. Consultas sobre cursos o instrumentos reales de la BD
        if (str_contains($q, 'curso') || str_contains($q, 'instrumento') || str_contains($q, 'piano') || str_contains($q, 'guitarra') || str_contains($q, 'viol') || str_contains($q, 'bater') || str_contains($q, 'canto') || str_contains($q, 'vocal')) {
            if (!empty($cursos)) {
                $lista = array_map(function ($c) {
                    $mod = ucfirst((string) $c['modalidad']);
                    return "• {$c['nombre']} (Modalidad {$mod}, {$c['duracion_semanas']} semanas)";
                }, $cursos);

                return "En la Academia de Música y Arte SCALA contamos con los siguientes programas activos en sistema:\n"
                    . implode("\n", $lista)
                    . "\n¿Te gustaría conocer los horarios y cupos disponibles de alguno en particular?";
            }
        }

        // 3. Consultas sobre inscripción, registro manual o primer inicio de sesión
        if (str_contains($q, 'inscri') || str_contains($q, 'registr') || str_contains($q, 'requisito') || str_contains($q, 'precio') || str_contains($q, 'costo')) {
            return "El proceso de inscripción en SCALA consta de 3 pasos:\n"
                . "1. Deja tu consulta en nuestro Formulario de Contacto o comunícate al +591 700-45890.\n"
                . "2. El personal autorizado de Secretaría realiza tu registro manual asignando curso, grupo, aula y docente.\n"
                . "3. Recibes tus credenciales para tu Primer Inicio de Sesión en SCALA-Digital (techmusic.work), donde aceptas la Política de Privacidad y los Términos y Condiciones.";
        }

        // 4. Consultas sobre ubicación, dirección, mapa o redes sociales
        if (str_contains($q, 'ubica') || str_contains($q, 'direcci') || str_contains($q, 'mapa') || str_contains($q, 'donde') || str_contains($q, 'dónde') || str_contains($q, 'telefono') || str_contains($q, 'facebook') || str_contains($q, 'instagram')) {
            return "Estamos ubicados en Santa Cruz de la Sierra, Bolivia.\n"
                . "• Google Maps oficial: https://maps.app.goo.gl/Qrk9fpKYiQyFAHyi9\n"
                . "• Teléfono / WhatsApp: +591 700-45890\n"
                . "• Horario: Lunes a Viernes de 08:00 a 20:00 y Sábados de 09:00 a 18:00.\n"
                . "También puedes visitarnos en nuestras cuentas oficiales de Facebook e Instagram desde los botones del portal.";
        }

        // 5. Consultas sobre IA, riesgo predictivo o seguimiento académico
        if (str_contains($q, 'riesgo') || str_contains($q, 'ia') || str_contains($q, 'inteligencia') || str_contains($q, 'predictiv') || str_contains($q, 'seguimiento')) {
            return "El sistema SCALA integra un módulo de Seguimiento Académico Predictivo con Inteligencia Artificial Explicable (Random Forest + Tree SHAP).\n"
                . "• Actualmente monitoreamos {$metricas['inscripciones_activas']} inscripciones activas y {$metricas['alertas_riesgo_pendientes']} alertas de seguimiento.\n"
                . "• Analiza asistencia, faltas, tendencia de calificaciones y bitácora docente para programar tutorías preventivas a tiempo.";
        }

        // 6. Consultas sobre Misión, Visión o valores
        if (str_contains($q, 'mision') || str_contains($q, 'misión') || str_contains($q, 'vision') || str_contains($q, 'visión') || str_contains($q, 'academia')) {
            return "• Misión SCALA: {$datos['academia']['mision']}\n"
                . "• Visión SCALA: {$datos['academia']['vision']}\n"
                . "Contamos actualmente con {$metricas['cursos_activos']} programas musicales, {$metricas['docentes_activos']} docentes especialistas y {$metricas['grupos_abiertos']} grupos abiertos.";
        }

        // 7. Si es un usuario autenticado dentro del panel y pide un resumen
        if ($usuario) {
            $rolNombre = is_object($usuario->rol) ? ($usuario->rol->nombre ?? 'Usuario') : ((string) ($usuario->rol ?? 'Usuario'));
            return "Hola {$usuario->nombres} ({$rolNombre}). En tiempo real nuestra base de datos registra {$metricas['cursos_activos']} cursos activos, {$metricas['grupos_abiertos']} grupos abiertos, {$metricas['inscripciones_activas']} inscripciones activas y {$metricas['alertas_riesgo_pendientes']} alertas predictivas en seguimiento. ¿Deseas consultar horarios de grupos, cupos o detalles del módulo predictivo?";
        }

        return "¡Hola! Soy el Agente de Atención de la Academia de Música y Arte SCALA. En este momento tenemos {$metricas['cursos_activos']} cursos musicales activos y {$metricas['grupos_abiertos']} grupos con inscripciones abiertas en Santa Cruz de la Sierra. Puedes preguntarme por cursos, horarios y cupos en vivo, cómo inscribirte, ubicación en Google Maps o tu primer inicio de sesión.";
    }
}
