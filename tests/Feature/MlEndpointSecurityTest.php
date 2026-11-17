<?php

namespace Tests\Feature;

use App\Models\AlertaSeguimiento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MlEndpointSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ml.integration_token' => 'token-servicio-solo-pruebas']);
    }

    public function test_sin_token_o_token_invalido_no_lee_ni_escribe(): void
    {
        $before = AlertaSeguimiento::query()->get()->map->getRawOriginal()->all();
        $this->getJson('/api/ml/dataset-alumnos')->assertUnauthorized();
        $this->postJson('/api/ml/webhook-alertas', [])->assertUnauthorized();
        $this->withToken('invalido')->getJson('/api/ml/dataset-alumnos')->assertUnauthorized();
        $this->postJson('/api/ml/webhook-alertas', [])->assertUnauthorized();
        $this->assertEquals($before, AlertaSeguimiento::query()->get()->map->getRawOriginal()->all());
    }

    public function test_sesion_docente_y_grupo_forzado_no_autorizan_integracion_global(): void
    {
        $alerta = AlertaSeguimiento::with('inscripcion.grupo')->findOrFail(7);
        $docente = Usuario::with('rol')->findOrFail($alerta->inscripcion->grupo->docente_id);
        $this->assertSame('Docente', $docente->rol->nombre);
        $this->actingAs($docente)->getJson('/api/ml/dataset-alumnos?grupo_id=1')->assertUnauthorized();
        $this->postJson('/api/ml/webhook-alertas', [])->assertUnauthorized();
        $this->getJson('/riesgo/alertas/7')->assertOk();
    }

    public function test_credencial_de_servicio_valida_mantiene_el_contrato_n8n(): void
    {
        $this->withToken('token-servicio-solo-pruebas');
        $this->getJson('/api/ml/dataset-alumnos')->assertOk()->assertJsonStructure(['dataset', 'total_registros']);
        $this->postJson('/api/ml/webhook-alertas', [
            'total_registros' => 0, 'predicciones_calculadas' => 0, 'resultados' => [],
        ])->assertOk()->assertJson(['procesadas' => 0]);
    }

    public function test_sin_configuracion_falla_cerrado_incluso_con_bearer(): void
    {
        config(['services.ml.integration_token' => '']);
        $this->withToken('token-servicio-solo-pruebas')->getJson('/api/ml/dataset-alumnos')
            ->assertStatus(503);
        $this->postJson('/api/ml/webhook-alertas', [])->assertStatus(503);
    }

    public function test_token_en_query_no_es_una_credencial_valida(): void
    {
        $this->getJson('/api/ml/dataset-alumnos?token=token-servicio-solo-pruebas')->assertUnauthorized();
    }
}
