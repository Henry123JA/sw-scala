<template>
  <AppLayout>
    <Head title="Riesgo Predictivo - Seguimiento Académico" />

    <div class="space-y-6">
      <!-- Notificación Flash -->
      <div
        v-if="flashSuccess"
        class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs font-semibold shadow-2xs"
      >
        <div class="flex items-center gap-2">
          <component :is="Lucide.CheckCircle2" class="w-4 h-4 text-emerald-600 shrink-0" />
          <span>{{ flashSuccess }}</span>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- ENCABEZADO DEL MÓDULO                                                 -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="badge-menta">HU15 · HU16 · HU18 · HU22</span>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
              Seguimiento Académico Predictivo
            </h1>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            Detección temprana de riesgo académico mediante aprendizaje automático explicable (XAI), bandeja de alertas e intervenciones pedagógicas.
          </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
          <Link
            :href="`${base}/seguimiento`"
            class="btn-menta-outline text-xs !py-2"
          >
            <component :is="Lucide.FileBadge" class="w-4 h-4" />
            Directorio de Seguimiento
          </Link>

          <button
            v-if="puedeGestionarModelos"
            type="button"
            @click="modalModelosAbierto = true"
            class="btn-menta-outline text-xs !py-2"
          >
            <component :is="Lucide.Cpu" class="w-4 h-4 text-[#0F766E]" />
            Gestionar Versiones del Modelo
          </button>

          <button
            v-if="puedeEjecutarAnalisis"
            type="button"
            :disabled="analizando"
            @click="ejecutarAnalisis"
            class="btn-menta text-xs !py-2 shadow-xs"
          >
            <component
              :is="analizando ? Lucide.Loader2 : Lucide.Sparkles"
              class="w-4 h-4"
              :class="{ 'animate-spin': analizando }"
            />
            <span>{{ analizando ? 'Analizando Estudiantes...' : 'Ejecutar Análisis Predictivo' }}</span>
          </button>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- SECCIÓN 1: BANNER DE MODELO PREDICTIVO ACTIVO (HU22)                  -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="card-menta p-5 bg-gradient-to-r from-white via-[#F8FFFD] to-[#F0FDF9] border border-[#DDEFEA] relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="flex items-start sm:items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0 shadow-2xs ring-2 ring-[#A7F3D0]/50">
              <component :is="Lucide.BrainCircuit" class="w-6 h-6" />
            </div>

            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-[#0F766E] text-white font-mono">
                  {{ modeloActual.version || 'v1.0.0' }}
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Modelo Predictivo Activo
                </span>
                <span class="text-xs font-semibold text-gray-500 font-mono">
                  {{ modeloActual.algoritmo || 'Random Forest Classifier' }}
                </span>
              </div>

              <h2 class="text-base font-extrabold text-gray-900 mt-1">
                {{ modeloActual.nombre || 'Motor Predictivo de Riesgo Académico Musical' }}
              </h2>
              <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">
                {{ modeloActual.descripcion || 'Evaluación multivariable de asistencia ponderada, calificaciones por criterio, tendencia evolutiva y puntualidad.' }}
              </p>
            </div>
          </div>

          <!-- Métricas de Calidad del Modelo (HU22) -->
          <div class="grid grid-cols-3 gap-3 shrink-0 border-t lg:border-t-0 pt-3 lg:pt-0 border-[#DDEFEA]">
            <div class="bg-white/90 px-3.5 py-2 rounded-xl border border-[#DDEFEA] text-center">
              <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Accuracy</span>
              <span class="text-sm font-black text-[#0F766E]">
                {{ formatMetricPercent(modeloActual.accuracy) }}%
              </span>
            </div>

            <div class="bg-white/90 px-3.5 py-2 rounded-xl border border-[#DDEFEA] text-center">
              <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">F1-Score</span>
              <span class="text-sm font-black text-teal-700">
                {{ formatMetricPercent(modeloActual.f1_score) }}%
              </span>
            </div>

            <div class="bg-white/90 px-3.5 py-2 rounded-xl border border-[#DDEFEA] text-center">
              <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Entrenamiento</span>
              <span class="text-xs font-bold text-gray-700 block mt-0.5">
                {{ formatDate(modeloActual.fecha_entrenamiento) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- SECCIÓN 2: TARJETAS KPI DE CLASIFICACIÓN DE RIESGO (HU16)             -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- 1. Total Analizados -->
        <div class="card-menta p-4 flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
            <component :is="Lucide.Users" class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">Total Analizados</span>
            <span class="text-2xl font-black text-gray-900 mt-0.5 block">{{ kpisData.total_analizados }}</span>
          </div>
        </div>

        <!-- 2. Riesgo Alto -->
        <div class="rounded-2xl p-4 flex items-center gap-3.5 bg-rose-50 border border-rose-200 text-rose-700 shadow-2xs">
          <div class="w-11 h-11 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 border border-rose-200">
            <component :is="Lucide.ShieldAlert" class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider block text-rose-700">Riesgo Alto</span>
            <div class="flex items-baseline gap-1.5 mt-0.5">
              <span class="text-2xl font-black text-rose-800">{{ kpisData.riesgo_alto }}</span>
              <span class="text-[11px] font-semibold text-rose-600">crítico</span>
            </div>
          </div>
        </div>

        <!-- 3. Riesgo Medio -->
        <div class="rounded-2xl p-4 flex items-center gap-3.5 bg-amber-50 border border-amber-200 text-amber-700 shadow-2xs">
          <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200">
            <component :is="Lucide.AlertTriangle" class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider block text-amber-700">Riesgo Medio</span>
            <div class="flex items-baseline gap-1.5 mt-0.5">
              <span class="text-2xl font-black text-amber-800">{{ kpisData.riesgo_medio }}</span>
              <span class="text-[11px] font-semibold text-amber-600">preventivo</span>
            </div>
          </div>
        </div>

        <!-- 4. Riesgo Bajo -->
        <div class="rounded-2xl p-4 flex items-center gap-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 shadow-2xs">
          <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200">
            <component :is="Lucide.ShieldCheck" class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider block text-emerald-700">Riesgo Bajo</span>
            <div class="flex items-baseline gap-1.5 mt-0.5">
              <span class="text-2xl font-black text-emerald-800">{{ kpisData.riesgo_bajo }}</span>
              <span class="text-[11px] font-semibold text-emerald-600">estable</span>
            </div>
          </div>
        </div>

        <!-- 5. Alertas por Atender -->
        <div class="card-menta p-4 flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-teal-50 text-[#0F766E] flex items-center justify-center shrink-0 border border-teal-200">
            <component :is="Lucide.BellRing" class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">Alertas por Atender</span>
            <div class="flex items-baseline gap-1.5 mt-0.5">
              <span class="text-2xl font-black text-[#0F766E]">{{ kpisData.alertas_pendientes }}</span>
              <span class="text-[11px] font-semibold text-gray-400">activas</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- SECCIÓN 3: FILTROS Y BANDEJA DE ALERTAS DE SEGUIMIENTO (HU18)         -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="card-menta p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          <!-- Buscador por alumno/código -->
          <div class="relative sm:col-span-2">
            <input
              v-model="filtroBusqueda"
              type="text"
              placeholder="Buscar por nombre del estudiante, código o CI..."
              class="input-menta text-xs py-2"
              style="padding-left: 2.25rem !important;"
            />
            <component
              :is="Lucide.Search"
              class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
            />
          </div>

          <!-- Selector Nivel de Riesgo -->
          <div>
            <select v-model="filtroNivelRiesgo" class="input-menta text-xs py-2">
              <option value="">Todos los niveles</option>
              <option value="ALTO">Riesgo ALTO</option>
              <option value="MEDIO">Riesgo MEDIO</option>
              <option value="BAJO">Riesgo BAJO</option>
            </select>
          </div>

          <!-- Selector Estado de Alerta -->
          <div>
            <select v-model="filtroEstado" class="input-menta text-xs py-2">
              <option value="">Todos los estados</option>
              <option value="PENDIENTE">PENDIENTE</option>
              <option value="EN_ATENCION">EN ATENCIÓN</option>
              <option value="RESUELTA">RESUELTA</option>
              <option value="DESCARTADA">DESCARTADA</option>
            </select>
          </div>

          <!-- Selector de Grupo -->
          <div>
            <select v-model="filtroGrupo" class="input-menta text-xs py-2">
              <option value="">Todos los grupos</option>
              <option
                v-for="g in grupos"
                :key="g.id"
                :value="String(g.id)"
              >
                {{ g.codigo_grupo }} — {{ g.curso?.nombre || g.curso_nombre || 'Curso' }}
              </option>
            </select>
          </div>
        </div>

        <!-- Contador y botón limpiar filtros -->
        <div class="flex items-center justify-between pt-2 border-t border-[#DDEFEA] text-xs text-gray-500">
          <div>
            Mostrando <strong>{{ alertasFiltradas.length }}</strong> de <strong>{{ listaAlertas.length }}</strong> evaluaciones predictivas
          </div>
          <button
            v-if="filtroBusqueda || filtroNivelRiesgo || filtroEstado || filtroGrupo"
            type="button"
            @click="limpiarFiltros"
            class="text-[#0F766E] hover:underline font-semibold flex items-center gap-1 cursor-pointer"
          >
            <component :is="Lucide.RotateCcw" class="w-3.5 h-3.5" />
            Limpiar filtros
          </button>
        </div>
      </div>

      <!-- TABLA DE ALERTAS PREDICTIVAS -->
      <div v-if="alertasFiltradas.length > 0" class="card-menta overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="table-header-menta">
              <tr>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Estudiante</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Grupo / Curso</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Nivel y Score de Riesgo</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Principal Factor Detectado</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Estado de Alerta</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#DDEFEA]">
              <tr
                v-for="alerta in alertasFiltradas"
                :key="alerta.id"
                class="hover:bg-[#F0FDF9]/70 transition-colors"
              >
                <!-- Columna 1: Estudiante -->
                <td class="p-3.5">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-white text-xs uppercase shrink-0 ring-2 ring-[#D1FAE5]"
                      :style="{ backgroundColor: '#14B8A6' }"
                    >
                      <img
                        v-if="getAlumno(alerta)?.foto_url"
                        :src="getAlumno(alerta).foto_url"
                        :alt="getAlumnoNombreCompleto(alerta)"
                        class="w-full h-full rounded-full object-cover"
                      />
                      <span v-else>{{ iniciales(getAlumno(alerta)?.nombres, getAlumno(alerta)?.apellidos) }}</span>
                    </div>
                    <div>
                      <span class="font-bold text-gray-900 block leading-tight">
                        {{ getAlumnoNombreCompleto(alerta) }}
                      </span>
                      <div class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-500 font-mono">
                        <span>{{ getAlumno(alerta)?.codigo || 'S/C' }}</span>
                        <span v-if="getAlumno(alerta)?.ci" class="text-gray-400">· CI: {{ getAlumno(alerta)?.ci }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Columna 2: Grupo / Curso -->
                <td class="p-3.5">
                  <span class="font-mono font-bold text-xs text-[#0F766E] block">
                    {{ getGrupo(alerta)?.codigo_grupo || 'GRUPO' }}
                  </span>
                  <span class="font-semibold text-gray-900 text-xs block leading-tight mt-0.5">
                    {{ getCursoNombre(alerta) }}
                  </span>
                  <span class="text-[11px] text-gray-500 block mt-0.5">
                    Doc.: {{ getDocenteNombre(alerta) }}
                  </span>
                </td>

                <!-- Columna 3: Nivel y Score de Riesgo (HU16) -->
                <td class="p-3.5 min-w-[175px]">
                  <div class="flex items-center justify-between gap-2 mb-1.5">
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border"
                      :class="getNivelRiesgoBadgeClasses(alerta.nivel_riesgo)"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="getNivelRiesgoDotClasses(alerta.nivel_riesgo)"></span>
                      {{ alerta.nivel_riesgo }}
                    </span>
                    <span class="text-xs font-black" :class="getNivelRiesgoTextClasses(alerta.nivel_riesgo)">
                      {{ formatScore(alerta.score_riesgo) }}%
                    </span>
                  </div>
                  <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div
                      class="h-2 rounded-full transition-all duration-500"
                      :class="getNivelRiesgoBarClasses(alerta.nivel_riesgo)"
                      :style="{ width: `${Math.min(Math.max(Number(alerta.score_riesgo || 0), 4), 100)}%` }"
                    ></div>
                  </div>
                </td>

                <!-- Columna 4: Principal Factor Detectado (HU17) -->
                <td class="p-3.5 max-w-xs">
                  <template v-if="getFactoresList(alerta).length > 0">
                    <div class="flex items-start gap-2">
                      <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                          <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-700 border border-gray-200">
                            {{ getFactoresList(alerta)[0].categoria || 'FACTOR' }}
                          </span>
                          <span class="text-[11px] font-extrabold rounded px-1" :class="clasesAporte(getFactoresList(alerta)[0])">
                            {{ textoAporte(getFactoresList(alerta)[0]) }}
                          </span>
                          <span
                            v-if="getFactoresList(alerta).length > 1"
                            class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-[#D1FAE5] text-[#0F766E]"
                          >
                            +{{ getFactoresList(alerta).length - 1 }} factores
                          </span>
                        </div>
                        <p class="text-xs text-gray-700 font-medium line-clamp-2 mt-1">
                          {{ getFactoresList(alerta)[0].descripcion || getFactoresList(alerta)[0].factor || getFactoresList(alerta)[0].nombre }}
                        </p>
                      </div>
                    </div>
                  </template>
                  <span v-else class="text-xs text-gray-400 italic">
                    Sin explicación individual registrada
                  </span>
                </td>

                <!-- Columna 5: Estado de Alerta (HU18) -->
                <td class="p-3.5">
                  <div class="space-y-1.5">
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                      :class="getEstadoAlertaBadgeClasses(alerta.estado)"
                    >
                      {{ getEstadoAlertaLabel(alerta.estado) }}
                    </span>

                    <div v-if="puedeCambiarEstado">
                      <select
                        :value="alerta.estado"
                        @change="cambiarEstadoAlerta(alerta.id, $event.target.value)"
                        class="block w-full text-[11px] bg-white border border-[#DDEFEA] rounded-lg px-2 py-1 text-gray-700 font-semibold focus:border-[#14B8A6] focus:outline-none cursor-pointer"
                      >
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="EN_ATENCION">EN ATENCIÓN</option>
                        <option value="RESUELTA">RESUELTA</option>
                        <option value="DESCARTADA">DESCARTADA</option>
                      </select>
                    </div>
                  </div>
                </td>

                <!-- Columna 6: Acciones -->
                <td class="p-3.5 text-right">
                  <Link
                    :href="`${base}/riesgo/alertas/${alerta.id}`"
                    class="btn-menta !py-1.5 !px-3 text-xs inline-flex items-center gap-1.5 whitespace-nowrap"
                  >
                    <component :is="Lucide.Sparkles" class="w-3.5 h-3.5" />
                    <span>Ver Factores e Intervenir</span>
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="card-menta p-12 text-center text-gray-500">
        <component :is="Lucide.ShieldAlert" class="w-12 h-12 mx-auto text-gray-300 mb-3" />
        <h3 class="text-base font-bold text-gray-800">No se encontraron alertas predictivas</h3>
        <p class="text-xs text-gray-400 mt-1 max-w-md mx-auto">
          No existen registros que coincidan con los filtros seleccionados o aún no se ha ejecutado el motor predictivo sobre los grupos activos.
        </p>
        <div v-if="puedeEjecutarAnalisis" class="mt-4">
          <button
            type="button"
            :disabled="analizando"
            @click="ejecutarAnalisis"
            class="btn-menta text-xs"
          >
            <component :is="Lucide.Sparkles" class="w-4 h-4" />
            Ejecutar Análisis Predictivo Ahora
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: GESTIÓN DE VERSIONES DEL MODELO PREDICTIVO (HU22)               -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div
      v-if="modalModelosAbierto"
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-xs p-4 overflow-y-auto"
    >
      <div class="card-menta w-full max-w-4xl bg-white p-6 space-y-6 max-h-[90vh] overflow-y-auto shadow-xl">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b border-[#DDEFEA]">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center">
              <component :is="Lucide.Cpu" class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-extrabold text-gray-900">
                Gestión de Versiones del Modelo Predictivo (HU22)
              </h3>
              <p class="text-xs text-gray-500">
                Administra las versiones entrenadas, métricas de validación (Accuracy / F1-Score) y activa el modelo en producción.
              </p>
            </div>
          </div>
          <button
            type="button"
            @click="modalModelosAbierto = false"
            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer"
          >
            <component :is="Lucide.X" class="w-5 h-5" />
          </button>
        </div>

        <!-- Listado de Versiones Registradas -->
        <div class="space-y-3">
          <h4 class="text-xs font-extrabold uppercase tracking-wider text-[#0F766E]">
            Historial de Modelos Entrenados ({{ listaVersiones.length }})
          </h4>

          <div class="overflow-x-auto border border-[#DDEFEA] rounded-xl">
            <table class="w-full text-left text-xs">
              <thead class="table-header-menta">
                <tr>
                  <th class="p-3 font-bold uppercase">Versión</th>
                  <th class="p-3 font-bold uppercase">Nombre / Algoritmo</th>
                  <th class="p-3 font-bold uppercase text-center">Accuracy</th>
                  <th class="p-3 font-bold uppercase text-center">F1-Score</th>
                  <th class="p-3 font-bold uppercase">Entrenamiento</th>
                  <th class="p-3 font-bold uppercase text-center">Estado</th>
                  <th class="p-3 font-bold uppercase text-right">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#DDEFEA]">
                <tr
                  v-for="ver in listaVersiones"
                  :key="ver.id"
                  class="hover:bg-[#F0FDF9]"
                >
                  <td class="p-3 font-mono font-bold text-[#0F766E]">
                    {{ ver.version }}
                  </td>
                  <td class="p-3">
                    <span class="font-bold text-gray-900 block">{{ ver.nombre }}</span>
                    <span class="text-[11px] text-gray-500 font-mono">{{ ver.algoritmo }}</span>
                  </td>
                  <td class="p-3 text-center font-black text-emerald-700">
                    {{ formatMetricPercent(ver.accuracy) }}%
                  </td>
                  <td class="p-3 text-center font-black text-teal-700">
                    {{ formatMetricPercent(ver.f1_score) }}%
                  </td>
                  <td class="p-3 text-gray-600">
                    {{ formatDate(ver.fecha_entrenamiento) }}
                  </td>
                  <td class="p-3 text-center">
                    <span
                      v-if="ver.activo"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                    >
                      ACTIVO
                    </span>
                    <span
                      v-else
                      class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500"
                    >
                      INACTIVO
                    </span>
                  </td>
                  <td class="p-3 text-right">
                    <button
                      v-if="!ver.activo"
                      type="button"
                      @click="activarVersionModelo(ver.id)"
                      class="btn-menta-outline !py-1 !px-2.5 text-[11px]"
                    >
                      Activar
                    </button>
                    <span v-else class="text-[11px] font-bold text-[#0F766E]">En producción</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Formulario para Registrar Nueva Versión del Modelo -->
        <form @submit.prevent="guardarNuevoModelo" class="p-4 rounded-xl bg-[#F0FDF9]/60 border border-[#DDEFEA] space-y-4">
          <h4 class="text-xs font-extrabold uppercase tracking-wider text-[#0F766E] flex items-center gap-1.5">
            <component :is="Lucide.PlusCircle" class="w-4 h-4" />
            Registrar Nueva Versión del Modelo Predictivo
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Versión *</label>
              <input
                v-model="formModelo.version"
                type="text"
                required
                placeholder="Ej. v1.2.0"
                class="input-menta text-xs py-2"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Nombre del Modelo *</label>
              <input
                v-model="formModelo.nombre"
                type="text"
                required
                placeholder="Ej. Scala Risk Classifier RF"
                class="input-menta text-xs py-2"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Algoritmo *</label>
              <input
                v-model="formModelo.algoritmo"
                type="text"
                required
                placeholder="Ej. Random Forest / XGBoost"
                class="input-menta text-xs py-2"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Accuracy (%) *</label>
              <input
                v-model="formModelo.accuracy"
                type="number"
                step="0.01"
                min="0"
                max="100"
                required
                placeholder="Ej. 91.50"
                class="input-menta text-xs py-2"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">F1-Score (%) *</label>
              <input
                v-model="formModelo.f1_score"
                type="number"
                step="0.01"
                min="0"
                max="100"
                required
                placeholder="Ej. 89.40"
                class="input-menta text-xs py-2"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Fecha de Entrenamiento *</label>
              <input
                v-model="formModelo.fecha_entrenamiento"
                type="date"
                required
                class="input-menta text-xs py-2"
              />
            </div>

            <div class="sm:col-span-3">
              <label class="block text-xs font-bold text-gray-700 mb-1">Endpoint Microservicio IA (Opcional)</label>
              <input
                v-model="formModelo.url_endpoint"
                type="text"
                placeholder="http://127.0.0.1:8001/api/v1/predict"
                class="input-menta text-xs py-2"
              />
            </div>

            <div class="sm:col-span-3">
              <label class="block text-xs font-bold text-gray-700 mb-1">Descripción y Notas de Hiperparámetros</label>
              <textarea
                v-model="formModelo.descripcion"
                rows="2"
                placeholder="Detalle de variables utilizadas, dataset y calibración..."
                class="input-menta text-xs py-2"
              ></textarea>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              @click="modalModelosAbierto = false"
              class="btn-menta-outline text-xs !py-2"
            >
              Cerrar
            </button>
            <button
              type="submit"
              :disabled="formModelo.processing"
              class="btn-menta text-xs !py-2"
            >
              <component :is="Lucide.Save" class="w-4 h-4" />
              <span>{{ formModelo.processing ? 'Guardando...' : 'Registrar Versión' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { textoAporte, clasesAporte } from './explicacion';
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  alertas: {
    type: [Array, Object],
    default: () => [],
  },
  kpis: {
    type: Object,
    default: () => ({}),
  },
  modeloActivo: {
    type: Object,
    default: null,
  },
  modelo_activo: {
    type: Object,
    default: null,
  },
  versionesModelo: {
    type: Array,
    default: () => [],
  },
  versiones_modelo: {
    type: Array,
    default: () => [],
  },
  grupos: {
    type: Array,
    default: () => [],
  },
  filtros: {
    type: Object,
    default: () => ({}),
  },
  rol_usuario: {
    type: String,
    default: '',
  },
  es_alumno: {
    type: Boolean,
    default: false,
  },
});

const base = (import.meta.env.VITE_BASE_URL || '').replace(/\/$/, '');
const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success || null);

const rolActual = computed(() => {
  return props.rol_usuario || page.props.auth?.user?.rol?.nombre || page.props.auth?.user?.role || 'Propietario';
});

const puedeGestionarModelos = computed(() => {
  if (props.es_alumno || rolActual.value === 'Alumno') return false;
  return ['Propietario', 'Secretaria'].includes(rolActual.value);
});

const puedeEjecutarAnalisis = computed(() => {
  if (props.es_alumno || rolActual.value === 'Alumno') return false;
  return ['Propietario', 'Secretaria', 'Docente'].includes(rolActual.value);
});

const puedeCambiarEstado = computed(() => {
  if (props.es_alumno || rolActual.value === 'Alumno') return false;
  return ['Propietario', 'Secretaria', 'Docente'].includes(rolActual.value);
});

const listaAlertas = computed(() => {
  if (Array.isArray(props.alertas)) return props.alertas;
  if (props.alertas && Array.isArray(props.alertas.data)) return props.alertas.data;
  return [];
});

const modeloActual = computed(() => {
  return props.modeloActivo || props.modelo_activo || {
    version: 'v1.1.0',
    nombre: 'Random Forest Classifier — Scala XAI',
    algoritmo: 'Random Forest Classifier',
    accuracy: 92.4,
    f1_score: 90.8,
    fecha_entrenamiento: new Date().toISOString().slice(0, 10),
    descripcion: 'Modelo ensamble entrenado con historial de asistencias, evaluaciones prácticas y evolución técnica.',
  };
});

const listaVersiones = computed(() => {
  const list = props.versionesModelo?.length ? props.versionesModelo : (props.versiones_modelo || []);
  return list.length ? list : [modeloActual.value];
});

const kpisData = computed(() => {
  const total = props.kpis?.total_analizados ?? listaAlertas.value.length;
  const alto = props.kpis?.riesgo_alto ?? listaAlertas.value.filter(a => a.nivel_riesgo === 'ALTO').length;
  const medio = props.kpis?.riesgo_medio ?? listaAlertas.value.filter(a => a.nivel_riesgo === 'MEDIO').length;
  const bajo = props.kpis?.riesgo_bajo ?? listaAlertas.value.filter(a => a.nivel_riesgo === 'BAJO').length;
  const pendientes = props.kpis?.alertas_pendientes ?? listaAlertas.value.filter(a => ['PENDIENTE', 'EN_ATENCION'].includes(a.estado)).length;

  return {
    total_analizados: total,
    riesgo_alto: alto,
    riesgo_medio: medio,
    riesgo_bajo: bajo,
    alertas_pendientes: pendientes,
  };
});

// Filtros reactivos
const filtroBusqueda = ref(props.filtros?.search || '');
const filtroNivelRiesgo = ref(props.filtros?.nivel_riesgo || '');
const filtroEstado = ref(props.filtros?.estado || '');
const filtroGrupo = ref(props.filtros?.grupo_id ? String(props.filtros.grupo_id) : '');

function getAlumno(alerta) {
  return alerta?.alumno || alerta?.inscripcion?.alumno || {};
}

function getAlumnoNombreCompleto(alerta) {
  const al = getAlumno(alerta);
  if (al?.nombres || al?.apellidos) {
    return `${al.nombres || ''} ${al.apellidos || ''}`.trim();
  }
  return alerta?.alumno_nombre || 'Estudiante';
}

function getGrupo(alerta) {
  return alerta?.grupo || alerta?.inscripcion?.grupo || {};
}

function getCursoNombre(alerta) {
  const g = getGrupo(alerta);
  return g?.curso?.nombre || alerta?.curso_nombre || 'Curso Musical';
}

function getDocenteNombre(alerta) {
  const g = getGrupo(alerta);
  if (g?.docente) {
    return `${g.docente.nombres || ''} ${g.docente.apellidos || ''}`.trim();
  }
  return alerta?.docente_nombre || 'Docente Asignado';
}

function getFactoresList(alerta) {
  const raw = alerta?.factores_riesgo;
  if (!raw) return [];
  if (Array.isArray(raw)) return raw;
  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }
  return [];
}

const alertasFiltradas = computed(() => {
  return listaAlertas.value.filter(alerta => {
    if (filtroNivelRiesgo.value && alerta.nivel_riesgo !== filtroNivelRiesgo.value) {
      return false;
    }
    if (filtroEstado.value && alerta.estado !== filtroEstado.value) {
      return false;
    }
    if (filtroGrupo.value) {
      const gId = String(alerta.grupo_id || getGrupo(alerta)?.id || '');
      if (gId !== filtroGrupo.value) return false;
    }
    if (filtroBusqueda.value.trim()) {
      const q = filtroBusqueda.value.toLowerCase().trim();
      const al = getAlumno(alerta);
      const grp = getGrupo(alerta);
      const matchNombre = getAlumnoNombreCompleto(alerta).toLowerCase().includes(q);
      const matchCodigo = (al?.codigo || '').toLowerCase().includes(q);
      const matchCi = (al?.ci || '').toLowerCase().includes(q);
      const matchGrupo = (grp?.codigo_grupo || '').toLowerCase().includes(q);
      const matchCurso = getCursoNombre(alerta).toLowerCase().includes(q);
      return matchNombre || matchCodigo || matchCi || matchGrupo || matchCurso;
    }
    return true;
  });
});

function limpiarFiltros() {
  filtroBusqueda.value = '';
  filtroNivelRiesgo.value = '';
  filtroEstado.value = '';
  filtroGrupo.value = '';
}

// Ejecutar análisis predictivo (HU15)
const analizando = ref(false);
function ejecutarAnalisis() {
  analizando.value = true;
  router.post(
    `${base}/riesgo/analizar`,
    { grupo_id: filtroGrupo.value || null },
    {
      preserveScroll: true,
      onFinish: () => {
        analizando.value = false;
      },
    }
  );
}

// Cambiar estado de alerta (HU18)
function cambiarEstadoAlerta(alertaId, nuevoEstado) {
  router.put(
    `${base}/riesgo/alertas/${alertaId}/estado`,
    { estado: nuevoEstado },
    { preserveScroll: true }
  );
}

// Gestión de versiones del modelo (HU22)
const modalModelosAbierto = ref(false);
const formModelo = useForm({
  version: '',
  nombre: '',
  algoritmo: 'Random Forest Classifier',
  accuracy: 93.0,
  f1_score: 91.5,
  fecha_entrenamiento: new Date().toISOString().slice(0, 10),
  url_endpoint: 'http://127.0.0.1:8001/api/v1/predict',
  descripcion: '',
});

function guardarNuevoModelo() {
  formModelo.post(`${base}/riesgo/modelos`, {
    preserveScroll: true,
    onSuccess: () => {
      formModelo.reset('version', 'nombre', 'descripcion');
    },
  });
}

function activarVersionModelo(id) {
  router.put(`${base}/riesgo/modelos/${id}/activar`, {}, { preserveScroll: true });
}

// Helpers visuales
function iniciales(nombres, apellidos) {
  const n = (nombres || '').trim().charAt(0);
  const a = (apellidos || '').trim().charAt(0);
  return (n + a).toUpperCase() || 'AL';
}

function formatScore(val) {
  const num = Number(val ?? 0);
  return Number.isFinite(num) ? num.toFixed(2) : '—';
}

function formatMetricPercent(val) {
  const num = Number(val ?? 0);
  if (num <= 1 && num > 0) {
    return (num * 100).toFixed(1);
  }
  return num.toFixed(1);
}

function formatDate(dateStr) {
  if (!dateStr) return 'Reciente';
  return String(dateStr).slice(0, 10);
}

function getNivelRiesgoBadgeClasses(nivel) {
  switch (nivel) {
    case 'ALTO':
      return 'bg-rose-50 text-rose-700 border-rose-200';
    case 'MEDIO':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'BAJO':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    default:
      return 'bg-gray-50 text-gray-600 border-gray-200';
  }
}

function getNivelRiesgoDotClasses(nivel) {
  switch (nivel) {
    case 'ALTO': return 'bg-rose-500';
    case 'MEDIO': return 'bg-amber-500';
    case 'BAJO': return 'bg-emerald-500';
    default: return 'bg-gray-400';
  }
}

function getNivelRiesgoTextClasses(nivel) {
  switch (nivel) {
    case 'ALTO': return 'text-rose-700';
    case 'MEDIO': return 'text-amber-700';
    case 'BAJO': return 'text-emerald-700';
    default: return 'text-gray-700';
  }
}

function getNivelRiesgoBarClasses(nivel) {
  switch (nivel) {
    case 'ALTO': return 'bg-rose-500';
    case 'MEDIO': return 'bg-amber-500';
    case 'BAJO': return 'bg-emerald-500';
    default: return 'bg-teal-500';
  }
}

function getEstadoAlertaLabel(estado) {
  switch (estado) {
    case 'PENDIENTE': return 'PENDIENTE';
    case 'EN_ATENCION': return 'EN ATENCIÓN';
    case 'RESUELTA': return 'RESUELTA';
    case 'DESCARTADA': return 'DESCARTADA';
    default: return estado || 'PENDIENTE';
  }
}

function getEstadoAlertaBadgeClasses(estado) {
  switch (estado) {
    case 'PENDIENTE':
      return 'bg-rose-50 text-rose-700 border-rose-200';
    case 'EN_ATENCION':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'RESUELTA':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'DESCARTADA':
      return 'bg-gray-100 text-gray-600 border-gray-200';
    default:
      return 'bg-gray-50 text-gray-600 border-gray-200';
  }
}
</script>
