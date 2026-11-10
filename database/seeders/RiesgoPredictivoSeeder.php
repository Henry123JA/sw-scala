<?php

namespace Database\Seeders;

use App\Models\AlertaSeguimiento;
use App\Models\Inscripcion;
use App\Models\IntervencionSeguimiento;
use App\Models\Menu;
use App\Models\Usuario;
use App\Models\VersionModelo;
use App\Services\RiesgoPredictivoService;
use Illuminate\Database\Seeder;

class RiesgoPredictivoSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Registrar ruta /riesgo en catálogo de menús para contador de visitas (pagina_visitada)
        Menu::updateOrCreate(
            ['ruta' => '/riesgo'],
            [
                'nombre'    => 'Riesgo Predictivo',
                'icono'     => 'BrainCircuit',
                'orden'     => 10,
                'padre_id'  => null,
                'eliminado' => false,
            ]
        );

        // 1. Versiones del Modelo Predictivo (HU22)
        VersionModelo::updateOrCreate(
            ['version' => 'v1.0.0'],
            [
                'nombre'              => 'Regresión Logística Base',
                'algoritmo'           => 'Logistic Regression (L2 Regularization)',
                'accuracy'            => 82.50,
                'f1_score'            => 80.10,
                'fecha_entrenamiento' => '2026-09-15',
                'url_endpoint'        => 'http://127.0.0.1:8001/api/v1/predict',
                'activo'              => false,
                'descripcion'         => 'Modelo lineal inicial entrenado sobre variables básicas de asistencia y promedio de notas.',
            ]
        );

        $modeloActivo = VersionModelo::updateOrCreate(
            ['version' => 'v1.1.0'],
            [
                'nombre'              => 'Random Forest Classifier - SCALA',
                'algoritmo'           => 'Random Forest + XAI Feature Importance',
                'accuracy'            => 89.40,
                'f1_score'            => 88.20,
                'fecha_entrenamiento' => '2026-11-01',
                'url_endpoint'        => 'http://127.0.0.1:8001/api/v1/predict',
                'activo'              => true,
                'descripcion'         => 'Modelo ensemble calibrado que integra regularidad de asistencia, tendencia temporal de calificaciones y bitácora pedagógica.',
            ]
        );

        // Asegurar que solo v1.1.0 esté activo
        VersionModelo::where('id', '!=', $modeloActivo->id)->update(['activo' => false]);

        // 2. Ejecutar análisis predictivo para todas las inscripciones (HU15, HU16, HU17, HU18)
        /** @var RiesgoPredictivoService $service */
        $service = app(RiesgoPredictivoService::class);
        $service->analizarAcademia(null, null, 'MOTOR_LOCAL');

        // 3. Intervenciones Pedagógicas Realistas (HU20)
        $intervencionesSeed = [
            [
                'alumno_codigo' => 'ALU-006', // Diego Condori Mamani (GRP-PIA-01)
                'grupo_codigo'  => 'GRP-PIA-01',
                'docente_email' => 'carlos.flores@academia.com',
                'fecha'         => '2026-11-04',
                'tipo'          => 'LLAMADA_SEGUIMIENTO',
                'titulo'        => 'Contacto con familia por pausa médica y plan de nivelación en casa',
                'descripcion'   => 'Se conversó con los padres de Diego para acompañar su pausa temporal y enviarle ejercicios rítmicos grabados en video de baja exigencia física.',
                'compromiso'    => 'Practicar 10 minutos diarios de lectura rítmica y confirmar fecha de reincorporación el 18 de noviembre.',
                'resultado'     => 'EN_PROCESO',
                'estado_alerta' => 'EN_ATENCION',
            ],
            [
                'alumno_codigo' => 'ALU-002', // Mateo Cusi Huanca (GRP-GIT-01)
                'grupo_codigo'  => 'GRP-GIT-01',
                'docente_email' => 'roberto.chavez@academia.com',
                'fecha'         => '2026-11-05',
                'tipo'          => 'TUTORIA_INDIVIDUAL',
                'titulo'        => 'Sesión de refuerzo en cambios de acordes con cejilla y puntualidad',
                'descripcion'   => 'Se realizó una tutoría personalizada de 25 minutos previa a la clase grupal para corregir postura del pulgar izquierdo en Fa mayor y revisar organización de horarios.',
                'compromiso'    => 'Ingresar 5 minutos antes de la hora de inicio y completar rutina de metrónomo a 75 bpm.',
                'resultado'     => 'MEJORA_EVIDENTE',
                'estado_alerta' => 'EN_ATENCION',
            ],
            [
                'alumno_codigo' => 'ALU-003', // Valentina Chura Apaza (GRP-GIT-02)
                'grupo_codigo'  => 'GRP-GIT-02',
                'docente_email' => 'roberto.chavez@academia.com',
                'fecha'         => '2026-11-06',
                'tipo'          => 'REFUERZO_TECNICO',
                'titulo'        => 'Reposición de repertorio tras inasistencia en clase de ensamble',
                'descripcion'   => 'Se revisaron los compases de arpegio p-i-m-a pendientes por la falta del 22 de octubre para asegurar el acople con el dúo instrumental.',
                'compromiso'    => 'Enviar audio de práctica de la primera voz antes del martes.',
                'resultado'     => 'MEJORA_EVIDENTE',
                'estado_alerta' => 'RESUELTA',
            ],
        ];

        foreach ($intervencionesSeed as $item) {
            $inscripcion = Inscripcion::whereHas('alumno.usuario', fn ($q) => $q->where('codigo', $item['alumno_codigo']))
                ->whereHas('grupo', fn ($q) => $q->where('codigo_grupo', $item['grupo_codigo']))
                ->first();

            if (! $inscripcion) {
                continue;
            }

            $alerta = AlertaSeguimiento::where('inscripcion_id', $inscripcion->id)->first();
            if (! $alerta) {
                continue;
            }

            $usuarioResponsable = Usuario::where('email', $item['docente_email'])->first()
                ?? Usuario::first();

            if (! $usuarioResponsable) {
                continue;
            }

            IntervencionSeguimiento::updateOrCreate(
                [
                    'alerta_id' => $alerta->id,
                    'titulo'    => $item['titulo'],
                ],
                [
                    'inscripcion_id' => $inscripcion->id,
                    'usuario_id'     => $usuarioResponsable->id,
                    'fecha'          => $item['fecha'],
                    'tipo'           => $item['tipo'],
                    'descripcion'    => $item['descripcion'],
                    'compromiso'     => $item['compromiso'],
                    'resultado'      => $item['resultado'],
                ]
            );

            $alerta->update(['estado' => $item['estado_alerta']]);
        }
    }
}

