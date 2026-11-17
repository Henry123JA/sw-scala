<?php

namespace Tests\Feature;

use App\Models\AlertaSeguimiento;
use App\Models\IntervencionSeguimiento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocenteAlertasTest extends TestCase
{
    use DatabaseTransactions;

    private Usuario $docente;
    private AlertaSeguimiento $propia;
    private AlertaSeguimiento $ajena;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ml.enabled' => false]);
        $this->propia = AlertaSeguimiento::with('inscripcion.grupo')->findOrFail(7);
        $this->docente = Usuario::with('rol')->findOrFail($this->propia->inscripcion->grupo->docente_id);
        $this->assertSame('Docente', $this->docente->rol->nombre);
        $this->ajena = AlertaSeguimiento::whereHas('inscripcion.grupo',
            fn ($q) => $q->where('docente_id', '<>', $this->docente->id))->firstOrFail();
        // Autenticacion con el usuario docente real asignado al grupo de la alerta 7.
        $this->actingAs($this->docente);
    }

    public function test_docente_asignado_ve_alerta7_y_explicaciones_json(): void
    {
        $response = $this->getJson('/riesgo/alertas/7')->assertOk();
        $response->assertJsonPath('rol_usuario', 'Docente')
            ->assertJsonPath('alerta.id', 7)
            ->assertJsonPath('alerta.score_riesgo', $this->propia->score_riesgo)
            ->assertJsonCount(6, 'alerta.factores_riesgo')
            ->assertJsonCount(3, 'metricas_base.prediccion_ml.acciones_sugeridas')
            ->assertJsonPath('metricas_base.prediccion_ml.explicacion_modelo.metodo', 'TREE_SHAP');
        $this->assertSame($this->propia->resumen_explicativo, $response->json('alerta.resumen_explicativo'));
    }

    public function test_vista_inertia_del_docente_recibe_el_detalle_correcto(): void
    {
        $this->get('/riesgo/alertas/7')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Riesgo/Show')->where('rol_usuario', 'Docente')
            ->where('alerta.id', 7)->has('alerta.factores_riesgo', 6)
            ->where('puede_gestionar', true));
    }

    public function test_url_directa_de_alerta_ajena_devuelve_403_en_html_y_json(): void
    {
        $url = '/riesgo/alertas/' . $this->ajena->id;
        $this->get($url)->assertForbidden();
        $response = $this->getJson($url)->assertForbidden();
        $response->assertJsonMissingPath('alerta.factores_riesgo');
        $this->assertStringNotContainsString($this->ajena->resumen_explicativo ?? '__sin_resumen__', $response->getContent());
    }

    public function test_bandeja_grupos_y_kpis_solo_incluyen_su_alcance(): void
    {
        $response = $this->getJson('/riesgo')->assertOk();
        $response->assertJsonPath('rol_usuario', 'Docente');
        $ids = array_column($response->json('alertas'), 'id');
        $this->assertContains(7, $ids);
        $this->assertNotContains($this->ajena->id, $ids);
        $esperadas = AlertaSeguimiento::whereHas('inscripcion.grupo',
            fn ($q) => $q->where('docente_id', $this->docente->id))->count();
        $this->assertSame($esperadas, $response->json('kpis.total_alertas'));
        foreach ($response->json('alertas') as $alerta) {
            $this->assertEquals($this->docente->id, $alerta['grupo']['docente']['id']);
        }
        foreach ($response->json('grupos') as $grupo) {
            $this->assertDatabaseHas('grupo', ['id' => $grupo['id'], 'docente_id' => $this->docente->id]);
        }
        $this->getJson('/riesgo?grupo_id=' . $this->ajena->inscripcion->grupo_id)
            ->assertOk()->assertJsonCount(0, 'alertas');
    }

    public function test_no_puede_cambiar_estado_de_alerta_ajena(): void
    {
        $antes = $this->ajena->getRawOriginal();
        $this->putJson('/riesgo/alertas/' . $this->ajena->id . '/estado', ['estado' => 'RESUELTA'])
            ->assertForbidden();
        $this->assertEquals($antes, $this->ajena->fresh()->getRawOriginal());
    }

    public function test_no_puede_crear_intervenciones_en_otro_grupo(): void
    {
        $antes = IntervencionSeguimiento::count();
        $this->postJson('/riesgo/alertas/' . $this->ajena->id . '/intervenciones', [
            'tipo' => 'TUTORIA_INDIVIDUAL', 'titulo' => 'Prueba de aislamiento',
            'descripcion' => 'No debe guardarse',
        ])->assertForbidden();
        $this->assertSame($antes, IntervencionSeguimiento::count());
    }


    public function test_no_puede_editar_intervencion_de_otro_grupo(): void
    {
        $intervencion = IntervencionSeguimiento::create([
            'alerta_id' => $this->ajena->id,
            'inscripcion_id' => $this->ajena->inscripcion_id,
            'usuario_id' => $this->ajena->inscripcion->grupo->docente_id,
            'fecha' => now()->toDateString(), 'tipo' => 'TUTORIA_INDIVIDUAL',
            'titulo' => 'Prueba transaccional de permisos', 'descripcion' => 'No persistir',
            'resultado' => 'EN_PROCESO',
        ]);
        $intervencion->refresh();
        $antes = $intervencion->getRawOriginal();
        $this->putJson('/riesgo/intervenciones/' . $intervencion->id,
            ['resultado' => 'MEJORA_EVIDENTE'])->assertForbidden();
        $this->assertEquals($antes, $intervencion->fresh()->getRawOriginal());
    }

    public function test_otro_docente_no_puede_abrir_la_alerta7(): void
    {
        $otro = Usuario::with('rol')->findOrFail($this->ajena->inscripcion->grupo->docente_id);
        $this->assertSame('Docente', $otro->rol->nombre);
        $this->actingAs($otro)->getJson('/riesgo/alertas/7')->assertForbidden();
    }
}
