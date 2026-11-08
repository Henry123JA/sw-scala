<template>
  <AppLayout>
    <Head :title="`Ficha: ${inscripcion?.alumno?.nombres} ${inscripcion?.alumno?.apellidos} - ${inscripcion?.grupo?.curso?.nombre}`" />

    <div class="space-y-6">
      <!-- Top Bar / Breadcrumbs -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <Link
            :href="buildUrl('/seguimiento')"
            class="btn-menta-outline !p-2 rounded-xl text-gray-500 hover:text-gray-800"
            title="Volver al directorio de seguimiento"
          >
            <component :is="Lucide.ArrowLeft" class="w-5 h-5" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="badge-menta font-mono">{{ inscripcion?.alumno?.codigo }}</span>
              <span class="text-xs text-gray-500 font-medium">Expediente Académico Integral</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight mt-0.5">
              Ficha del Estudiante
            </h1>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center gap-2">
          <button
            v-if="puede_editar"
            type="button"
            @click="modalObservacionOpen = true"
            class="btn-menta text-xs !py-2"
          >
            <component :is="Lucide.PlusCircle" class="w-4 h-4" />
            Nueva Observación
          </button>

          <Link
            v-if="!es_alumno"
            :href="buildUrl(`/progreso/grupos/${inscripcion?.grupo?.id}`)"
            class="btn-menta-outline text-xs !py-2"
          >
            <component :is="Lucide.BarChart2" class="w-4 h-4" />
            Progreso Grupal
          </Link>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- ENCABEZADO DEL ESTUDIANTE (DATOS PERSONALES & CURSO)                  -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="card-menta p-6 bg-gradient-to-r from-white via-[#F8FFFD] to-[#F0FDF9] border border-[#DDEFEA] relative overflow-hidden">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 relative z-10">
          <!-- Foto e Identificación del Alumno -->
          <div class="flex items-center gap-5">
            <div
              class="w-20 h-20 rounded-2xl flex items-center justify-center font-bold text-white text-2xl uppercase shrink-0 shadow-sm ring-4 ring-[#D1FAE5]"
              :style="{ backgroundColor: '#14B8A6' }"
            >
              <img
                v-if="inscripcion?.alumno?.foto_url"
                :src="inscripcion.alumno.foto_url"
                :alt="`${inscripcion.alumno.nombres} ${inscripcion.alumno.apellidos}`"
                class="w-full h-full rounded-2xl object-cover"
              />
              <span v-else>{{ iniciales(inscripcion?.alumno?.nombres, inscripcion?.alumno?.apellidos) }}</span>
            </div>

            <div>
              <div class="flex items-center gap-2.5">
                <h2 class="text-2xl font-black text-gray-900 leading-tight">
                  {{ inscripcion?.alumno?.nombres }} {{ inscripcion?.alumno?.apellidos }}
                </h2>
                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                  :class="getEstadoBadgeClasses(inscripcion?.estado)"
                >
                  {{ inscripcion?.estado }}
                </span>
              </div>

              <!-- Metadatos del Estudiante -->
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 mt-1">
                <span v-if="inscripcion?.alumno?.ci" class="flex items-center gap-1">
                  <component :is="Lucide.CreditCard" class="w-3.5 h-3.5 text-gray-400" />
                  CI: <strong>{{ inscripcion.alumno.ci }}</strong>
                </span>
                <span v-if="inscripcion?.alumno?.email" class="flex items-center gap-1">
                  <component :is="Lucide.Mail" class="w-3.5 h-3.5 text-gray-400" />
                  {{ inscripcion.alumno.email }}
                </span>
                <span v-if="inscripcion?.alumno?.telefono" class="flex items-center gap-1">
                  <component :is="Lucide.Phone" class="w-3.5 h-3.5 text-gray-400" />
                  {{ inscripcion.alumno.telefono }}
                </span>
              </div>

              <!-- Asignatura, Grupo y Docente -->
              <div class="flex flex-wrap items-center gap-3 mt-3 pt-2 border-t border-[#DDEFEA] text-xs">
                <span class="inline-flex items-center gap-1.5 font-bold text-[#0F766E] bg-[#D1FAE5]/60 px-2.5 py-1 rounded-lg">
                  <component :is="Lucide.BookOpen" class="w-3.5 h-3.5" />
                  {{ inscripcion?.grupo?.curso?.nombre }}
                </span>
                <span class="text-gray-600 font-mono font-medium">
                  Grupo: <strong>{{ inscripcion?.grupo?.codigo_grupo }}</strong>
                </span>
                <span class="text-gray-600">
                  Docente: <strong>{{ inscripcion?.grupo?.docente?.nombres }} {{ inscripcion?.grupo?.docente?.apellidos }}</strong>
                </span>
                <span v-if="inscripcion?.grupo?.aula?.nombre" class="text-gray-500">
                  Aula: {{ inscripcion.grupo.aula.nombre }}
                </span>
              </div>
            </div>
          </div>

          <!-- Semáforo Destacado en Header -->
          <div class="flex flex-col sm:items-end gap-1 shrink-0 bg-white/80 p-3.5 rounded-xl border border-[#DDEFEA]">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Semáforo de Rendimiento</span>
            <div
              class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-sm font-black border shadow-xs"
              :class="getSemaforoBadgeClasses(progreso?.semaforo)"
            >
              <span class="relative flex h-2.5 w-2.5">
                <span
                  class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                  :class="getSemaforoDotClasses(progreso?.semaforo)"
                ></span>
                <span
                  class="relative inline-flex rounded-full h-2.5 w-2.5"
                  :class="getSemaforoDotClasses(progreso?.semaforo)"
                ></span>
              </span>
              <span>{{ getSemaforoLabel(progreso?.semaforo) }}</span>
            </div>
            <span class="text-[11px] text-gray-500 text-right max-w-[210px]">
              {{ getSemaforoDescripcion(progreso?.semaforo) }}
            </span>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- TARJETAS DE INDICADORES CLAVE (KPIS HU11)                             -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Promedio General -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
            <component :is="Lucide.Award" class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Promedio Calificación</span>
            <div class="flex items-baseline gap-1 mt-0.5">
              <span
                class="text-2xl font-black"
                :class="progreso?.promedio_general !== null && progreso?.promedio_general >= 51 ? 'text-emerald-700' : progreso?.promedio_general !== null ? 'text-rose-700' : 'text-gray-400'"
              >
                {{ progreso?.promedio_general !== null ? progreso?.promedio_general : '—' }}
              </span>
              <span v-if="progreso?.promedio_general !== null" class="text-xs font-semibold text-gray-500">/ 100 pts</span>
            </div>
            <span class="text-[10px] text-gray-400 block mt-0.5">
              Máx: {{ progreso?.nota_maxima ?? '—' }} | Mín: {{ progreso?.nota_minima ?? '—' }}
            </span>
          </div>
        </div>

        <!-- Asistencia Ponderada -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0 border border-teal-100">
            <component :is="Lucide.CalendarCheck" class="w-6 h-6" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Asistencia Global</span>
            <div class="flex items-baseline gap-1 mt-0.5">
              <span class="text-2xl font-black text-gray-900">{{ progreso?.porcentaje_asistencia ?? 100 }}%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
              <div
                class="h-1.5 rounded-full transition-all"
                :class="(progreso?.porcentaje_asistencia ?? 100) >= 80 ? 'bg-teal-500' : (progreso?.porcentaje_asistencia ?? 100) >= 65 ? 'bg-amber-400' : 'bg-rose-500'"
                :style="{ width: `${Math.min(progreso?.porcentaje_asistencia ?? 100, 100)}%` }"
              ></div>
            </div>
            <span class="text-[10px] text-gray-400 block mt-1">
              {{ progreso?.metricas_asistencia?.presentes ?? 0 }} presentes / {{ progreso?.metricas_asistencia?.faltas ?? 0 }} faltas
            </span>
          </div>
        </div>

        <!-- Total Evaluaciones -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0 border border-sky-100">
            <component :is="Lucide.FileCheck" class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Evaluaciones Rendidas</span>
            <div class="flex items-baseline gap-1 mt-0.5">
              <span class="text-2xl font-black text-gray-900">{{ calificaciones.length }}</span>
              <span class="text-xs text-gray-500">registradas</span>
            </div>
            <span class="text-[10px] text-gray-400 block mt-0.5">Libreta de notas al día</span>
          </div>
        </div>

        <!-- Total Observaciones Pedagógicas -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 border border-purple-100">
            <component :is="Lucide.BookMarked" class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Notas Pedagógicas</span>
            <div class="flex items-baseline gap-1 mt-0.5">
              <span class="text-2xl font-black text-gray-900">{{ observaciones.length }}</span>
              <span class="text-xs text-gray-500">en bitácora</span>
            </div>
            <span class="text-[10px] text-gray-400 block mt-0.5">Seguimiento formativo</span>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- PESTAÑAS ORGANIZADAS (TABS)                                           -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="card-menta overflow-hidden">
        <!-- Navegación de Pestañas -->
        <div class="flex border-b border-[#DDEFEA] bg-[#F8FFFD] px-4">
          <button
            type="button"
            @click="activeTab = 'calificaciones'"
            class="flex items-center gap-2 py-3.5 px-4 text-xs font-bold transition-all border-b-2 cursor-pointer select-none"
            :class="activeTab === 'calificaciones' ? 'border-[#14B8A6] text-[#0F766E]' : 'border-transparent text-gray-500 hover:text-gray-700'"
          >
            <component :is="Lucide.TrendingUp" class="w-4 h-4" />
            <span>1. Evaluaciones y Calificaciones</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 font-mono">
              {{ calificaciones.length }}
            </span>
          </button>

          <button
            type="button"
            @click="activeTab = 'asistencias'"
            class="flex items-center gap-2 py-3.5 px-4 text-xs font-bold transition-all border-b-2 cursor-pointer select-none"
            :class="activeTab === 'asistencias' ? 'border-[#14B8A6] text-[#0F766E]' : 'border-transparent text-gray-500 hover:text-gray-700'"
          >
            <component :is="Lucide.CalendarDays" class="w-4 h-4" />
            <span>2. Historial de Asistencias</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 font-mono">
              {{ asistencias.length }}
            </span>
          </button>

          <button
            type="button"
            @click="activeTab = 'bitacora'"
            class="flex items-center gap-2 py-3.5 px-4 text-xs font-bold transition-all border-b-2 cursor-pointer select-none"
            :class="activeTab === 'bitacora' ? 'border-[#14B8A6] text-[#0F766E]' : 'border-transparent text-gray-500 hover:text-gray-700'"
          >
            <component :is="Lucide.MessageSquareQuote" class="w-4 h-4" />
            <span>3. Bitácora Pedagógica</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 font-mono">
              {{ observaciones.length }}
            </span>
          </button>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!-- PESTAÑA 1: EVALUACIONES Y CALIFICACIONES                         -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div v-if="activeTab === 'calificaciones'" class="p-6 space-y-6">
          <!-- Desglose por Tipo de Evaluación -->
          <div v-if="progreso?.desglose_calificaciones" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div
              v-for="(promTipo, tipo) in progreso.desglose_calificaciones"
              :key="tipo"
              class="p-3 rounded-xl border border-[#DDEFEA] bg-white flex items-center justify-between"
            >
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">{{ tipo }}</span>
                <span
                  class="text-sm font-black mt-0.5 block"
                  :class="promTipo !== null && promTipo >= 51 ? 'text-emerald-700' : promTipo !== null ? 'text-rose-700' : 'text-gray-400'"
                >
                  {{ promTipo !== null ? `${promTipo} pts` : 'Sin nota' }}
                </span>
              </div>
              <span class="p-2 rounded-lg" :class="getTipoBadgeClasses(tipo)">
                <component :is="getTipoIcon(tipo)" class="w-4 h-4" />
              </span>
            </div>
          </div>

          <!-- Tabla de Evaluaciones -->
          <div class="rounded-xl border border-[#DDEFEA] overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead class="table-header-menta">
                  <tr>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Fecha</th>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Evaluación</th>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Tipo</th>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Ponderación</th>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Nota Obtenida</th>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Observaciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#DDEFEA]">
                  <tr
                    v-for="cal in calificaciones"
                    :key="cal.id"
                    class="hover:bg-[#F0FDF9] transition-colors"
                  >
                    <!-- Fecha -->
                    <td class="p-3.5 text-xs font-mono text-gray-700 whitespace-nowrap">
                      {{ cal.fecha || '—' }}
                    </td>

                    <!-- Nombre Evaluación -->
                    <td class="p-3.5">
                      <span class="font-bold text-gray-900 block">{{ cal.nombre }}</span>
                    </td>

                    <!-- Tipo -->
                    <td class="p-3.5">
                      <span
                        class="px-2 py-0.5 rounded text-[11px] font-bold border"
                        :class="getTipoBadgeClasses(cal.tipo)"
                      >
                        {{ cal.tipo }}
                      </span>
                    </td>

                    <!-- Ponderación -->
                    <td class="p-3.5 text-xs font-mono text-gray-600">
                      {{ cal.ponderacion }}%
                    </td>

                    <!-- Nota Obtenida -->
                    <td class="p-3.5">
                      <span
                        v-if="cal.nota !== null"
                        class="text-xs font-black px-2.5 py-1 rounded-lg inline-block border"
                        :class="cal.nota >= 51 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200'"
                      >
                        {{ cal.nota }} pts
                      </span>
                      <span v-else class="text-xs text-gray-400 italic">
                        Pendiente
                      </span>
                    </td>

                    <!-- Observaciones -->
                    <td class="p-3.5 text-xs text-gray-600 max-w-xs">
                      {{ cal.observaciones || 'Sin comentarios registrados.' }}
                    </td>
                  </tr>

                  <!-- Empty State -->
                  <tr v-if="calificaciones.length === 0">
                    <td colspan="6" class="p-8 text-center text-gray-400">
                      <component :is="Lucide.Award" class="w-8 h-8 mx-auto text-gray-300 mb-2" />
                      <p class="font-semibold text-gray-600 text-sm">No hay evaluaciones registradas</p>
                      <p class="text-xs text-gray-400 mt-0.5">Las calificaciones de exámenes y prácticas aparecerán aquí.</p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!-- PESTAÑA 2: HISTORIAL DE ASISTENCIAS                              -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'asistencias'" class="p-6 space-y-6">
          <!-- Resumen de Estados de Asistencia -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3 rounded-xl border border-emerald-200 bg-emerald-50/50 flex items-center justify-between">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 block">Presentes</span>
                <span class="text-lg font-black text-emerald-700 mt-0.5 block">
                  {{ progreso?.metricas_asistencia?.presentes ?? 0 }}
                </span>
              </div>
              <component :is="Lucide.UserCheck" class="w-5 h-5 text-emerald-600" />
            </div>

            <div class="p-3 rounded-xl border border-amber-200 bg-amber-50/50 flex items-center justify-between">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 block">Atrasos</span>
                <span class="text-lg font-black text-amber-700 mt-0.5 block">
                  {{ progreso?.metricas_asistencia?.atrasos ?? 0 }}
                </span>
              </div>
              <component :is="Lucide.Clock" class="w-5 h-5 text-amber-600" />
            </div>

            <div class="p-3 rounded-xl border border-sky-200 bg-sky-50/50 flex items-center justify-between">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-sky-800 block">Licencias</span>
                <span class="text-lg font-black text-sky-700 mt-0.5 block">
                  {{ progreso?.metricas_asistencia?.licencias ?? 0 }}
                </span>
              </div>
              <component :is="Lucide.FileText" class="w-5 h-5 text-sky-600" />
            </div>

            <div class="p-3 rounded-xl border border-rose-200 bg-rose-50/50 flex items-center justify-between">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-800 block">Faltas</span>
                <span class="text-lg font-black text-rose-700 mt-0.5 block">
                  {{ progreso?.metricas_asistencia?.faltas ?? 0 }}
                </span>
              </div>
              <component :is="Lucide.UserX" class="w-5 h-5 text-rose-600" />
            </div>
          </div>

          <!-- Listado Cronológico de Sesiones -->
          <div class="rounded-xl border border-[#DDEFEA] overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead class="table-header-menta">
                  <tr>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Fecha</th>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Tema / Contenido Sesión</th>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Estado</th>
                    <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Observación</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#DDEFEA]">
                  <tr
                    v-for="asist in asistencias"
                    :key="asist.id"
                    class="hover:bg-[#F0FDF9] transition-colors"
                  >
                    <!-- Fecha -->
                    <td class="p-3.5 text-xs font-mono text-gray-700 whitespace-nowrap font-bold">
                      {{ asist.fecha || '—' }}
                    </td>

                    <!-- Tema -->
                    <td class="p-3.5 text-xs text-gray-800">
                      {{ asist.tema || 'Sesión ordinaria de clase' }}
                    </td>

                    <!-- Estado -->
                    <td class="p-3.5">
                      <span
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border"
                        :class="getAsistenciaBadgeClasses(asist.estado)"
                      >
                        <component :is="getAsistenciaIcon(asist.estado)" class="w-3.5 h-3.5" />
                        {{ asist.estado }}
                      </span>
                    </td>

                    <!-- Observación -->
                    <td class="p-3.5 text-xs text-gray-500">
                      {{ asist.observacion || '—' }}
                    </td>
                  </tr>

                  <!-- Empty State -->
                  <tr v-if="asistencias.length === 0">
                    <td colspan="4" class="p-8 text-center text-gray-400">
                      <component :is="Lucide.CalendarCheck" class="w-8 h-8 mx-auto text-gray-300 mb-2" />
                      <p class="font-semibold text-gray-600 text-sm">No hay asistencias registradas</p>
                      <p class="text-xs text-gray-400 mt-0.5">El historial se actualizará conforme el docente tome asistencia.</p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!-- PESTAÑA 3: BITÁCORA PEDAGÓGICA (TIMELINE)                         -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'bitacora'" class="p-6 space-y-6">
          <!-- Barra superior de bitácora -->
          <div class="flex items-center justify-between pb-2 border-b border-[#DDEFEA]">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Observaciones Académicas y Formativas</h3>
              <p class="text-xs text-gray-500">Historial de recomendaciones pedagógicas, postura, afinación y disciplina.</p>
            </div>

            <button
              v-if="puede_editar"
              type="button"
              @click="modalObservacionOpen = true"
              class="btn-menta text-xs !py-1.5"
            >
              <component :is="Lucide.Plus" class="w-4 h-4" />
              Nueva Observación
            </button>
          </div>

          <!-- Timeline Vertical -->
          <div v-if="observaciones.length > 0" class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-[#DDEFEA]">
            <div
              v-for="obs in observaciones"
              :key="obs.id"
              class="relative group"
            >
              <!-- Nodo del timeline -->
              <div
                class="absolute -left-6 top-1.5 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center shadow-xs"
                :class="getObsDotClasses(obs.tipo)"
              ></div>

              <!-- Contenido de la observación -->
              <div class="card-menta p-4 hover:shadow-xs transition-shadow">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-gray-100">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase"
                      :class="getObsBadgeClasses(obs.tipo)"
                    >
                      {{ getObsTipoLabel(obs.tipo) }}
                    </span>
                    <h4 class="font-bold text-gray-900 text-sm">{{ obs.titulo }}</h4>
                  </div>

                  <div class="flex items-center gap-3 text-xs text-gray-400 shrink-0">
                    <span class="flex items-center gap-1 font-mono">
                      <component :is="Lucide.Calendar" class="w-3.5 h-3.5" />
                      {{ obs.fecha }}
                    </span>
                    <!-- Badge Visibilidad -->
                    <span
                      v-if="!es_alumno"
                      class="px-2 py-0.5 rounded text-[10px] font-semibold"
                      :class="obs.visible_para_alumno ? 'bg-teal-50 text-teal-700' : 'bg-gray-100 text-gray-600'"
                    >
                      {{ obs.visible_para_alumno ? 'Visible al Alumno' : 'Privada Docente' }}
                    </span>

                    <!-- Eliminar Observación (Docente/Admin) -->
                    <button
                      v-if="puede_editar"
                      type="button"
                      @click="eliminarObservacion(obs.id)"
                      class="text-gray-300 hover:text-rose-600 transition p-1"
                      title="Eliminar observación"
                    >
                      <component :is="Lucide.Trash2" class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>

                <p class="text-xs text-gray-700 mt-2.5 whitespace-pre-line leading-relaxed">
                  {{ obs.descripcion }}
                </p>

                <div class="mt-3 pt-2 border-t border-gray-100 text-[11px] text-gray-400 flex items-center gap-1">
                  <component :is="Lucide.User" class="w-3 h-3 text-gray-400" />
                  Registrado por: <strong class="text-gray-600">{{ obs.docente_nombre }}</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State Bitácora -->
          <div v-else class="card-menta p-10 text-center text-gray-400">
            <component :is="Lucide.BookOpen" class="w-10 h-10 mx-auto text-gray-300 mb-2" />
            <p class="font-semibold text-gray-600 text-sm">Sin observaciones registradas en la bitácora</p>
            <p class="text-xs text-gray-400 mt-0.5">
              {{ puede_editar ? 'Utiliza el botón superior para ingresar la primera nota pedagógica del alumno.' : 'El docente aún no ha registrado notas formativas para esta materia.' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para Agregar Observación Pedagógica -->
    <ObservacionesModal
      :show="modalObservacionOpen"
      :inscripcion-id="inscripcion?.id"
      @close="modalObservacionOpen = false"
      @created="onObservacionCreada"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ObservacionesModal from '@/Components/ObservacionesModal.vue';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  es_alumno: {
    type: Boolean,
    default: false,
  },
  puede_editar: {
    type: Boolean,
    default: false,
  },
  rol_usuario: {
    type: String,
    default: '',
  },
  inscripcion: {
    type: Object,
    required: true,
  },
  progreso: {
    type: Object,
    default: () => ({}),
  },
  calificaciones: {
    type: Array,
    default: () => [],
  },
  asistencias: {
    type: Array,
    default: () => [],
  },
  observaciones: {
    type: Array,
    default: () => [],
  },
});

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const activeTab = ref('calificaciones');
const modalObservacionOpen = ref(false);

function onObservacionCreada() {
  router.reload({ only: ['observaciones', 'progreso'] });
}

function eliminarObservacion(id) {
  if (!confirm('¿Estás seguro de eliminar esta observación pedagógica?')) return;
  router.delete(buildUrl(`/observaciones/${id}`), {
    preserveScroll: true,
  });
}

function iniciales(nombres, apellidos) {
  const n = (nombres || '').trim().charAt(0);
  const a = (apellidos || '').trim().charAt(0);
  return (n + a).toUpperCase() || 'AL';
}

function getEstadoBadgeClasses(estado) {
  switch (estado) {
    case 'ACTIVA': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'PAUSADA': return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'CANCELADA': return 'bg-rose-50 text-rose-700 border-rose-200';
    default: return 'bg-gray-50 text-gray-600 border-gray-200';
  }
}

function getSemaforoLabel(s) {
  switch (s) {
    case 'EXCELENTE': return 'Excelente';
    case 'FAVORABLE': return 'Favorable';
    case 'REGULAR': return 'Regular';
    case 'EN RIESGO': return 'En Riesgo';
    default: return 'Sin Evaluar';
  }
}

function getSemaforoBadgeClasses(s) {
  switch (s) {
    case 'EXCELENTE': return 'bg-emerald-50 text-emerald-800 border-emerald-300';
    case 'FAVORABLE': return 'bg-teal-50 text-teal-800 border-teal-300';
    case 'REGULAR': return 'bg-amber-50 text-amber-800 border-amber-300';
    case 'EN RIESGO': return 'bg-rose-50 text-rose-800 border-rose-300';
    default: return 'bg-gray-50 text-gray-600 border-gray-200';
  }
}

function getSemaforoDotClasses(s) {
  switch (s) {
    case 'EXCELENTE': return 'bg-emerald-500';
    case 'FAVORABLE': return 'bg-teal-500';
    case 'REGULAR': return 'bg-amber-500';
    case 'EN RIESGO': return 'bg-rose-500';
    default: return 'bg-gray-400';
  }
}

function getSemaforoDescripcion(s) {
  switch (s) {
    case 'EXCELENTE': return 'Promedio ≥ 90 pts y Asistencia ≥ 85%';
    case 'FAVORABLE': return 'Promedio ≥ 70 pts y Asistencia ≥ 75%';
    case 'REGULAR': return 'Rendimiento estándar, requiere constancia';
    case 'EN RIESGO': return 'Alerta: Promedio < 51 pts o Asistencia < 65%';
    default: return 'Aún no se registran evaluaciones suficientes';
  }
}

function getTipoBadgeClasses(tipo) {
  switch (tipo) {
    case 'PRACTICA': return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    case 'TEORIA': return 'bg-sky-50 text-sky-800 border-sky-200';
    case 'RECITAL': return 'bg-purple-50 text-purple-800 border-purple-200';
    case 'AUDICION': return 'bg-amber-50 text-amber-800 border-amber-200';
    default: return 'bg-gray-50 text-gray-700 border-gray-200';
  }
}

function getTipoIcon(tipo) {
  switch (tipo) {
    case 'PRACTICA': return Lucide.Music;
    case 'TEORIA': return Lucide.BookOpen;
    case 'RECITAL': return Lucide.Mic;
    case 'AUDICION': return Lucide.Headphones;
    default: return Lucide.Award;
  }
}

function getAsistenciaBadgeClasses(estado) {
  switch (estado) {
    case 'PRESENTE': return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    case 'ATRASO': return 'bg-amber-50 text-amber-800 border-amber-200';
    case 'LICENCIA': return 'bg-sky-50 text-sky-800 border-sky-200';
    case 'FALTA': return 'bg-rose-50 text-rose-800 border-rose-200';
    default: return 'bg-gray-50 text-gray-700 border-gray-200';
  }
}

function getAsistenciaIcon(estado) {
  switch (estado) {
    case 'PRESENTE': return Lucide.Check;
    case 'ATRASO': return Lucide.Clock;
    case 'LICENCIA': return Lucide.FileText;
    case 'FALTA': return Lucide.X;
    default: return Lucide.HelpCircle;
  }
}

function getObsTipoLabel(tipo) {
  switch (tipo) {
    case 'PROGRESO_TECNICO': return 'Progreso Técnico';
    case 'RECOMENDACION': return 'Recomendación';
    case 'DISCIPLINA': return 'Disciplina';
    case 'FELICITACION': return 'Felicitación';
    default: return tipo;
  }
}

function getObsBadgeClasses(tipo) {
  switch (tipo) {
    case 'PROGRESO_TECNICO': return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    case 'RECOMENDACION': return 'bg-sky-50 text-sky-800 border-sky-200';
    case 'DISCIPLINA': return 'bg-amber-50 text-amber-800 border-amber-200';
    case 'FELICITACION': return 'bg-purple-50 text-purple-800 border-purple-200';
    default: return 'bg-gray-50 text-gray-700 border-gray-200';
  }
}

function getObsDotClasses(tipo) {
  switch (tipo) {
    case 'PROGRESO_TECNICO': return 'bg-emerald-500';
    case 'RECOMENDACION': return 'bg-sky-500';
    case 'DISCIPLINA': return 'bg-amber-500';
    case 'FELICITACION': return 'bg-purple-500';
    default: return 'bg-gray-400';
  }
}
</script>
