<?php

namespace Tests\Feature;

use App\Models\AlertaSeguimiento;
use App\Models\Inscripcion;
use App\Services\RiesgoPredictivoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FastApiIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private function elegible(): Inscripcion
    {
        $service = app(RiesgoPredictivoService::class);
        return Inscripcion::all()->first(function ($inscripcion) use ($service) {
            $f = $service->extraerFeatures($inscripcion);
            return $inscripcion->estado !== 'CANCELADA'
                && $f['total_sesiones'] >= 3 && $f['evaluaciones_rendidas'] >= 1;
        }) ?? throw new \RuntimeException('Se requiere una inscripcion con seguimiento');
    }

    private function respuesta(Inscripcion $inscripcion): array
    {
        return ['inscripcion_id' => $inscripcion->id, 'estado_prediccion' => 'CALCULADA',
            'score_riesgo' => 45.62, 'nivel_riesgo' => 'MEDIO',
            'version_modelo' => 'demo-sintetica-v2', 'experimental' => true,
            'entrenado_con' => 'SINTETICO', 'estimacion_abandono_pct' => 61.54,
            'factores_riesgo' => [], 'resumen_explicativo' => 'Modelo experimental sintetico.'];
    }

    public function test_guarda_score_version_metadata_y_no_duplica_alertas(): void
    {
        config(['services.ml.enabled' => true]);
        $inscripcion = $this->elegible();
        Http::fake(['*' => Http::response($this->respuesta($inscripcion))]);
        $service = app(RiesgoPredictivoService::class);
        $primera = $service->analizarInscripcion($inscripcion);
        $segunda = $service->analizarInscripcion($inscripcion);
        $this->assertSame($primera->id, $segunda->id);
        $this->assertSame('ML_API', $segunda->origen);
        $this->assertEquals(45.62, $segunda->score_riesgo);
        $this->assertSame([], $segunda->factores_riesgo);
        $this->assertSame('demo-sintetica-v2', $segunda->versionModelo->version);
        $this->assertTrue($segunda->metricas_base['prediccion_ml']['experimental']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/v1/predict')
            && $request['inscripcion_id'] === $inscripcion->id
            && isset($request['features']['total_obs_alerta']));
    }

    public function test_fallo_o_payload_invalido_se_identifica_como_motor_local(): void
    {
        config(['services.ml.enabled' => true]);
        $inscripcion = $this->elegible();
        $service = app(RiesgoPredictivoService::class);
        Http::fake(['*' => Http::response([], 503)]);
        $this->assertSame('MOTOR_LOCAL', $service->analizarInscripcion($inscripcion, 'ML_API')->origen);
        $payload = $this->respuesta($inscripcion);
        $payload['nivel_riesgo'] = 'ALTO';
        Http::fake(['*' => Http::response($payload)]);
        $this->assertSame('MOTOR_LOCAL', $service->analizarInscripcion($inscripcion)->origen);
    }

    public function test_retiros_y_datos_insuficientes_no_generan_alertas_ni_consultas(): void
    {
        config(['services.ml.enabled' => true]);
        Http::fake();
        $service = app(RiesgoPredictivoService::class);
        $excluidas = Inscripcion::all()->filter(function ($inscripcion) use ($service) {
            $f = $service->extraerFeatures($inscripcion);
            return $inscripcion->estado === 'CANCELADA'
                || $f['total_sesiones'] < 3 || $f['evaluaciones_rendidas'] < 1;
        });
        $this->assertNotEmpty($excluidas);
        $antes = AlertaSeguimiento::count();
        foreach ($excluidas as $inscripcion) {
            $this->assertNull($service->analizarInscripcion($inscripcion));
        }
        $this->assertEquals($antes, AlertaSeguimiento::count());
        Http::assertNothingSent();
    }
}
