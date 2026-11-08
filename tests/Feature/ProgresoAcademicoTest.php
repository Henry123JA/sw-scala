<?php

namespace Tests\Feature;

use App\Http\Controllers\ProgresoAcademicoController;
use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Rol;
use App\Models\SesionClase;
use App\Models\Usuario;
use App\Services\ProgresoAcademicoService;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProgresoAcademicoTest extends TestCase
{
    private ProgresoAcademicoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProgresoAcademicoService();
    }

    public function test_determinar_nivel_rendimiento_clasifica_correctamente(): void
    {
        // EXCELENTE: nota >= 90 y asistencia >= 85
        $this->assertSame('EXCELENTE', $this->service->determinarNivelRendimiento(95.0, 90.0));
        $this->assertSame('EXCELENTE', $this->service->determinarNivelRendimiento(90.0, 85.0));

        // FAVORABLE: nota >= 70 y asistencia >= 75
        $this->assertSame('FAVORABLE', $this->service->determinarNivelRendimiento(85.0, 80.0));
        $this->assertSame('FAVORABLE', $this->service->determinarNivelRendimiento(70.0, 75.0));

        // REGULAR: nota >= 51 y asistencia >= 65
        $this->assertSame('REGULAR', $this->service->determinarNivelRendimiento(65.0, 70.0));
        $this->assertSame('REGULAR', $this->service->determinarNivelRendimiento(51.0, 65.0));

        // EN RIESGO: nota < 51 o asistencia < 65
        $this->assertSame('EN RIESGO', $this->service->determinarNivelRendimiento(45.0, 90.0)); // Nota reprobatoria
        $this->assertSame('EN RIESGO', $this->service->determinarNivelRendimiento(85.0, 60.0)); // Asistencia baja
        $this->assertSame('EN RIESGO', $this->service->determinarNivelRendimiento(40.0, 50.0)); // Ambas deficientes

        // Sin notas registradas aún
        $this->assertSame('FAVORABLE', $this->service->determinarNivelRendimiento(null, 90.0));
        $this->assertSame('REGULAR', $this->service->determinarNivelRendimiento(null, 70.0));
        $this->assertSame('EN RIESGO', $this->service->determinarNivelRendimiento(null, 50.0));
    }

    public function test_formatear_progreso_inscripcion_calcula_asistencia_y_calificaciones(): void
    {
        // Simulamos objetos en memoria sin persistir en BD para test unitario rápido
        $inscripcion = new Inscripcion([
            'id' => 101,
            'estado' => 'ACTIVA',
            'fecha' => '2026-02-01',
            'fecha_inicio_clases' => '2026-02-10',
        ]);
        $inscripcion->id = 101;

        // Asistencias: 2 PRESENTE (100%), 1 ATRASO (80%), 1 FALTA (0%) -> (100 + 100 + 80 + 0) / 4 = 70.0%
        $sesion1 = new SesionClase(['fecha' => '2026-03-01']);
        $sesion2 = new SesionClase(['fecha' => '2026-03-08']);
        $sesion3 = new SesionClase(['fecha' => '2026-03-15']);
        $sesion4 = new SesionClase(['fecha' => '2026-03-22']);

        $asist1 = new Asistencia(['estado' => 'PRESENTE']);
        $asist1->id = 1;
        $asist1->setRelation('sesionClase', $sesion1);

        $asist2 = new Asistencia(['estado' => 'PRESENTE']);
        $asist2->id = 2;
        $asist2->setRelation('sesionClase', $sesion2);

        $asist3 = new Asistencia(['estado' => 'ATRASO']);
        $asist3->id = 3;
        $asist3->setRelation('sesionClase', $sesion3);

        $asist4 = new Asistencia(['estado' => 'FALTA']);
        $asist4->id = 4;
        $asist4->setRelation('sesionClase', $sesion4);

        $inscripcion->setRelation('asistencias', collect([$asist1, $asist2, $asist3, $asist4]));

        // Calificaciones:
        // Evaluacion 1: PRACTICA nota 90
        // Evaluacion 2: TEORIA nota 70
        // Evaluacion 3: RECITAL nota 80
        $ev1 = new Evaluacion(['id' => 1, 'nombre' => 'Ejercicios Escalas', 'tipo' => 'PRACTICA', 'fecha' => '2026-03-05', 'ponderacion' => 100]);
        $ev2 = new Evaluacion(['id' => 2, 'nombre' => 'Examen Escrito 1', 'tipo' => 'TEORIA', 'fecha' => '2026-03-12', 'ponderacion' => 100]);
        $ev3 = new Evaluacion(['id' => 3, 'nombre' => 'Presentación Bimestral', 'tipo' => 'RECITAL', 'fecha' => '2026-03-25', 'ponderacion' => 100]);

        $cal1 = new Calificacion(['evaluacion_id' => 1, 'nota' => 90.00]);
        $cal1->id = 11;
        $cal1->setRelation('evaluacion', $ev1);

        $cal2 = new Calificacion(['evaluacion_id' => 2, 'nota' => 70.00]);
        $cal2->id = 12;
        $cal2->setRelation('evaluacion', $ev2);

        $cal3 = new Calificacion(['evaluacion_id' => 3, 'nota' => 80.00]);
        $cal3->id = 13;
        $cal3->setRelation('evaluacion', $ev3);

        $inscripcion->setRelation('calificaciones', collect([$cal1, $cal2, $cal3]));

        $resultado = $this->service->formatearProgresoInscripcion($inscripcion, collect([$ev1, $ev2, $ev3]));

        // Verificación de Asistencias
        $this->assertSame(4, $resultado['asistencias']['total_sesiones']);
        $this->assertSame(2, $resultado['asistencias']['presentes']);
        $this->assertSame(1, $resultado['asistencias']['atrasos']);
        $this->assertSame(1, $resultado['asistencias']['faltas']);
        // (2*100 + 1*80)/4 = 280/4 = 70.0%
        $this->assertSame(70.0, $resultado['asistencias']['porcentaje']);

        // Verificación de Calificaciones
        // Promedio: (90 + 70 + 80) / 3 = 80.0
        $this->assertSame(80.0, $resultado['calificaciones']['promedio_general']);
        $this->assertSame(90.0, $resultado['calificaciones']['nota_maxima']);
        $this->assertSame(70.0, $resultado['calificaciones']['nota_minima']);
        $this->assertSame(3, $resultado['calificaciones']['evaluaciones_rendidas']);

        // Desglose por tipo
        $this->assertSame(90.0, $resultado['calificaciones']['desglose_por_tipo']['PRACTICA']['promedio']);
        $this->assertSame(70.0, $resultado['calificaciones']['desglose_por_tipo']['TEORIA']['promedio']);
        $this->assertSame(80.0, $resultado['calificaciones']['desglose_por_tipo']['RECITAL']['promedio']);
        $this->assertNull($resultado['calificaciones']['desglose_por_tipo']['AUDICION']['promedio']);

        // Rendimiento: nota 80.0, asistencia 70.0% -> Asistencia < 75 para FAVORABLE, pero nota >= 51 y asistencia >= 65 -> REGULAR
        $this->assertSame('REGULAR', $resultado['rendimiento']['nivel']);

        // Evolución cronológica ordenada
        $eventos = $resultado['evolucion_cronologica'];
        $this->assertCount(7, $eventos); // 4 asistencias + 3 calificaciones
        $fechas = array_column($eventos, 'fecha');
        $fechasOrdenadas = $fechas;
        sort($fechasOrdenadas);
        $this->assertSame($fechasOrdenadas, $fechas);
    }

    public function test_autorizar_grupo_valida_roles_y_asignacion_docente(): void
    {
        $grupo = new Grupo(['id' => 5, 'docente_id' => 20]);
        $grupo->id = 5;
        $grupo->docente_id = 20;

        $rolDocente = new Rol(['nombre' => 'Docente']);
        $rolAdmin = new Rol(['nombre' => 'Propietario']);
        $rolSecretaria = new Rol(['nombre' => 'Secretaria']);
        $rolAlumno = new Rol(['nombre' => 'Alumno']);

        // 1. Docente del grupo: Permitido
        $docenteAsignado = new Usuario(['id' => 20]);
        $docenteAsignado->id = 20;
        $docenteAsignado->setRelation('rol', $rolDocente);
        $this->service->autorizarGrupo($grupo, $docenteAsignado); // No exception

        // 2. Admin (Propietario): Permitido
        $admin = new Usuario(['id' => 1]);
        $admin->id = 1;
        $admin->setRelation('rol', $rolAdmin);
        $this->service->autorizarGrupo($grupo, $admin); // No exception

        // 3. Secretaria: Permitido
        $secretaria = new Usuario(['id' => 2]);
        $secretaria->id = 2;
        $secretaria->setRelation('rol', $rolSecretaria);
        $this->service->autorizarGrupo($grupo, $secretaria); // No exception

        // 4. Docente de otro grupo: Excepción 403
        $otroDocente = new Usuario(['id' => 30]);
        $otroDocente->id = 30;
        $otroDocente->setRelation('rol', $rolDocente);
        try {
            $this->service->autorizarGrupo($grupo, $otroDocente);
            $this->fail('Se esperaba HttpException 403 para docente no asignado.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // 5. Alumno: Excepción 403
        $alumno = new Usuario(['id' => 99]);
        $alumno->id = 99;
        $alumno->setRelation('rol', $rolAlumno);
        try {
            $this->service->autorizarGrupo($grupo, $alumno);
            $this->fail('Se esperaba HttpException 403 para rol Alumno.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
