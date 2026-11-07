<?php

namespace Tests\Feature;

use App\Http\Requests\Asistencia\GuardarAsistenciaRequest;
use App\Models\Asistencia;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AsistenciaService;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
        DB::statement('CREATE TABLE grupo (id bigint PRIMARY KEY, docente_id bigint, curso_id bigint, eliminado boolean DEFAULT false)');
        DB::statement('CREATE TABLE curso (id bigint PRIMARY KEY, eliminado boolean DEFAULT false)');
        DB::statement('CREATE TABLE usuario (id bigint PRIMARY KEY, nombres text, apellidos text, eliminado boolean DEFAULT false)');
        DB::statement('CREATE TABLE alumno (id bigint PRIMARY KEY)');
        DB::statement('CREATE TABLE inscripcion (id bigint PRIMARY KEY, grupo_id bigint, alumno_id bigint, estado text)');
        (require __DIR__.'/../../database/migrations/2026_11_07_000001_create_sesion_clase_table.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_07_000002_create_asistencia_table.php')->up();
        DB::table('grupo')->insert([['id' => 1, 'docente_id' => 10], ['id' => 2, 'docente_id' => 20]]);
        DB::table('usuario')->insert(['id' => 1, 'nombres' => 'Ana', 'apellidos' => 'López']);
        DB::table('alumno')->insert(['id' => 1]);
        DB::table('inscripcion')->insert([
            ['id' => 1, 'grupo_id' => 1, 'alumno_id' => 1, 'estado' => 'ACTIVA'],
            ['id' => 2, 'grupo_id' => 2, 'alumno_id' => 1, 'estado' => 'ACTIVA'],
        ]);
        $this->usuario = new Usuario;
        $this->usuario->id = 10;
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

    public function test_guarda_actualiza_y_lista_sin_duplicar(): void
    {
        $sesion = $this->service->seleccionarSesion(['grupo_id' => 1, 'fecha' => '2026-11-07'], $this->usuario);
        $this->assertNull($this->service->listarAlumnos($sesion->id, $this->usuario)['alumnos'][0]['estado']);
        $this->service->guardar($sesion->id, [['inscripcion_id' => 1, 'estado' => 'PRESENTE']], $this->usuario);
        $this->service->guardar($sesion->id, [['inscripcion_id' => 1, 'estado' => 'ATRASO', 'observacion' => 'Diez minutos']], $this->usuario);
        $this->assertSame(1, Asistencia::count());
        $fila = $this->service->listarAlumnos($sesion->id, $this->usuario)['alumnos'][0];
        $this->assertSame('ATRASO', $fila['estado']);
        $this->assertSame('Diez minutos', $fila['observacion']);
        $this->assertSame($sesion->id, $this->service->seleccionarSesion(['grupo_id' => 1, 'fecha' => '2026-11-07'], $this->usuario)->id);
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
