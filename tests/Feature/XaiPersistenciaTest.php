<?php

namespace Tests\Feature;

use App\Models\AlertaSeguimiento;
use App\Services\RiesgoPredictivoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class XaiPersistenciaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ml.integration_token' => 'token-integracion-pruebas']);
        $this->withToken('token-integracion-pruebas');
    }

    public function test_webhook_y_detalle_conservan_aportes_y_metadata_shap(): void
    {
        $batch = json_decode(file_get_contents(base_path('../iaService/data/predicciones_xai.json')),
            true, 512, JSON_THROW_ON_ERROR);
        $item = collect($batch['resultados'])->firstWhere('inscripcion_id', 7);
        $this->postJson('/api/ml/webhook-alertas', $batch)->assertOk()
            ->assertJson(['procesadas' => 7, 'omitidas' => 5]);
        $alerta = AlertaSeguimiento::where('inscripcion_id', 7)->latest('id')->firstOrFail();
        $this->assertEquals($item['factores_riesgo'], $alerta->factores_riesgo);
        $this->assertSame($item['resumen_explicativo'], $alerta->resumen_explicativo);
        $this->assertSame('TREE_SHAP', $alerta->metricas_base['prediccion_ml']['explicacion_modelo']['metodo']);
        $this->assertSame($item['acciones_sugeridas'], $alerta->metricas_base['prediccion_ml']['acciones_sugeridas']);
        $this->assertContains('REDUCE', array_column($alerta->factores_riesgo, 'direccion'));
        $formatted = app(RiesgoPredictivoService::class)->formatearAlerta($alerta);
        $this->assertEquals($item['factores_riesgo'], $formatted['factores_riesgo']);
        $this->assertSame('TREE_SHAP', $formatted['metricas_base']['prediccion_ml']['explicacion_modelo']['metodo']);
    }
}
