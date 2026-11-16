<template>
  <AppLayout>
    <Head :title="`Detalle Predictivo - ${alumnoNombreCompleto}`" />

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
      <!-- ENCABEZADO: NAVEGACIÓN, ESTUDIANTE, GRUPO Y ESTADO DE ALERTA (HU18)   -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="card-menta p-5 bg-gradient-to-r from-white via-[#F8FFFD] to-[#F0FDF9]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-[#DDEFEA]">
          <div class="flex items-center gap-3 flex-wrap">
            <Link
              :href="`${base}/riesgo`"
              class="btn-menta-outline !py-1.5 !px-3 text-xs"
            >
              <component :is="Lucide.ArrowLeft" class="w-4 h-4" />
              Volver a Bandeja Predictiva
            </Link>
            <span class="badge-menta">HU17 · HU18 · HU20</span>
            <span class="text-xs font-mono font-bold text-[#0F766E] bg-[#D1FAE5]/60 px-2.5 py-0.5 rounded-full">
              Modelo: {{ modeloVersion }}
            </span>
          </div>

          <!-- Selector de Estado de la Alerta (HU18) -->
          <div class="flex items-center gap-3 flex-wrap">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Estado de Alerta:</span>
            <span
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold border"
              :class="getEstadoAlertaBadgeClasses(alerta.estado)"
            >
              {{ getEstadoAlertaLabel(alerta.estado) }}
            </span>

            <select
              v-if="puedeIntervenir"
              :value="alerta.estado"
              @change="cambiarEstadoAlerta($event.target.value)"
              class="input-menta !w-auto text-xs !py-1.5 font-bold cursor-pointer"
            >
              <option value="PENDIENTE">Cambiar a: PENDIENTE</option>
              <option value="EN_ATENCION">Cambiar a: EN ATENCIÓN</option>
              <option value="RESUELTA">Cambiar a: RESUELTA</option>
              <option value="DESCARTADA">Cambiar a: DESCARTADA</option>
            </select>
          </div>
        </div>

        <!-- Información del Estudiante y Curso -->
        <div class="pt-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 rounded-2xl flex items-center justify-center font-bold text-white text-lg uppercase shrink-0 shadow-xs ring-4 ring-[#D1FAE5]"
              :style="{ backgroundColor: '#14B8A6' }"
            >
              <img
                v-if="alumno?.foto_url"
                :src="alumno.foto_url"
                :alt="alumnoNombreCompleto"
                class="w-full h-full rounded-2xl object-cover"
              />
              <span v-else>{{ iniciales(alumno?.nombres, alumno?.apellidos) }}</span>
            </div>

            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-xl font-black text-gray-900">
                  {{ alumnoNombreCompleto }}
                </h1>
                <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 font-mono text-xs font-bold">
                  {{ alumno?.codigo || 'ALU' }}
                </span>
                <span v-if="alumno?.ci" class="text-xs text-gray-400 font-mono">
                  CI: {{ alumno.ci }}
                </span>
              </div>

              <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-600 flex-wrap">
                <span class="flex items-center gap-1 font-semibold text-gray-800">
                  <component :is="Lucide.BookOpen" class="w-3.5 h-3.5 text-[#0F766E]" />
                  {{ cursoNombre }}
                </span>
                <span class="flex items-center gap-1 font-mono font-bold text-[#0F766E]">
                  <component :is="Lucide.Folder" class="w-3.5 h-3.5" />
                  {{ grupo?.codigo_grupo || 'G-01' }}
                </span>
                <span class="flex items-center gap-1">
                  <component :is="Lucide.UserCheck" class="w-3.5 h-3.5 text-gray-400" />
                  Docente: {{ docenteNombre }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <Link
              v-if="inscripcionId"
              :href="`${base}/seguimiento/inscripciones/${inscripcionId}`"
              class="btn-menta-outline text-xs !py-2"
            >
              <component :is="Lucide.FileBadge" class="w-4 h-4" />
              Abrir Ficha Integral 360°
            </Link>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- PANEL DIVIDIDO: IZQUIERDO (FACTORES XAI) + DERECHO (INTERVENCIONES)   -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- ───────────────────────────────────────────────────────────────── -->
        <!-- PANEL IZQUIERDO: FACTORES DETERMINANTES DE RIESGO (HU17 — XAI)    -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div class="lg:col-span-6 space-y-6">
          <!-- Tarjeta de Score y Explicación IA -->
          <div class="card-menta p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-[#DDEFEA] pb-3">
              <div class="flex items-center gap-2">
                <component :is="Lucide.Sparkles" class="w-5 h-5 text-[#0F766E]" />
                <h2 class="text-base font-extrabold text-gray-900">
                  Diagnóstico de Inteligencia Artificial Explicable (HU17)
                </h2>
              </div>
              <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold border"
                :class="getNivelRiesgoBadgeClasses(alerta.nivel_riesgo)"
              >
                <span class="w-2 h-2 rounded-full" :class="getNivelRiesgoDotClasses(alerta.nivel_riesgo)"></span>
                RIESGO {{ alerta.nivel_riesgo || 'MEDIO' }}
              </span>
            </div>

            <!-- Score Grande y Barra de Probabilidad -->
            <div class="p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4" :class="getNivelRiesgoPanelClasses(alerta.nivel_riesgo)">
              <div>
                <span class="text-[11px] font-bold uppercase tracking-wider block opacity-80">
                  {{ metricas.prediccion_ml ? 'Estimación experimental de reprobación' : 'Índice de seguimiento académico' }}
                </span>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-4xl font-black tracking-tight">
                    {{ formatScore(alerta.score_riesgo) }}%
                  </span>
                  <span class="text-xs font-bold opacity-85">
                    Índice Predictivo (0 - 100%)
                  </span>
                </div>
              </div>

              <div class="w-full sm:w-44">
                <div class="w-full bg-white/80 rounded-full h-3 overflow-hidden border border-black/5">
                  <div
                    class="h-3 rounded-full transition-all duration-500"
                    :class="getNivelRiesgoBarClasses(alerta.nivel_riesgo)"
                    :style="{ width: `${Math.min(Math.max(Number(alerta.score_riesgo || 0), 5), 100)}%` }"
                  ></div>
                </div>
                <span class="text-[10px] font-semibold block mt-1 text-right opacity-80">
                  Evaluado: {{ formatDate(alerta.fecha_analisis || alerta.updated_at) }}
                </span>
              </div>
            </div>

            <!-- Resumen Explicativo en Lenguaje Natural -->
            <div class="p-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] text-xs text-gray-700 leading-relaxed">
              <div class="font-extrabold text-[#0F766E] mb-1 flex items-center gap-1.5">
                <component :is="Lucide.MessageSquareQuote" class="w-4 h-4" />
                Resumen de la predicción
              </div>
              <p>
                {{ alerta.resumen_explicativo || generarResumenExplicativoFallback() }}
              </p>
            </div>

            <section v-if="accionesSugeridas.length" class="p-4 rounded-xl bg-white border border-[#DDEFEA] text-xs text-gray-700">
              <h3 class="font-extrabold text-[#0F766E] mb-2">Acciones sugeridas para el docente</h3>
              <ul class="list-disc pl-5 space-y-2">
                <li v-for="(accion, idx) in accionesSugeridas" :key="idx">{{ accion }}</li>
              </ul>
            </section>

            <!-- Lista Desglosada de Factores Determinantes con Barras de Impacto -->
            <div class="space-y-3 pt-1">
              <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-500">
                Factores de riesgo y protección ({{ listaFactores.length }})
              </h3>

              <p v-if="explicacionModelo" class="text-xs text-gray-600 leading-relaxed">
                Referencia interna del modelo: {{ formatScore(explicacionModelo.referencia_pct) }}%.
                Cada aporte indica cuánto eleva o reduce la estimación frente a esa referencia.
                Las barras muestran el peso relativo de su magnitud, no causas ni porcentajes de culpa.
              </p>
              <div v-if="listaFactores.length > 0" class="space-y-3">
                <div
                  v-for="(factor, idx) in listaFactores"
                  :key="idx"
                  class="p-3.5 rounded-xl border border-[#DDEFEA] bg-white hover:border-[#14B8A6]/50 transition-colors space-y-2"
                >
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span
                        class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase border"
                        :class="getCategoriaFactorBadgeClasses(factor.categoria)"
                      >
                        {{ factor.categoria || 'ACADÉMICO' }}
                      </span>
                      <span class="text-xs font-bold text-gray-900">
                        {{ factor.descripcion || factor.factor || factor.nombre }}
                      </span>
                    </div>

                    <span
                      class="text-xs font-black shrink-0 px-2 py-0.5 rounded-md"
                      :class="clasesAporte(factor)"
                    >
                      {{ textoAporte(factor) }}
                    </span>
                  </div>

                  <p v-if="factor.detalle || factor.valor_actual" class="text-[11px] text-gray-500">
                    Valor observado: <strong class="text-gray-700">{{ factor.detalle || factor.valor_actual }}</strong>
                  </p>

                  <p v-if="factor.metodo === 'TREE_SHAP'" class="text-[11px] text-gray-500">
                    Peso relativo del aporte: {{ factor.impacto }}%
                  </p>
                  <p v-if="aclaracionFactor(factor)" class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
                    {{ aclaracionFactor(factor) }}
                  </p>
                  <!-- Magnitud relativa; el signo se muestra en la etiqueta -->
                  <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div
                      class="h-2 rounded-full transition-all duration-500"
                      :class="clasesBarraAporte(factor)"
                      :style="{ width: `${Math.min(Math.max(Number(factor.impacto ?? factor.peso ?? 0), 0), 100)}%` }"
                    ></div>
                  </div>
                </div>
              </div>

              <div v-else class="p-6 text-center rounded-xl bg-emerald-50/50 border border-emerald-100 text-xs text-emerald-800">
                <component :is="Lucide.CheckCircle" class="w-6 h-6 mx-auto text-emerald-600 mb-1.5" />
                No hay una explicación individual disponible para esta alerta. Revise las métricas y el resumen.
              </div>
            </div>
          </div>

          <!-- Resumen de Métricas Base del Estudiante -->
          <div class="card-menta p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-[#DDEFEA] pb-3">
              <div class="flex items-center gap-2">
                <component :is="Lucide.Activity" class="w-4 h-4 text-[#0F766E]" />
                <h3 class="text-sm font-extrabold text-gray-900">
                  Métricas Académicas Base Analizadas
                </h3>
              </div>

              <Link
                v-if="inscripcionId"
                :href="`${base}/seguimiento/inscripciones/${inscripcionId}`"
                class="text-xs font-bold text-[#0F766E] hover:underline flex items-center gap-1"
              >
                <span>Ver Ficha 360°</span>
                <component :is="Lucide.ExternalLink" class="w-3.5 h-3.5" />
              </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
              <div class="p-3 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA]">
                <span class="text-[10px] font-bold uppercase text-gray-500 block">% Asistencia</span>
                <span class="text-lg font-black text-[#0F766E] mt-0.5 block">
                  {{ metricas.porcentaje_asistencia ?? metricas.asistencia ?? 100 }}%
                </span>
              </div>

              <div class="p-3 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA]">
                <span class="text-[10px] font-bold uppercase text-gray-500 block">Promedio Notas</span>
                <span class="text-lg font-black text-gray-900 mt-0.5 block">
                  {{ metricas.promedio_notas ?? metricas.promedio ?? '—' }} pts
                </span>
              </div>

              <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-100">
                <span class="text-[10px] font-bold uppercase text-rose-700 block">Faltas</span>
                <span class="text-lg font-black text-rose-700 mt-0.5 block">
                  {{ metricas.faltas ?? metricas.total_faltas ?? 0 }}
                </span>
              </div>

              <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100">
                <span class="text-[10px] font-bold uppercase text-amber-700 block">Atrasos</span>
                <span class="text-lg font-black text-amber-700 mt-0.5 block">
                  {{ metricas.atrasos ?? metricas.total_atrasos ?? 0 }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!-- PANEL DERECHO: GESTIÓN DE INTERVENCIONES DE SEGUIMIENTO (HU20)    -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div class="lg:col-span-6 space-y-6">
          <div class="card-menta p-5 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#DDEFEA] pb-3.5">
              <div>
                <div class="flex items-center gap-2">
                  <component :is="Lucide.ClipboardCheck" class="w-5 h-5 text-[#0F766E]" />
                  <h2 class="text-base font-extrabold text-gray-900">
                    Intervenciones Pedagógicas (HU20)
                  </h2>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                  Bitácora de tutorías, refuerzos técnicos, acuerdos con padres y evolución del caso.
                </p>
              </div>

              <button
                v-if="puedeIntervenir"
                type="button"
                @click="mostrarFormularioIntervencion = !mostrarFormularioIntervencion"
                class="btn-menta text-xs !py-2 shrink-0"
              >
                <component :is="mostrarFormularioIntervencion ? Lucide.ChevronUp : Lucide.Plus" class="w-4 h-4" />
                <span>{{ mostrarFormularioIntervencion ? 'Ocultar Formulario' : '+ Registrar Intervención Pedagógica' }}</span>
              </button>
            </div>

            <!-- Formulario Desplegable de Nueva Intervención (HU20) -->
            <form
              v-if="mostrarFormularioIntervencion && puedeIntervenir"
              @submit.prevent="guardarIntervencion"
              class="p-4 rounded-2xl bg-[#F0FDF9]/80 border border-[#DDEFEA] space-y-3.5"
            >
              <h3 class="text-xs font-extrabold uppercase tracking-wider text-[#0F766E] flex items-center gap-1.5">
                <component :is="Lucide.FilePlus2" class="w-4 h-4" />
                Nueva Acción de Intervención Académica
              </h3>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1">Tipo de Intervención *</label>
                  <select v-model="formIntervencion.tipo" required class="input-menta text-xs py-2">
                    <option value="TUTORIA_INDIVIDUAL">Tutoría Individual</option>
                    <option value="REFUERZO_TECNICO">Refuerzo Técnico Musical</option>
                    <option value="CITACION_PADRES">Citación a Padres / Tutores</option>
                    <option value="LLAMADA_SEGUIMIENTO">Llamada de Seguimiento</option>
                    <option value="REPROGRAMACION">Reprogramación / Nivelación</option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1">Fecha de Realización *</label>
                  <input
                    v-model="formIntervencion.fecha"
                    type="date"
                    required
                    class="input-menta text-xs py-2"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold text-gray-700 mb-1">Título de la Intervención *</label>
                  <input
                    v-model="formIntervencion.titulo"
                    type="text"
                    required
                    placeholder="Ej. Tutoría de refuerzo en lectura musical y rítmica"
                    class="input-menta text-xs py-2"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold text-gray-700 mb-1">Descripción de la Acción Realizada *</label>
                  <textarea
                    v-model="formIntervencion.descripcion"
                    rows="3"
                    required
                    placeholder="Detalle los temas abordados, dificultades detectadas y orientación brindada..."
                    class="input-menta text-xs py-2"
                  ></textarea>
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold text-gray-700 mb-1">Compromiso Establecido con el Estudiante / Familia</label>
                  <textarea
                    v-model="formIntervencion.compromiso"
                    rows="2"
                    placeholder="Ej. Práctica diaria de 20 minutos con metrónomo y asistencia puntual..."
                    class="input-menta text-xs py-2"
                  ></textarea>
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold text-gray-700 mb-1">Resultado / Estado Inicial *</label>
                  <select v-model="formIntervencion.resultado" required class="input-menta text-xs py-2">
                    <option value="EN_PROCESO">EN PROCESO</option>
                    <option value="MEJORA_EVIDENTE">MEJORA EVIDENTE</option>
                    <option value="SIN_CAMBIOS">SIN CAMBIOS</option>
                    <option value="REQUIERE_ESCALAMIENTO">REQUIERE ESCALAMIENTO</option>
                  </select>
                </div>
              </div>

              <div class="flex items-center justify-end gap-2 pt-2">
                <button
                  type="button"
                  @click="mostrarFormularioIntervencion = false"
                  class="btn-menta-outline text-xs !py-1.5"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  :disabled="formIntervencion.processing"
                  class="btn-menta text-xs !py-1.5"
                >
                  <component :is="Lucide.Save" class="w-3.5 h-3.5" />
                  <span>{{ formIntervencion.processing ? 'Guardando...' : 'Guardar Intervención' }}</span>
                </button>
              </div>
            </form>

            <!-- LÍNEA DE TIEMPO (TIMELINE) DE INTERVENCIONES REGISTRADAS -->
            <div v-if="listaIntervenciones.length > 0" class="relative pl-6 space-y-5 before:content-[''] before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#A7F3D0]">
              <div
                v-for="intItem in listaIntervenciones"
                :key="intItem.id"
                class="relative p-4 rounded-2xl border border-[#DDEFEA] bg-white hover:shadow-xs transition-shadow space-y-2.5"
              >
                <!-- Punto de la línea de tiempo -->
                <span class="absolute -left-[1.45rem] top-5 w-3.5 h-3.5 rounded-full bg-[#14B8A6] ring-4 ring-[#D1FAE5]"></span>

                <!-- Cabecera de la intervención -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-[#D1FAE5] text-[#0F766E]">
                      {{ getTipoIntervencionLabel(intItem.tipo) }}
                    </span>
                    <span class="text-[11px] font-mono text-gray-400">
                      {{ formatDate(intItem.fecha || intItem.created_at) }}
                    </span>
                  </div>

                  <span
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border self-start sm:self-auto"
                    :class="getResultadoIntervencionBadgeClasses(intItem.resultado)"
                  >
                    {{ getResultadoIntervencionLabel(intItem.resultado) }}
                  </span>
                </div>

                <h4 class="text-sm font-extrabold text-gray-900">
                  {{ intItem.titulo || getTipoIntervencionLabel(intItem.tipo) }}
                </h4>

                <p class="text-xs text-gray-700 leading-relaxed">
                  {{ intItem.descripcion }}
                </p>

                <div
                  v-if="intItem.compromiso"
                  class="p-2.5 rounded-xl bg-amber-50/60 border border-amber-200/70 text-xs text-amber-900"
                >
                  <strong class="font-bold block text-[11px] uppercase tracking-wider text-amber-800">
                    Compromiso Acordado:
                  </strong>
                  <span>{{ intItem.compromiso }}</span>
                </div>

                <!-- Pie de la intervención: Autor + Selector rápido para actualizar resultado -->
                <div class="pt-2 border-t border-[#DDEFEA] flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px] text-gray-500">
                  <span class="flex items-center gap-1">
                    <component :is="Lucide.UserCheck" class="w-3.5 h-3.5 text-[#0F766E]" />
                    Registrado por:
                    <strong class="text-gray-700">{{ getAutorIntervencion(intItem) }}</strong>
                  </span>

                  <div v-if="puedeIntervenir" class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold uppercase text-gray-400">Resultado:</span>
                    <select
                      :value="intItem.resultado"
                      @change="actualizarResultadoIntervencion(intItem, $event.target.value)"
                      class="text-[11px] bg-[#F0FDF9] border border-[#DDEFEA] rounded-lg px-2 py-1 text-[#0F766E] font-bold focus:border-[#14B8A6] focus:outline-none cursor-pointer"
                    >
                      <option value="EN_PROCESO">EN PROCESO</option>
                      <option value="MEJORA_EVIDENTE">MEJORA EVIDENTE</option>
                      <option value="SIN_CAMBIOS">SIN CAMBIOS</option>
                      <option value="REQUIERE_ESCALAMIENTO">REQUIERE ESCALAMIENTO</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State Intervenciones -->
            <div v-else class="p-10 text-center rounded-2xl border border-dashed border-[#DDEFEA] bg-[#F0FDF9]/30">
              <component :is="Lucide.ClipboardList" class="w-10 h-10 mx-auto text-gray-300 mb-2" />
              <h4 class="text-sm font-bold text-gray-800">Sin intervenciones registradas aún</h4>
              <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                Registra una tutoría individual, refuerzo técnico o citación para dar seguimiento formal a esta alerta predictiva.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { textoAporte, clasesAporte, clasesBarraAporte, aclaracionFactor } from './explicacion';
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  alerta: {
    type: Object,
    required: true,
  },
  intervenciones: {
    type: Array,
    default: () => [],
  },
  metricas_base: {
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

const puedeIntervenir = computed(() => {
  if (props.es_alumno || rolActual.value === 'Alumno') return false;
  return ['Propietario', 'Secretaria', 'Docente'].includes(rolActual.value);
});

const alumno = computed(() => {
  return props.alerta?.alumno || props.alerta?.inscripcion?.alumno || {};
});

const alumnoNombreCompleto = computed(() => {
  const al = alumno.value;
  if (al?.nombres || al?.apellidos) {
    return `${al.nombres || ''} ${al.apellidos || ''}`.trim();
  }
  return props.alerta?.alumno_nombre || 'Estudiante';
});

const grupo = computed(() => {
  return props.alerta?.grupo || props.alerta?.inscripcion?.grupo || {};
});

const cursoNombre = computed(() => {
  return grupo.value?.curso?.nombre || props.alerta?.curso_nombre || 'Curso Musical';
});

const docenteNombre = computed(() => {
  const doc = grupo.value?.docente;
  if (doc) return `${doc.nombres || ''} ${doc.apellidos || ''}`.trim();
  return props.alerta?.docente_nombre || 'Docente';
});

const inscripcionId = computed(() => {
  return props.alerta?.inscripcion_id || props.alerta?.inscripcion?.id || null;
});

const modeloVersion = computed(() => {
  return (
    props.alerta?.modelo?.version ||
    props.alerta?.version_modelo?.version ||
    props.alerta?.modelo_version ||
    'v1.1.0 (Random Forest)'
  );
});

const listaFactores = computed(() => {
  const raw = props.alerta?.factores_riesgo;
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
});

const metricas = computed(() => {
  const raw = props.metricas_base && Object.keys(props.metricas_base).length > 0
    ? props.metricas_base
    : (props.alerta?.metricas_base || {});
  if (typeof raw === 'string') {
    try {
      return JSON.parse(raw) || {};
    } catch {
      return {};
    }
  }
  return raw;
});

const listaIntervenciones = computed(() => {
  if (Array.isArray(props.intervenciones) && props.intervenciones.length > 0) {
    return props.intervenciones;
  }
  if (Array.isArray(props.alerta?.intervenciones)) {
    return props.alerta.intervenciones;
  }
  return [];
});

// Acciones de estado de alerta (HU18)
function cambiarEstadoAlerta(nuevoEstado) {
  router.put(
    `${base}/riesgo/alertas/${props.alerta.id}/estado`,
    { estado: nuevoEstado },
    { preserveScroll: true }
  );
}

// Registro y edición de intervenciones (HU20)
const mostrarFormularioIntervencion = ref(false);
const formIntervencion = useForm({
  tipo: 'TUTORIA_INDIVIDUAL',
  fecha: new Date().toISOString().slice(0, 10),
  titulo: '',
  descripcion: '',
  compromiso: '',
  resultado: 'EN_PROCESO',
});

function guardarIntervencion() {
  formIntervencion.post(`${base}/riesgo/alertas/${props.alerta.id}/intervenciones`, {
    preserveScroll: true,
    onSuccess: () => {
      formIntervencion.reset('titulo', 'descripcion', 'compromiso');
      mostrarFormularioIntervencion.value = false;
    },
  });
}

function actualizarResultadoIntervencion(intItem, nuevoResultado) {
  router.put(
    `${base}/riesgo/intervenciones/${intItem.id}`,
    {
      resultado: nuevoResultado,
      compromiso: intItem.compromiso || null,
    },
    { preserveScroll: true }
  );
}

// Helpers visuales y de formato
function iniciales(nombres, apellidos) {
  const n = (nombres || '').trim().charAt(0);
  const a = (apellidos || '').trim().charAt(0);
  return (n + a).toUpperCase() || 'AL';
}

function formatScore(val) {
  const num = Number(val ?? 0);
  return Number.isFinite(num) ? num.toFixed(2) : '—';
}

function formatDate(dateStr) {
  if (!dateStr) return 'Hoy';
  return String(dateStr).slice(0, 10);
}

function generarResumenExplicativoFallback() {
  const score = formatScore(props.alerta?.score_riesgo);
  const nivel = props.alerta?.nivel_riesgo || 'MEDIO';
  return `El modelo predictivo asignó un índice de riesgo ${nivel} (${score}%) en función de la combinación ponderada de asistencia, promedio de evaluaciones y progresión práctica del estudiante.`;
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

function getNivelRiesgoPanelClasses(nivel) {
  switch (nivel) {
    case 'ALTO':
      return 'bg-rose-50/80 border-rose-200 text-rose-900';
    case 'MEDIO':
      return 'bg-amber-50/80 border-amber-200 text-amber-900';
    case 'BAJO':
      return 'bg-emerald-50/80 border-emerald-200 text-emerald-900';
    default:
      return 'bg-gray-50 border-gray-200 text-gray-800';
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

function getNivelRiesgoBarClasses(nivel) {
  switch (nivel) {
    case 'ALTO': return 'bg-rose-500';
    case 'MEDIO': return 'bg-amber-500';
    case 'BAJO': return 'bg-emerald-500';
    default: return 'bg-teal-500';
  }
}

const accionesSugeridas = computed(() => {
  const acciones = metricas.value?.prediccion_ml?.acciones_sugeridas;
  return Array.isArray(acciones) ? acciones.filter(a => typeof a === 'string' && a.trim()) : [];
});

const explicacionModelo = computed(() => metricas.value?.prediccion_ml?.explicacion_modelo || null);

function getCategoriaFactorBadgeClasses(cat) {
  switch (cat) {
    case 'ASISTENCIA':
      return 'bg-rose-50 text-rose-700 border-rose-200';
    case 'ACADEMICO':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'EVOLUCION':
      return 'bg-purple-50 text-purple-700 border-purple-200';
    case 'DISCIPLINA':
      return 'bg-blue-50 text-blue-700 border-blue-200';
    default:
      return 'bg-teal-50 text-teal-700 border-teal-200';
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

function getTipoIntervencionLabel(tipo) {
  switch (tipo) {
    case 'TUTORIA_INDIVIDUAL': return 'Tutoría Individual';
    case 'REFUERZO_TECNICO': return 'Refuerzo Técnico';
    case 'CITACION_PADRES': return 'Citación a Padres';
    case 'LLAMADA_SEGUIMIENTO': return 'Llamada de Seguimiento';
    case 'REPROGRAMACION': return 'Reprogramación';
    default: return tipo || 'Intervención';
  }
}

function getResultadoIntervencionLabel(res) {
  switch (res) {
    case 'EN_PROCESO': return 'EN PROCESO';
    case 'MEJORA_EVIDENTE': return 'MEJORA EVIDENTE';
    case 'SIN_CAMBIOS': return 'SIN CAMBIOS';
    case 'REQUIERE_ESCALAMIENTO': return 'REQUIERE ESCALAMIENTO';
    default: return res || 'EN PROCESO';
  }
}

function getResultadoIntervencionBadgeClasses(res) {
  switch (res) {
    case 'MEJORA_EVIDENTE':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'EN_PROCESO':
      return 'bg-teal-50 text-teal-700 border-teal-200';
    case 'SIN_CAMBIOS':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'REQUIERE_ESCALAMIENTO':
      return 'bg-rose-50 text-rose-700 border-rose-200';
    default:
      return 'bg-gray-50 text-gray-600 border-gray-200';
  }
}

function getAutorIntervencion(intItem) {
  const reg = intItem?.registrado_por || intItem?.registradoPor || intItem?.autor || intItem?.usuario;
  if (reg && typeof reg === 'object') {
    return `${reg.nombres || ''} ${reg.apellidos || ''}`.trim() || 'Docente / Coordinación';
  }
  if (typeof reg === 'string') return reg;
  return intItem?.autor_nombre || 'Equipo Pedagógico';
}
</script>
