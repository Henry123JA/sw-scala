<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\ObservacionAcademica;
use App\Models\Usuario;
use App\Services\SeguimientoAcademicoService;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SeguimientoAcademicoTest extends TestCase
{
    private SeguimientoAcademicoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SeguimientoAcademicoService::class);
    }

    public function test_docente_puede_ver_ficha_de_su_alumno_pero_no_de_otro_docente(): void
    {
        DB::beginTransaction();
        try {
            // Grupo 1 tiene como docente a Carlos Alberto (ID 3)
            $docenteCarlos = Usuario::with('rol')->findOrFail(3);
            // Grupo 3 tiene como docente a Roberto (ID 5)
            $docenteRoberto = Usuario::with('rol')->findOrFail(5);

            // Inscripcion 1 pertenece al Grupo 1 (Docente Carlos)
            $fichaPropia = $this->service->obtenerFichaIntegral(1, $docenteCarlos);
            $this->assertSame(1, $fichaPropia['inscripcion']['id']);
            $this->assertNotEmpty($fichaPropia['alumno']['nombres']);
            $this->assertNotEmpty($fichaPropia['grupo']['codigo_grupo']);

            // Docente Roberto NO debe poder ver la inscripción 1
            $this->expectException(HttpException::class);
            $this->service->obtenerFichaIntegral(1, $docenteRoberto);
        } finally {
            DB::rollBack();
        }
    }

    public function test_alumno_solo_puede_ver_su_propia_ficha(): void
    {
        DB::beginTransaction();
        try {
            // Inscripcion 1 pertenece al Alumno Sofía (ID 8)
            $alumnoSofia = Usuario::with('rol')->findOrFail(8);
            // Inscripcion 2 pertenece al Alumno Mateo (ID 9)

            // Sofía ve su propia ficha
            $ficha = $this->service->obtenerFichaIntegral(1, $alumnoSofia);
            $this->assertSame(1, $ficha['inscripcion']['id']);

            // Sofía intenta ver la ficha de Mateo (ID 2) => 403
            $this->expectException(HttpException::class);
            $this->service->obtenerFichaIntegral(2, $alumnoSofia);
        } finally {
            DB::rollBack();
        }
    }

    public function test_alumno_no_ve_observaciones_privadas(): void
    {
        DB::beginTransaction();
        try {
            $docenteCarlos = Usuario::with('rol')->findOrFail(3);
            $alumnoSofia = Usuario::with('rol')->findOrFail(8);

            // Limpiar observaciones previas para aislamiento del test
            ObservacionAcademica::where('inscripcion_id', 1)->delete();

            // Crear 1 observación pública y 1 privada
            ObservacionAcademica::create([
                'inscripcion_id' => 1,
                'docente_id' => 3,
                'fecha' => '2026-10-01',
                'tipo' => ObservacionAcademica::TIPO_PROGRESO_TECNICO,
                'titulo' => 'Observación Pública',
                'descripcion' => 'Recomendación abierta al estudiante',
                'visible_para_alumno' => true,
            ]);

            ObservacionAcademica::create([
                'inscripcion_id' => 1,
                'docente_id' => 3,
                'fecha' => '2026-10-02',
                'tipo' => ObservacionAcademica::TIPO_RECOMENDACION,
                'titulo' => 'Observación Confidencial',
                'descripcion' => 'Solo para cuerpo docente y secretaría',
                'visible_para_alumno' => false,
            ]);

            // Docente ve ambas
            $fichaDocente = $this->service->obtenerFichaIntegral(1, $docenteCarlos);
            $this->assertCount(2, $fichaDocente['observaciones']);

            // Alumno solo ve la pública
            $fichaAlumno = $this->service->obtenerFichaIntegral(1, $alumnoSofia);
            $this->assertCount(1, $fichaAlumno['observaciones']);
            $this->assertTrue($fichaAlumno['observaciones'][0]['visible_para_alumno']);
            $this->assertSame('Observación Pública', $fichaAlumno['observaciones'][0]['titulo']);
        } finally {
            DB::rollBack();
        }
    }
}

