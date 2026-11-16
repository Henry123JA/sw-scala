<?php

namespace Tests\Feature;

use App\Models\AlertaSeguimiento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class N8nBatchWebhookTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ml.integration_token' => 'token-integracion-pruebas']);
        $this->withToken('token-integracion-pruebas');
    }

    private function lote(): array
    {
        return json_decode(file_get_contents(base_path('../iaService/data/predicciones_fastapi.json')),
            true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_lote_real_conserva_scores_version_y_excluye_cinco_inscripciones(): void
    {
        $lote = $this->lote();
        $before = AlertaSeguimiento::count();
        $this->postJson('/api/ml/webhook-alertas', $lote)->assertOk()
            ->assertJson(['procesadas' => 7, 'omitidas' => 5, 'origen' => 'N8N_AUTO']);
        foreach ($lote['resultados'] as $item) {
            if ($item['estado_prediccion'] !== 'CALCULADA') {
                continue;
            }
            $alerta = AlertaSeguimiento::where('inscripcion_id', $item['inscripcion_id'])->latest('id')->firstOrFail();
            $this->assertSame('N8N_AUTO', $alerta->origen);
            $this->assertSame($item['version_modelo'], $alerta->versionModelo->version);
            $this->assertEquals($item['score_riesgo'], $alerta->score_riesgo);
            $this->assertEquals($item['factores_riesgo'], $alerta->factores_riesgo);
            $this->assertTrue($alerta->metricas_base['prediccion_ml']['experimental']);
            $this->assertSame('ML_API', $alerta->metricas_base['prediccion_ml']['proveedor']);
        }
        $this->postJson('/api/ml/webhook-alertas', $lote)->assertOk()->assertJson(['procesadas' => 7]);
        $this->assertSame($before, AlertaSeguimiento::count());
    }

    public function test_error_en_ultimo_elemento_revierte_el_lote_completo(): void
    {
        $lote = $this->lote();
        $primera = AlertaSeguimiento::where('inscripcion_id', 1)->firstOrFail();
        $original = $primera->getRawOriginal();
        $lote['resultados'][0]['score_riesgo'] = 99;
        $lote['resultados'][0]['nivel_riesgo'] = 'ALTO';
        $lote['resultados'][11]['nivel_riesgo'] = 'ALTO'; // score BAJO: contrato inconsistente.
        $this->postJson('/api/ml/webhook-alertas', $lote)->assertUnprocessable();
        $this->assertEquals($original, $primera->fresh()->getRawOriginal());
    }

    public function test_lote_vacio_no_dispara_analisis_local(): void
    {
        $before = AlertaSeguimiento::query()->pluck('updated_at', 'id')->all();
        $this->postJson('/api/ml/webhook-alertas', [
            'total_registros' => 0, 'predicciones_calculadas' => 0, 'resultados' => [],
        ])->assertOk()->assertJson(['procesadas' => 0, 'omitidas' => 0]);
        $this->assertEquals($before, AlertaSeguimiento::query()->pluck('updated_at', 'id')->all());
    }

    public function test_rechaza_totales_incorrectos_y_duplicados(): void
    {
        $lote = $this->lote();
        $lote['total_registros'] = 1;
        $this->postJson('/api/ml/webhook-alertas', $lote)->assertUnprocessable();
        $lote = $this->lote();
        $lote['resultados'][1]['inscripcion_id'] = $lote['resultados'][0]['inscripcion_id'];
        $this->postJson('/api/ml/webhook-alertas', $lote)->assertUnprocessable();
    }
}
