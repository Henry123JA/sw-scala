<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\SesionClase;
use App\Models\Asistencia;
use App\Models\Evaluacion;
use App\Models\Calificacion;
use App\Models\ObservacionAcademica;
use Carbon\Carbon;

/**
 * CicloAcademicoSeeder
 *
 * Popula datos completos y realistas del ciclo académico activo:
 * - HU8 / HU9: Sesiones de clase e historial de asistencia (PRESENTE, FALTA, ATRASO, LICENCIA)
 * - HU10: Evaluaciones por grupo (Prácticas, Teóricas, Audiciones, Recitales) y calificaciones
 * - HU12: Observaciones pedagógicas (Progreso técnico, Recomendaciones, Disciplina, Felicitaciones)
 * - Alimenta directamente los motores analíticos de HU11 (Progreso) y HU13 (Seguimiento Ficha Integral),
 *   dejando una base sólida para HU14 (Reportes Académicos).
 *
 * Idempotente: seguro de ejecutar múltiples veces sin duplicar registros.
 */
class CicloAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────
        // 1. SESIONES DE CLASE Y REGISTRO DE ASISTENCIA (HU8 & HU9)
        // ─────────────────────────────────────────────────────────────
        $sesionesPlan = [
            // GRP-PIA-01 (Piano Básico - Docente Carlos Flores)
            'GRP-PIA-01' => [
                ['fecha' => '2026-10-05', 'asistencias' => ['ALU-001' => ['PRESENTE', null], 'ALU-006' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-12', 'asistencias' => ['ALU-001' => ['PRESENTE', null], 'ALU-006' => ['ATRASO', 'Llegó 15 min tarde por tráfico']]],
                ['fecha' => '2026-10-19', 'asistencias' => ['ALU-001' => ['PRESENTE', null], 'ALU-006' => ['LICENCIA', 'Permiso médico justificado']]],
                ['fecha' => '2026-10-26', 'asistencias' => ['ALU-001' => ['PRESENTE', null], 'ALU-006' => ['PRESENTE', null]]],
                ['fecha' => '2026-11-02', 'asistencias' => ['ALU-001' => ['ATRASO', 'Ingreso 10 min tarde'], 'ALU-006' => ['FALTA', 'Sin justificativo previo a la pausa']]],
                 // ALU-006 ya en pausa
            ],
            // GRP-GIT-01 (Guitarra Acústica - Docente Roberto Chávez)
            'GRP-GIT-01' => [
                ['fecha' => '2026-10-07', 'asistencias' => ['ALU-002' => ['PRESENTE', null], 'ALU-010' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-14', 'asistencias' => ['ALU-002' => ['PRESENTE', null], 'ALU-010' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-21', 'asistencias' => ['ALU-002' => ['ATRASO', 'Retraso de 10 min'], 'ALU-010' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-28', 'asistencias' => ['ALU-002' => ['PRESENTE', null], 'ALU-010' => ['PRESENTE', null]]],
                ['fecha' => '2026-11-04', 'asistencias' => ['ALU-002' => ['LICENCIA', 'Licencia por exámenes de colegio'], 'ALU-010' => ['PRESENTE', null]]],
            ],
            // GRP-PIA-02 (Piano Intermedio - Docente Carlos Flores)
            'GRP-PIA-02' => [
                ['fecha' => '2026-10-06', 'asistencias' => ['ALU-003' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-13', 'asistencias' => ['ALU-003' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-20', 'asistencias' => ['ALU-003' => ['LICENCIA', 'Viaje de representación estudiantil']]],
                ['fecha' => '2026-10-27', 'asistencias' => ['ALU-003' => ['PRESENTE', null]]],
            ],
            // GRP-CAN-01 (Técnica Vocal - Docente María Torrico)
            'GRP-CAN-01' => [
                ['fecha' => '2026-10-06', 'asistencias' => ['ALU-005' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-13', 'asistencias' => ['ALU-005' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-20', 'asistencias' => ['ALU-005' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-27', 'asistencias' => ['ALU-005' => ['PRESENTE', null]]],
            ],
            // GRP-GIT-02 (Guitarra Acústica - Docente Roberto Chávez)
            'GRP-GIT-02' => [
                ['fecha' => '2026-10-08', 'asistencias' => ['ALU-003' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-15', 'asistencias' => ['ALU-003' => ['PRESENTE', null]]],
                ['fecha' => '2026-10-22', 'asistencias' => ['ALU-003' => ['FALTA', 'Inasistencia no justificada']]],
                ['fecha' => '2026-10-29', 'asistencias' => ['ALU-003' => ['PRESENTE', null]]],
            ],
        ];

        foreach ($sesionesPlan as $codigoGrupo => $sesiones) {
            $grupo = Grupo::where('codigo_grupo', $codigoGrupo)->first();
            if (! $grupo) {
                continue;
            }

            foreach ($sesiones as $sData) {
                $sesion = SesionClase::firstOrCreate(
                    [
                        'grupo_id' => $grupo->id,
                        'fecha'    => Carbon::parse($sData['fecha']),
                    ]
                );

                foreach ($sData['asistencias'] as $aluCod => [$estado, $obs]) {
                    $inscripcion = Inscripcion::where('grupo_id', $grupo->id)
                        ->whereHas('alumno.usuario', function ($q) use ($aluCod) {
                            $q->where('codigo', $aluCod);
                        })
                        ->first();

                    if ($inscripcion) {
                        Asistencia::updateOrCreate(
                            [
                                'sesion_clase_id' => $sesion->id,
                                'inscripcion_id'  => $inscripcion->id,
                            ],
                            [
                                'estado'      => $estado,
                                'observacion' => $obs,
                            ]
                        );
                    }
                }
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 2. EVALUACIONES Y CALIFICACIONES (HU10)
        // ─────────────────────────────────────────────────────────────
        $evaluacionesPlan = [
            'GRP-PIA-01' => [
                [
                    'nombre'      => 'Práctica #1: Postura e Independencia Digital',
                    'tipo'        => 'PRACTICA',
                    'fecha'       => '2026-10-12',
                    'ponderacion' => 30.00,
                    'descripcion' => 'Evaluación de postura ante el teclado, arco de la mano y digitación básica.',
                    'notas'       => [
                        'ALU-001' => [95.00, 'Excelente curvatura de dedos y pulsación regular.'],
                        'ALU-006' => [70.00, 'Tensión en hombros y muñeca izquierda. Requiere mayor relajación.'],
                    ],
                ],
                [
                    'nombre'      => 'Examen Teórico: Clave de Sol y Métricas Simples',
                    'tipo'        => 'TEORIA',
                    'fecha'       => '2026-10-26',
                    'ponderacion' => 30.00,
                    'descripcion' => 'Lectura rítmica en 4/4 y 3/4, identificación de notas en el pentagrama.',
                    'notas'       => [
                        'ALU-001' => [92.00, 'Dominio completo de notas y silencios de negra y corchea.'],
                        'ALU-006' => [65.00, 'Confusiones menores en tresillos y silencios de redonda.'],
                    ],
                ],
                [
                    'nombre'      => 'Audición Parcial: Minueto en Sol Mayor',
                    'tipo'        => 'AUDICION',
                    'fecha'       => '2026-11-06',
                    'ponderacion' => 40.00,
                    'descripcion' => 'Interpretación en vivo frente al grupo con acompañamiento de metrónomo.',
                    'notas'       => [
                        'ALU-001' => [96.00, 'Interpretación impecable, tempo fluido y matices dinámicos claros.'],
                    ],
                ],
            ],
            'GRP-GIT-01' => [
                [
                    'nombre'      => 'Práctica #1: Acordes Mayores y Rasgueo Tradicional',
                    'tipo'        => 'PRACTICA',
                    'fecha'       => '2026-10-14',
                    'ponderacion' => 30.00,
                    'descripcion' => 'Transición entre acordes de primera y segunda posición sin corte de sonido.',
                    'notas'       => [
                        'ALU-010' => [98.00, 'Cejilla firme sin trasteo, cambios limpios a tempo exacto.'],
                        'ALU-002' => [78.00, 'Buen sonido general, afinar velocidad en cambios hacia Fa mayor.'],
                    ],
                ],
                [
                    'nombre'      => 'Examen Teórico: Escalas Diatónicas y Círculos Armónicos',
                    'tipo'        => 'TEORIA',
                    'fecha'       => '2026-10-28',
                    'ponderacion' => 30.00,
                    'descripcion' => 'Construcción de acordes tríadas y lectura de tablatura.',
                    'notas'       => [
                        'ALU-010' => [94.00, 'Respuestas precisas y excelente comprensión armónica.'],
                        'ALU-002' => [72.00, 'Comprende la estructura pero duda en las alteraciones accidentales.'],
                    ],
                ],
                [
                    'nombre'      => 'Recital de Mitad de Ciclo: Cueca y Bailecito',
                    'tipo'        => 'RECITAL',
                    'fecha'       => '2026-11-07',
                    'ponderacion' => 40.00,
                    'descripcion' => 'Interpretación de dos obras folclóricas bolivianas con afinación estándar.',
                    'notas'       => [
                        'ALU-010' => [97.00, 'Gran expresividad, rasgueo tradicional auténtico y sonoridad brillante.'],
                        'ALU-002' => [82.00, 'Notable avance escénico y buen acompañamiento rítmico.'],
                    ],
                ],
            ],
            'GRP-PIA-02' => [
                [
                    'nombre'      => 'Práctica de Técnica: Czerny Op. 599 N° 1 al 15',
                    'tipo'        => 'PRACTICA',
                    'fecha'       => '2026-10-13',
                    'ponderacion' => 35.00,
                    'descripcion' => 'Velocidad controlada con metrónomo a 88 bpm y articulación staccato/legato.',
                    'notas'       => [
                        'ALU-003' => [88.00, 'Excelente claridad en arpegios de mano derecha.'],
                    ],
                ],
                [
                    'nombre'      => 'Examen Teórico: Armaduras de Clave y Círculo de Quintas',
                    'tipo'        => 'TEORIA',
                    'fecha'       => '2026-10-27',
                    'ponderacion' => 30.00,
                    'descripcion' => 'Reconocimiento de tonalidades mayores y menores hasta 4 alteraciones.',
                    'notas'       => [
                        'ALU-003' => [85.00, 'Identificación rápida de tonalidades sostenidas y bemoles.'],
                    ],
                ],
                [
                    'nombre'      => 'Audición de Piano: Sonatina Op. 36 N° 1 (Clementi)',
                    'tipo'        => 'AUDICION',
                    'fecha'       => '2026-11-03',
                    'ponderacion' => 35.00,
                    'descripcion' => 'Interpretación del primer movimiento Allegro con dinámicas p, mf y f.',
                    'notas'       => [
                        'ALU-003' => [90.00, 'Gran musicalidad y equilibrio sonoro entre melodía y bajo Alberti.'],
                    ],
                ],
            ],
            'GRP-CAN-01' => [
                [
                    'nombre'      => 'Práctica Técnica: Respiración Costo-Diafragmática y Apoyo',
                    'tipo'        => 'PRACTICA',
                    'fecha'       => '2026-10-13',
                    'ponderacion' => 30.00,
                    'descripcion' => 'Mantenimiento del fiato en notas tenidas y vocalización de escalas pentatónicas.',
                    'notas'       => [
                        'ALU-005' => [92.00, 'Excelente gestión del aire sin elevar los hombros.'],
                    ],
                ],
                [
                    'nombre'      => 'Control Teórico: Anatomía Vocal e Higiene de la Voz',
                    'tipo'        => 'TEORIA',
                    'fecha'       => '2026-10-27',
                    'ponderacion' => 30.00,
                    'descripcion' => 'Conceptos de fonación, cuidado del tracto vocal y registro de cabeza y pecho.',
                    'notas'       => [
                        'ALU-005' => [90.00, 'Conoce a detalle las pautas de cuidado y prevención vocal.'],
                    ],
                ],
                [
                    'nombre'      => 'Audición: Aria Antigua o Canción Popular Lírica',
                    'tipo'        => 'AUDICION',
                    'fecha'       => '2026-11-03',
                    'ponderacion' => 40.00,
                    'descripcion' => 'Interpretación solista con pista o acompañamiento instrumental.',
                    'notas'       => [
                        'ALU-005' => [95.00, 'Timbre claro, afinación exacta y dicción sobresaliente.'],
                    ],
                ],
            ],
            'GRP-GIT-02' => [
                [
                    'nombre'      => 'Práctica de Arpegios y Punteo Inicial',
                    'tipo'        => 'PRACTICA',
                    'fecha'       => '2026-10-15',
                    'ponderacion' => 50.00,
                    'descripcion' => 'Fórmula p-i-m-a sobre acordes de La menor y Mi menor.',
                    'notas'       => [
                        'ALU-003' => [84.00, 'Buen control de mano derecha y balance entre cuerdas graves y agudas.'],
                    ],
                ],
                [
                    'nombre'      => 'Audición de Dúo Instrumental',
                    'tipo'        => 'AUDICION',
                    'fecha'       => '2026-11-05',
                    'ponderacion' => 50.00,
                    'descripcion' => 'Ensamble a dos voces con melodía y acompañamiento.',
                    'notas'       => [
                        'ALU-003' => [86.00, 'Buena escucha mutua y ritmo sólido durante toda la pieza.'],
                    ],
                ],
            ],
        ];

        foreach ($evaluacionesPlan as $codigoGrupo => $evaluaciones) {
            $grupo = Grupo::where('codigo_grupo', $codigoGrupo)->first();
            if (! $grupo) {
                continue;
            }

            foreach ($evaluaciones as $eData) {
                $evaluacion = Evaluacion::updateOrCreate(
                    [
                        'grupo_id' => $grupo->id,
                        'nombre'   => $eData['nombre'],
                    ],
                    [
                        'tipo'        => $eData['tipo'],
                        'fecha'       => Carbon::parse($eData['fecha']),
                        'ponderacion' => $eData['ponderacion'],
                        'descripcion' => $eData['descripcion'],
                    ]
                );

                foreach ($eData['notas'] as $aluCod => [$nota, $obs]) {
                    $inscripcion = Inscripcion::where('grupo_id', $grupo->id)
                        ->whereHas('alumno.usuario', function ($q) use ($aluCod) {
                            $q->where('codigo', $aluCod);
                        })
                        ->first();

                    if ($inscripcion) {
                        Calificacion::updateOrCreate(
                            [
                                'evaluacion_id'  => $evaluacion->id,
                                'inscripcion_id' => $inscripcion->id,
                            ],
                            [
                                'nota'          => $nota,
                                'observaciones' => $obs,
                            ]
                        );
                    }
                }
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 3. OBSERVACIONES ACADÉMICAS PEDAGÓGICAS (HU12)
        // ─────────────────────────────────────────────────────────────
        $observacionesPlan = [
            // Sofía Laime Quispe (ALU-001 en GRP-PIA-01)
            [
                'grupo'       => 'GRP-PIA-01',
                'alumno'      => 'ALU-001',
                'fecha'       => '2026-10-15',
                'tipo'        => 'PROGRESO_TECNICO',
                'titulo'      => 'Notable evolución en independencia de dedos',
                'descripcion' => 'Sofía ha demostrado una notable constancia en el estudio diario. La pulsación en los dedos 4 y 5 de ambas manos ha mejorado ostensiblemente.',
                'visible'     => true,
            ],
            [
                'grupo'       => 'GRP-PIA-01',
                'alumno'      => 'ALU-001',
                'fecha'       => '2026-11-06',
                'tipo'        => 'FELICITACION',
                'titulo'      => 'Presentación sobresaliente en la Audición de Minueto',
                'descripcion' => 'Felicitaciones por la excelente interpretación en vivo. Mantuvo el tempo firme y demostró solidez técnica bajo presión escénica.',
                'visible'     => true,
            ],
            [
                'grupo'       => 'GRP-PIA-01',
                'alumno'      => 'ALU-001',
                'fecha'       => '2026-11-07',
                'tipo'        => 'RECOMENDACION',
                'titulo'      => 'Candidata para promoción anticipada a Piano Intermedio',
                'descripcion' => 'Nota pedagógica interna: El rendimiento y la madurez musical de Sofía justifican planificar su pase a repertorio de nivel intermedio el próximo ciclo.',
                'visible'     => false, // Privado para docente y administración
            ],

            // Miguel Ángel Salinas Pedraza (ALU-010 en GRP-GIT-01)
            [
                'grupo'       => 'GRP-GIT-01',
                'alumno'      => 'ALU-010',
                'fecha'       => '2026-10-20',
                'tipo'        => 'PROGRESO_TECNICO',
                'titulo'      => 'Dominio completo de cambios armónicos rápidos',
                'descripcion' => 'Miguel Ángel ejecuta cejillas y enlaces complejos con afinación perfecta y sonoridad uniforme.',
                'visible'     => true,
            ],
            [
                'grupo'       => 'GRP-GIT-01',
                'alumno'      => 'ALU-010',
                'fecha'       => '2026-11-07',
                'tipo'        => 'FELICITACION',
                'titulo'      => 'Seleccionado como solista para el Concierto de Fin de Año',
                'descripcion' => 'Por su destacada disciplina, asistencia perfecta (100%) y promedio de 96.4 pts, representará a la academia en el concierto anual.',
                'visible'     => true,
            ],

            // Mateo Cusi Huanca (ALU-002 en GRP-GIT-01)
            [
                'grupo'       => 'GRP-GIT-01',
                'alumno'      => 'ALU-002',
                'fecha'       => '2026-10-18',
                'tipo'        => 'RECOMENDACION',
                'titulo'      => 'Rutina diaria recomendada con metrónomo',
                'descripcion' => 'Se sugiere dedicar 15 minutos al inicio de cada práctica a escalas mayores y cromáticas a 75 bpm para estabilizar el cambio de acordes.',
                'visible'     => true,
            ],
            [
                'grupo'       => 'GRP-GIT-01',
                'alumno'      => 'ALU-002',
                'fecha'       => '2026-10-25',
                'tipo'        => 'DISCIPLINA',
                'titulo'      => 'Puntualidad en el inicio de la clase grupal',
                'descripcion' => 'Recordar la importancia de llegar 5 minutos antes para afinar el instrumento con calma antes del inicio formal de la clase.',
                'visible'     => true,
            ],

            // Valentina Chura Apaza (ALU-003 en GRP-PIA-02)
            [
                'grupo'       => 'GRP-PIA-02',
                'alumno'      => 'ALU-003',
                'fecha'       => '2026-10-22',
                'tipo'        => 'PROGRESO_TECNICO',
                'titulo'      => 'Sensibilidad en dinámicas y fraseo lírico',
                'descripcion' => 'Valentina comprende muy bien la forma de la sonatina y respeta las articulaciones staccato y ligaduras.',
                'visible'     => true,
            ],
            [
                'grupo'       => 'GRP-PIA-02',
                'alumno'      => 'ALU-003',
                'fecha'       => '2026-11-04',
                'tipo'        => 'RECOMENDACION',
                'titulo'      => 'Práctica concentrada de bajo Alberti',
                'descripcion' => 'Trabajar la mano izquierda por separado con pedal de resonancia sutil para evitar sobrecargar los compases impares.',
                'visible'     => true,
            ],

            // Camila Torrez Baldivia (ALU-005 en GRP-CAN-01)
            [
                'grupo'       => 'GRP-CAN-01',
                'alumno'      => 'ALU-005',
                'fecha'       => '2026-10-20',
                'tipo'        => 'FELICITACION',
                'titulo'      => 'Excelente afinación y proyección vocal',
                'descripcion' => 'Camila muestra un timbre dulce y gran resonancia natural en el registro medio sin forzar las cuerdas vocales.',
                'visible'     => true,
            ],
            [
                'grupo'       => 'GRP-CAN-01',
                'alumno'      => 'ALU-005',
                'fecha'       => '2026-11-04',
                'tipo'        => 'RECOMENDACION',
                'titulo'      => 'Ejercicios de articulación lingual y mandibular',
                'descripcion' => 'Continuar con trabalenguas y vocalizaciones con consonantes oclusivas para perfeccionar la dicción en lengua extranjera.',
                'visible'     => true,
            ],

            // Diego Condori Mamani (ALU-006 en GRP-PIA-01)
            [
                'grupo'       => 'GRP-PIA-01',
                'alumno'      => 'ALU-006',
                'fecha'       => '2026-10-18',
                'tipo'        => 'RECOMENDACION',
                'titulo'      => 'Refuerzo de lectura rítmica básica',
                'descripcion' => 'Presenta dudas en figuras subdivididas. Se coordinó entrega de partituras simplificadas para estudio en casa.',
                'visible'     => true,
            ],
            [
                'grupo'       => 'GRP-PIA-01',
                'alumno'      => 'ALU-006',
                'fecha'       => '2026-11-03',
                'tipo'        => 'DISCIPLINA',
                'titulo'      => 'Seguimiento por inasistencias y trámite de pausa académica',
                'descripcion' => 'Registro interno administrativo: Se coordinó con la familia la pausa temporal de su inscripción por viaje médico.',
                'visible'     => false, // Privado institucional
            ],
        ];

        foreach ($observacionesPlan as $obsData) {
            $grupo = Grupo::where('codigo_grupo', $obsData['grupo'])->first();
            if (! $grupo) {
                continue;
            }

            $inscripcion = Inscripcion::where('grupo_id', $grupo->id)
                ->whereHas('alumno.usuario', function ($q) use ($obsData) {
                    $q->where('codigo', $obsData['alumno']);
                })
                ->first();

            if (! $inscripcion) {
                continue;
            }

            ObservacionAcademica::updateOrCreate(
                [
                    'inscripcion_id' => $inscripcion->id,
                    'titulo'         => $obsData['titulo'],
                ],
                [
                    'docente_id'          => $grupo->docente_id,
                    'fecha'               => Carbon::parse($obsData['fecha']),
                    'tipo'                => $obsData['tipo'],
                    'descripcion'         => $obsData['descripcion'],
                    'visible_para_alumno' => $obsData['visible'],
                ]
            );
        }
    }
}

