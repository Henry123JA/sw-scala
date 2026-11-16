<?php

namespace Tests\Feature;

use App\Models\AlertaSeguimiento;
use App\Models\Inscripcion;
use App\Models\IntervencionSeguimiento;
use App\Models\Usuario;
use App\Models\VersionModelo;
use App\Services\RiesgoPredictivoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RiesgoPredictivoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ml.integration_token' => 'token-integracion-pruebas']);
        $this->withToken('token-integracion-pruebas');
        // Estas pruebas verifican heuristicas locales; la API tiene su propia suite.
        config(['services.ml.enabled' => false]);
    }

    public function test_motor_predictivo_extrae_features_y_clasifica_con_explicabilidad_xai(): void
    {
        $inscripcion = Inscripcion::with(['alumno.usuario', 'grupo.curso'])->first();
        $this->assertNotNull($inscripcion, 'Debe existir al menos una inscripción para analizar.');

        /** @var RiesgoPredictivoService $service */
        $service = app(RiesgoPredictivoService::class);

        $features = $service->extraerFeatures($inscripcion);
        $this->assertArrayHasKey('porcentaje_asistencia', $features);
        $this->assertArrayHasKey('tendencia_notas', $features);
        $this->assertArrayHasKey('faltas_injustificadas', $features);

        $alerta = $service->analizarInscripcion($inscripcion, 'MOTOR_LOCAL');

        $this->assertInstanceOf(AlertaSeguimiento::class, $alerta);
        $this->assertContains($alerta->nivel_riesgo, ['ALTO', 'MEDIO', 'BAJO']);
        $this->assertGreaterThanOrEqual(0, $alerta->score_riesgo);
        $this->assertLessThanOrEqual(100, $alerta->score_riesgo);
        $this->assertNotEmpty($alerta->factores_riesgo);
        $this->assertNotEmpty($alerta->resumen_explicativo);
    }

    public function test_endpoints_n8n_dataset_y_webhook_funcionan_correctamente(): void
    {
        $responseDataset = $this->getJson('/api/ml/dataset-alumnos');
        $responseDataset->assertOk()
            ->assertJsonStructure([
                'status',
                'version_modelo',
                'total_registros',
                'dataset',
            ]);

        $inscripcion = Inscripcion::first();
        $this->assertNotNull($inscripcion);

        $responseWebhook = $this->postJson('/api/ml/webhook-alertas', [
            'version_modelo' => 'v1.1.0',
            'predicciones'   => [
                [
                    'inscripcion_id'      => $inscripcion->id,
                    'score_riesgo'        => 78.5,
                    'nivel_riesgo'        => 'ALTO',
                    'resumen_explicativo' => 'Alerta recibida vía flujo automatizado n8n.',
                ],
            ],
        ]);

        $responseWebhook->assertOk()
            ->assertJson([
                'status'     => 'ok',
                'procesadas' => 1,
            ]);

        $this->assertDatabaseHas('alerta_seguimiento', [
            'inscripcion_id' => $inscripcion->id,
            'nivel_riesgo'   => 'ALTO',
            'origen'         => 'N8N_AUTO',
        ]);
    }

    public function test_registro_de_intervencion_pedagogica_y_versionado_modelo(): void
    {
        $admin = Usuario::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Propietario', 'Secretaria']))->first();
        $this->assertNotNull($admin);

        $inscripcion = Inscripcion::first();
        $alerta = app(RiesgoPredictivoService::class)->analizarInscripcion($inscripcion);
        $alerta->update(['estado' => 'PENDIENTE']);

        // Registrar intervención pedagógica (HU20)
        $response = $this->actingAs($admin)->post("/riesgo/alertas/{$alerta->id}/intervenciones", [
            'tipo'        => 'TUTORIA_INDIVIDUAL',
            'titulo'      => 'Tutoría preventiva de técnica instrumental',
            'descripcion' => 'Revisión detallada de digitación y lectura rítmica.',
            'compromiso'  => 'Estudiar 20 minutos diarios con metrónomo.',
            'resultado'   => 'EN_PROCESO',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('intervencion_seguimiento', [
            'alerta_id' => $alerta->id,
            'titulo'    => 'Tutoría preventiva de técnica instrumental',
        ]);
        $this->assertEquals('EN_ATENCION', $alerta->fresh()->estado);

        // Registrar nueva versión del modelo (HU22)
        $responseModelo = $this->actingAs($admin)->post('/riesgo/modelos', [
            'version'     => 'v1.2.0-test',
            'nombre'      => 'XGBoost Experimental SCALA',
            'algoritmo'   => 'XGBoost Classifier',
            'accuracy'    => 91.20,
            'f1_score'    => 90.05,
            'activo'      => true,
            'descripcion' => 'Versión de prueba automatizada.',
        ]);

        $responseModelo->assertRedirect();
        $this->assertDatabaseHas('version_modelo', [
            'version' => 'v1.2.0-test',
            'activo'  => true,
        ]);
    }
}

