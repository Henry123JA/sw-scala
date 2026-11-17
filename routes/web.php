<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\ObservacionAcademicaController;
use App\Http\Controllers\ProgresoAcademicoController;
use App\Http\Controllers\SeguimientoAcademicoController;
use App\Http\Controllers\ReporteAcademicoController;
use App\Http\Controllers\RiesgoAcademicoController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'bitacora', 'pagina.visitada'])->group(function () {
    // ── Gestión y Consulta de Asistencias — HU8 ─────────────────────────────
    // El index es accesible por todos los roles autenticados (los alumnos ven su propio historial)
    Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
    // ── Reportes Académicos — HU14 ──────────────────────────────────────────
    // Vista general y consulta de reportes (accesible para usuarios autenticados con bitácora)
    // ── Reportes Académicos — HU14 ──────────────────────────────────────────
    Route::prefix('reportes-academicos')->name('reportes.academicos.')->group(function () {
        Route::get('/', [ReporteAcademicoController::class, 'index'])->name('index');
        Route::get('/grupos/{grupo}/sabana', [ReporteAcademicoController::class, 'sabanaGrupo'])->whereNumber('grupo')->name('sabana.grupo');
        Route::get('/inscripciones/{inscripcion}/boletin', [ReporteAcademicoController::class, 'boletinIndividual'])->whereNumber('inscripcion')->name('boletin.individual');
    });

    // La toma y guardado de asistencias está restringido a Propietario, Secretaria y Docente
    Route::middleware('role:Propietario,Secretaria,Docente')->group(function () {
        Route::get('/asistencias/grupos/{grupo}/historial', [AsistenciaController::class, 'historialGrupo'])->whereNumber('grupo')->name('asistencias.grupo.historial');
        Route::post('/asistencias/sesion', [AsistenciaController::class, 'seleccionar'])->name('asistencias.seleccionar');
        Route::get('/asistencias/{sesion}', [AsistenciaController::class, 'show'])->whereNumber('sesion')->name('asistencias.show');
        Route::put('/asistencias/{sesion}', [AsistenciaController::class, 'guardar'])->whereNumber('sesion')->name('asistencias.guardar');
    });

    // ── Gestión y Consulta de Evaluaciones y Calificaciones — HU10 ──────────
    // El index es accesible por todos los roles autenticados (los alumnos ven su propia libreta)
    Route::get('/evaluaciones', [EvaluacionController::class, 'index'])->name('evaluaciones.index');

    // La creación de evaluaciones y el registro de calificaciones está restringido a Propietario, Secretaria y Docente
    Route::middleware('role:Propietario,Secretaria,Docente')->group(function () {
        Route::post('/evaluaciones/grupos/{grupo}', [EvaluacionController::class, 'store'])->whereNumber('grupo')->name('evaluaciones.store');
        Route::get('/evaluaciones/{evaluacion}', [EvaluacionController::class, 'show'])->whereNumber('evaluacion')->name('evaluaciones.show');
        Route::put('/evaluaciones/{evaluacion}', [EvaluacionController::class, 'guardar'])->whereNumber('evaluacion')->name('evaluaciones.guardar');
    });

    // ── Progreso Académico — HU11 ───────────────────────────────────────────
    Route::get('/progreso', [ProgresoAcademicoController::class, 'index'])->name('progreso.index');
    Route::middleware('role:Propietario,Secretaria,Docente')->group(function () {
        Route::get('/progreso/grupos/{grupo}', [ProgresoAcademicoController::class, 'progresoGrupo'])->whereNumber('grupo')->name('progreso.grupo');
        Route::get('/progreso/inscripciones/{inscripcion}', [ProgresoAcademicoController::class, 'progresoInscripcion'])->whereNumber('inscripcion')->name('progreso.inscripcion');
    });

    // ── Seguimiento Académico y Ficha Integral — HU13 ───────────────────────
    Route::get('/seguimiento', [SeguimientoAcademicoController::class, 'index'])->name('seguimiento.index');
    Route::get('/seguimiento/inscripciones/{inscripcion}', [SeguimientoAcademicoController::class, 'show'])->whereNumber('inscripcion')->name('seguimiento.show');

    // ── Seguimiento Académico Predictivo — Sprint 3 (HU15 a HU22) ───────────
    Route::get('/riesgo', [RiesgoAcademicoController::class, 'index'])->name('riesgo.index');
    Route::post('/riesgo/analizar', [RiesgoAcademicoController::class, 'analizar'])->name('riesgo.analizar');
    Route::get('/riesgo/alertas/{alerta}', [RiesgoAcademicoController::class, 'show'])->whereNumber('alerta')->name('riesgo.show');
    Route::put('/riesgo/alertas/{alerta}/estado', [RiesgoAcademicoController::class, 'actualizarEstado'])->whereNumber('alerta')->name('riesgo.alerta.estado');
    Route::post('/riesgo/alertas/{alerta}/intervenciones', [RiesgoAcademicoController::class, 'storeIntervencion'])->whereNumber('alerta')->name('riesgo.intervencion.store');
    Route::put('/riesgo/intervenciones/{intervencion}', [RiesgoAcademicoController::class, 'actualizarIntervencion'])->whereNumber('intervencion')->name('riesgo.intervencion.update');
    Route::post('/riesgo/modelos', [RiesgoAcademicoController::class, 'storeVersionModelo'])->name('riesgo.modelo.store');
    Route::put('/riesgo/modelos/{modelo}/activar', [RiesgoAcademicoController::class, 'activarVersionModelo'])->whereNumber('modelo')->name('riesgo.modelo.activar');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil.index');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::post('/perfil/foto', [PerfilController::class, 'updateFoto'])->name('perfil.foto.store');

    // ── Gestión de Usuarios — CU1 ────────────────────────────────────────────
    Route::middleware(['role:Propietario,Secretaria'])->group(function () {

        // Alumnos
        Route::get('/alumnos', [UsuarioController::class, 'indexAlumnos'])->name('alumnos.index');
        Route::get('/alumnos/crear', [UsuarioController::class, 'createAlumno'])->name('alumnos.create');
        Route::post('/alumnos', [UsuarioController::class, 'storeAlumno'])->name('alumnos.store');
        Route::get('/alumnos/{alumno}/editar', [UsuarioController::class, 'editAlumno'])->name('alumnos.edit');
        Route::put('/alumnos/{alumno}', [UsuarioController::class, 'updateAlumno'])->name('alumnos.update');
        Route::delete('/alumnos/{alumno}', [UsuarioController::class, 'destroy'])->name('alumnos.destroy');

        // Docentes
        Route::get('/docentes', [UsuarioController::class, 'indexDocentes'])->name('docentes.index');
        Route::get('/docentes/crear', [UsuarioController::class, 'createDocente'])->name('docentes.create');
        Route::post('/docentes', [UsuarioController::class, 'storeDocente'])->name('docentes.store');
        Route::get('/docentes/{docente}/editar', [UsuarioController::class, 'editDocente'])->name('docentes.edit');
        Route::put('/docentes/{docente}', [UsuarioController::class, 'updateDocente'])->name('docentes.update');
        Route::delete('/docentes/{docente}', [UsuarioController::class, 'destroy'])->name('docentes.destroy');

        // Aulas
        Route::get('/aulas', [AulaController::class, 'index'])->name('aulas.index');
        Route::get('/aulas/crear', [AulaController::class, 'create'])->name('aulas.create');
        Route::post('/aulas', [AulaController::class, 'store'])->name('aulas.store');
        Route::get('/aulas/{aula}/editar', [AulaController::class, 'edit'])->name('aulas.edit');
        Route::put('/aulas/{aula}', [AulaController::class, 'update'])->name('aulas.update');
        Route::delete('/aulas/{aula}', [AulaController::class, 'destroy'])->name('aulas.destroy');
    });

    // Secretarias — only Propietario for store/destroy
    Route::middleware(['role:Propietario,Secretaria'])->group(function () {
        Route::get('/secretarias', [UsuarioController::class, 'indexSecretarias'])->name('secretarias.index');
        Route::get('/secretarias/{secretaria}/editar', [UsuarioController::class, 'editSecretaria'])->name('secretarias.edit');
        Route::put('/secretarias/{secretaria}', [UsuarioController::class, 'updateSecretaria'])->name('secretarias.update');
    });

    Route::middleware(['role:Propietario'])->group(function () {
        Route::get('/secretarias/crear', [UsuarioController::class, 'createSecretaria'])->name('secretarias.create');
        Route::post('/secretarias', [UsuarioController::class, 'storeSecretaria'])->name('secretarias.store');
        Route::delete('/secretarias/{secretaria}', [UsuarioController::class, 'destroy'])->name('secretarias.destroy');
    });

    // Stubs for modular views so navigation links in Sidebar do not break
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    // --- Oferta Académica (CU4) ---
    Route::get('/cursos', [CursoController::class, 'index'])->name('cursos.index');
    
    Route::middleware(['role:Propietario,Secretaria,Docente'])->group(function () {
        Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
    });

    Route::middleware(['role:Propietario,Secretaria'])->group(function () {
        Route::get('/cursos/crear', [CursoController::class, 'create'])->name('cursos.create');
        Route::post('/cursos', [CursoController::class, 'store'])->name('cursos.store');
        Route::get('/cursos/{curso}/editar', [CursoController::class, 'edit'])->name('cursos.edit');
        Route::put('/cursos/{curso}', [CursoController::class, 'update'])->name('cursos.update');
        Route::delete('/cursos/{curso}', [CursoController::class, 'destroy'])->name('cursos.destroy');

        Route::get('/grupos/crear', [GrupoController::class, 'create'])->name('grupos.create');
        Route::post('/grupos', [GrupoController::class, 'store'])->name('grupos.store');
        Route::get('/grupos/{grupo}/editar', [GrupoController::class, 'edit'])->name('grupos.edit');
        Route::put('/grupos/{grupo}', [GrupoController::class, 'update'])->name('grupos.update');
        Route::delete('/grupos/{grupo}', [GrupoController::class, 'destroy'])->name('grupos.destroy');
    });

    Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
    Route::middleware(['role:Propietario,Secretaria'])->group(function () {
        Route::post('/horarios/docentes', [HorarioController::class, 'storeDocente'])->name('horarios.docente.store');
        Route::put('/horarios/docentes/{id}', [HorarioController::class, 'updateDocente'])->name('horarios.docente.update');
        Route::delete('/horarios/docentes/{id}', [HorarioController::class, 'destroyDocente'])->name('horarios.docente.destroy');
        Route::post('/horarios/grupos', [HorarioController::class, 'storeGrupo'])->name('horarios.grupo.store');
        Route::put('/horarios/grupos/{id}', [HorarioController::class, 'updateGrupo'])->name('horarios.grupo.update');
        Route::delete('/horarios/grupos/{id}', [HorarioController::class, 'destroyGrupo'])->name('horarios.grupo.destroy');
    });
    Route::get('/inscripciones', [InscripcionController::class, 'index'])->name('inscripciones.index');
    Route::get('/inscripciones/{id}', [InscripcionController::class, 'show'])->name('inscripciones.show')->whereNumber('id');

    Route::middleware(['role:Propietario,Secretaria'])->group(function () {
        Route::get('/inscripciones/crear', [InscripcionController::class, 'create'])->name('inscripciones.create');
        Route::post('/inscripciones', [InscripcionController::class, 'store'])->name('inscripciones.store');
        Route::get('/inscripciones/{id}/editar', [InscripcionController::class, 'edit'])->name('inscripciones.edit')->whereNumber('id');
        Route::put('/inscripciones/{id}', [InscripcionController::class, 'update'])->name('inscripciones.update')->whereNumber('id');
        Route::delete('/inscripciones/{id}', [InscripcionController::class, 'destroy'])->name('inscripciones.destroy')->whereNumber('id');
        Route::post('/inscripciones/{id}/pausar', [InscripcionController::class, 'pause'])->name('inscripciones.pause')->whereNumber('id');
        Route::post('/inscripciones/{id}/reanudar', [InscripcionController::class, 'resume'])->name('inscripciones.resume')->whereNumber('id');
        Route::post('/inscripciones/{id}/cancelar', [InscripcionController::class, 'cancel'])->name('inscripciones.cancel')->whereNumber('id');
    });

    // ── Observaciones Académicas — HU12 ──────────────────────────────────────
    Route::get('/inscripciones/{inscripcion}/observaciones', [ObservacionAcademicaController::class, 'index'])->name('observaciones.index')->whereNumber('inscripcion');
    Route::middleware(['role:Propietario,Secretaria,Docente'])->group(function () {
        Route::post('/observaciones', [ObservacionAcademicaController::class, 'store'])->name('observaciones.store');
        Route::delete('/observaciones/{observacion}', [ObservacionAcademicaController::class, 'destroy'])->name('observaciones.destroy')->whereNumber('observacion');
    });

    Route::middleware(['role:Propietario'])->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/acceso', [ReporteController::class, 'acceso'])->name('reportes.acceso');
        Route::get('/reportes/acceso/exportar', [ReporteController::class, 'exportarAcceso'])->name('reportes.acceso.export');
        Route::get('/reportes/acceso/detalle', [ReporteController::class, 'detalleAcceso'])->name('reportes.acceso.detalle');
        Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    });
});

// ── Endpoints de Integración Automatizada n8n y Servicio Python ML (HU19) ────
Route::get('/api/ml/dataset-alumnos', [RiesgoAcademicoController::class, 'apiDatasetAlumnos'])
    ->middleware(\App\Http\Middleware\RequireMlServiceToken::class)
    ->name('api.ml.dataset');

Route::post('/api/ml/webhook-alertas', [RiesgoAcademicoController::class, 'apiWebhookAlertas'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->middleware(\App\Http\Middleware\RequireMlServiceToken::class)
    ->name('api.ml.webhook');

require __DIR__.'/auth.php';
