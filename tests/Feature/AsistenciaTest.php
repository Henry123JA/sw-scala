<?php

namespace Tests\Feature;

use App\Http\Controllers\AsistenciaController;
use App\Http\Requests\Asistencia\GuardarAsistenciaRequest;
use App\Models\Asistencia;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AsistenciaService;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AsistenciaTest extends TestCase
{
    private AsistenciaService $service;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        // An isolated schema is rolled back with all fixtures after each test.
        DB::statement('CREATE SCHEMA hu8_'.getmypid());
        DB::statement('SET LOCAL search_path TO hu8_'.getmypid());
        DB::statement('CREATE TABLE curso (id bigint PRIMARY KEY, nombre text, eliminado boolean DEFAULT false)');
        DB::statement('CREATE TABLE docente (id bigint PRIMARY KEY)');
        DB::statement('CREATE TABLE grupo (id bigint PRIMARY KEY, docente_id bigint, curso_id bigint, codigo_grupo text, eliminado boolean DEFAULT false)');
        DB::statement('CREATE TABLE usuario (id bigint PRIMARY KEY, nombres text, apellidos text, eliminado boolean DEFAULT false)');
        DB::statement('CREATE TABLE alumno (id bigint PRIMARY KEY)');
        DB::statement('CREATE TABLE inscripcion (id bigint PRIMARY KEY, grupo_id bigint, alumno_id bigint, estado text)');
        (require __DIR__.'/../../database/migrations/2026_11_07_000001_create_sesion_clase_table.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_07_000002_create_asistencia_table.php')->up();

        DB::table('curso')->insert([
            ['id' => 1, 'nombre' => 'Piano Básico'],
            ['id' => 2, 'nombre' => 'Guitarra'],
        ]);
        DB::table('docente')->insert([
            ['id' => 10],
            ['id' => 20],
        ]);
        DB::table('grupo')->insert([
            ['id' => 1, 'docente_id' => 10, 'curso_id' => 1, 'codigo_grupo' => 'G-101'],
            ['id' => 2, 'docente_id' => 20, 'curso_id' => 2, 'codigo_grupo' => 'G-201'],
        ]);
        DB::table('usuario')->insert([
            ['id' => 1, 'nombres' => 'Ana', 'apellidos' => 'López'],
            ['id' => 2, 'nombres' => 'Carlos', 'apellidos' => 'Pérez'],
            ['id' => 10, 'nombres' => 'Profesor', 'apellidos' => 'Docente'],
        ]);
        DB::table('alumno')->insert([
            ['id' => 1],
            ['id' => 2],
        ]);
        DB::table('inscripcion')->insert([
            ['id' => 1, 'grupo_id' => 1, 'alumno_id' => 1, 'estado' => 'ACTIVA'],
            ['id' => 2, 'grupo_id' => 2, 'alumno_id' => 1, 'estado' => 'ACTIVA'],
            ['id' => 3, 'grupo_id' => 1, 'alumno_id' => 2, 'estado' => 'ACTIVA'],
        ]);

        $this->usuario = new Usuario;
        $this->usuario->id = 10;
        $this->usuario->nombres = 'Profesor';
        $this->usuario->apellidos = 'Docente';
        $this->usuario->setRelation('rol', new Rol(['nombre' => 'Docente']));
        $this->service = new AsistenciaService;
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    public function test_tabla_usuario_no_contiene_columna_tema_id(): void
    {
        $this->assertFalse(
            Schema::hasColumn('usuario', 'tema_id'),
            'La tabla usuario no debe incluir la columna obsoleta tema_id.'
        );
    }

    public function test_guarda_actualiza_y_lista_sin_duplicar(): void
    {
        $sesion = $this->service->seleccionarSesion(['grupo_id' => 1, 'fecha' => '2026-11-07'], $this->usuario);
        $alumnos = $this->service->listarAlumnos($sesion->id, $this->usuario)['alumnos'];
        $ana = $alumnos->firstWhere('inscripcion_id', 1);
        $this->assertNull($ana['estado']);

        $this->service->guardar($sesion->id, [['inscripcion_id' => 1, 'estado' => 'PRESENTE']], $this->usuario);
        $this->service->guardar($sesion->id, [['inscripcion_id' => 1, 'estado' => 'ATRASO', 'observacion' => 'Diez minutos']], $this->usuario);
        $this->assertSame(1, Asistencia::count());

        $fila = $this->service->listarAlumnos($sesion->id, $this->usuario)['alumnos']->firstWhere('inscripcion_id', 1);
        $this->assertSame('ATRASO', $fila['estado']);
        $this->assertSame('Diez minutos', $fila['observacion']);
        $this->assertSame($sesion->id, $this->service->seleccionarSesion(['grupo_id' => 1, 'fecha' => '2026-11-07'], $this->usuario)->id);
    }

    public function test_guardar_redirige_a_index_con_session_flash_success(): void
    {
        $sesion = $this->service->seleccionarSesion(['grupo_id' => 1, 'fecha' => '2026-11-07'], $this->usuario);

        $request = GuardarAsistenciaRequest::create(
            route('asistencias.guardar', $sesion->id),
            'PUT',
            [
                'asistencias' => [
                    ['inscripcion_id' => 1, 'estado' => 'PRESENTE', 'observacion' => 'Puntual'],
                ],
            ]
        );
        $request->setUserResolver(fn () => $this->usuario);
        $request->setLaravelSession(app('session.store'));

        $controller = new AsistenciaController($this->service);
        $response = $controller->guardar($request, $sesion->id);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('asistencias.index'), $response->getTargetUrl());
        $this->assertSame('Asistencia registrada correctamente.', session('success'));
        $this->assertSame(1, Asistencia::where('sesion_clase_id', $sesion->id)->where('estado', 'PRESENTE')->count());
    }

    public function test_alumno_tiene_acceso_a_index_y_ve_unicamente_sus_registros(): void
    {
        // 1. Crear sesión de clase para el grupo 1
        $sesion = $this->service->seleccionarSesion(['grupo_id' => 1, 'fecha' => '2026-11-07'], $this->usuario);

        // 2. Registrar asistencia para Alumno 1 (inscripcion 1) y Alumno 2 (inscripcion 3)
        $this->service->guardar($sesion->id, [
            ['inscripcion_id' => 1, 'estado' => 'PRESENTE', 'observacion' => 'Asistencia Alumno 1'],
            ['inscripcion_id' => 3, 'estado' => 'FALTA', 'observacion' => 'Falta Alumno 2'],
        ], $this->usuario);

        // 3. Crear usuario autenticado Alumno 1
        $alumno1 = new Usuario;
        $alumno1->id = 1;
        $alumno1->nombres = 'Ana';
        $alumno1->apellidos = 'López';
        $alumno1->setRelation('rol', new Rol(['nombre' => 'Alumno']));

        $request = Request::create(route('asistencias.index'), 'GET');
        $request->setUserResolver(fn () => $alumno1);
        $request->headers->set('X-Inertia', 'true');

        $controller = new AsistenciaController($this->service);
        $response = $controller->index($request);

        $this->assertInstanceOf(InertiaResponse::class, $response);

        // Extraer props pasadas al componente Inertia
        $page = $response->toResponse($request)->getData(true);
        $props = $page['props'];

        // Verificar que el usuario Alumno ve su vista personal
        $this->assertTrue($props['es_alumno']);

        // Verificar que el historial solo contiene los registros de Alumno 1
        $this->assertCount(1, $props['historial']);
        $this->assertSame('PRESENTE', $props['historial'][0]['estado']);
        $this->assertSame('Asistencia Alumno 1', $props['historial'][0]['observacion']);

        // Verificar que ningún registro de Alumno 2 aparece en el historial de Alumno 1
        $observaciones = array_column($props['historial'], 'observacion');
        $this->assertNotContains('Falta Alumno 2', $observaciones);

        // Verificar que el resumen de cursos corresponde a las inscripciones del Alumno 1
        $inscripcionesResumen = array_column($props['resumen_cursos'], 'inscripcion_id');
        $this->assertContains(1, $inscripcionesResumen);
        $this->assertNotContains(3, $inscripcionesResumen);
    }

    public function test_rechaza_lote_de_otro_grupo_sin_guardado_parcial(): void
    {
        $sesion = $this->service->seleccionarSesion(['grupo_id' => 1, 'fecha' => '2026-11-07'], $this->usuario);
        try {
            $this->service->guardar($sesion->id, [
                ['inscripcion_id' => 1, 'estado' => 'PRESENTE'],
                ['inscripcion_id' => 2, 'estado' => 'FALTA'],
            ], $this->usuario);
            $this->fail('Debe rechazar inscripciones de otro grupo.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('asistencias.1.inscripcion_id', $e->errors());
            $this->assertSame(0, Asistencia::count());
        }
    }

    public function test_docente_no_puede_abrir_grupo_ajeno(): void
    {
        $this->expectException(HttpException::class);
        $this->service->seleccionarSesion(['grupo_id' => 2, 'fecha' => '2026-11-07'], $this->usuario);
    }

    public function test_validacion_rechaza_duplicados_estados_invalidos_y_observaciones_invalidas(): void
    {
        $validator = Validator::make(['asistencias' => [
            ['inscripcion_id' => 1, 'estado' => 'OTRO', 'observacion' => []],
            ['inscripcion_id' => 1, 'estado' => 'PRESENTE'],
        ]], (new GuardarAsistenciaRequest)->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('asistencias.0.estado', $validator->errors()->toArray());
        $this->assertArrayHasKey('asistencias.0.inscripcion_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('asistencias.0.observacion', $validator->errors()->toArray());
    }

    public function test_eliminar_sesion_elimina_asistencias_en_cascada(): void
    {
        $sesion = $this->service->seleccionarSesion(['grupo_id' => 1, 'fecha' => '2026-11-07'], $this->usuario);
        $this->service->guardar($sesion->id, [['inscripcion_id' => 1, 'estado' => 'LICENCIA']], $this->usuario);
        $sesion->delete();
        $this->assertSame(0, Asistencia::count());
    }
}
