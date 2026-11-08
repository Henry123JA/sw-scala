<template>
  <AppLayout>
    <Head title="Progreso Académico" />

    <div class="space-y-6">
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- MODO ALUMNO                                                           -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <template v-if="es_alumno">
        <!-- Banner de Bienvenida y Estado General -->
        <div class="card-menta p-6 bg-gradient-to-r from-white via-[#F8FFFD] to-[#F0FDF9] border border-[#DDEFEA] relative overflow-hidden">
          <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 relative z-10">
            <div class="flex items-center gap-4">
              <div
                class="w-16 h-16 rounded-2xl flex items-center justify-center font-bold text-white text-xl uppercase shrink-0 shadow-sm ring-4 ring-[#D1FAE5]"
                :style="{ backgroundColor: '#14B8A6' }"
              >
                <img
                  v-if="alumno?.foto_url"
                  :src="alumno.foto_url"
                  :alt="`${alumno.nombres} ${alumno.apellidos}`"
                  class="w-full h-full rounded-2xl object-cover"
                />
                <span v-else>{{ iniciales(alumno?.nombres, alumno?.apellidos) }}</span>
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <span class="badge-menta font-mono text-[11px]">{{ alumno?.codigo || 'ALUMNO' }}</span>
                  <span class="text-xs text-gray-500 font-medium">Panel del Estudiante</span>
                </div>
                <h1 class="text-2xl font-black text-gray-900 mt-0.5">
                  {{ alumno?.nombres }} {{ alumno?.apellidos }}
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">
                  Consulta tu rendimiento académico general, asistencia ponderada y evolución por instrumento.
                </p>
              </div>
            </div>

            <!-- Semáforo Principal Badge -->
            <div class="flex flex-col sm:items-end gap-1.5 shrink-0">
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado de Rendimiento</span>
              <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-bold shadow-xs transition-transform hover:scale-102"
                :class="getSemaforoBadgeClasses(resumen_personal?.semaforo)"
              >
                <span class="relative flex h-3 w-3">
                  <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                    :class="getSemaforoDotClasses(resumen_personal?.semaforo)"
                  ></span>
                  <span
                    class="relative inline-flex rounded-full h-3 w-3"
                    :class="getSemaforoDotClasses(resumen_personal?.semaforo)"
                  ></span>
                </span>
                <span>{{ getSemaforoLabel(resumen_personal?.semaforo) }}</span>
              </div>
              <span class="text-[11px] text-gray-400">
                {{ getSemaforoDescripcion(resumen_personal?.semaforo) }}
              </span>
            </div>
          </div>
        </div>

        <!-- KPIs Personales del Estudiante -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Promedio General -->
          <div class="card-menta p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
              <component :is="Lucide.Award" class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Promedio Calificaciones</span>
              <div class="flex items-baseline gap-1 mt-0.5">
                <span class="text-2xl font-black text-gray-900">
                  {{ resumen_personal?.promedio_general !== null ? resumen_personal?.promedio_general : '—' }}
                </span>
                <span v-if="resumen_personal?.promedio_general !== null" class="text-xs font-semibold text-gray-500">/ 100 pts</span>
              </div>
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
                <span class="text-2xl font-black text-gray-900">
                  {{ resumen_personal?.porcentaje_asistencia ?? 100 }}%
                </span>
              </div>
              <!-- Barra mini -->
              <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                <div
                  class="h-1.5 rounded-full transition-all duration-500"
                  :class="(resumen_personal?.porcentaje_asistencia ?? 100) >= 80 ? 'bg-teal-500' : (resumen_personal?.porcentaje_asistencia ?? 100) >= 65 ? 'bg-amber-500' : 'bg-rose-500'"
                  :style="{ width: `${Math.min(resumen_personal?.porcentaje_asistencia ?? 100, 100)}%` }"
                ></div>
              </div>
            </div>
          </div>

          <!-- Total Cursos Inscritos -->
          <div class="card-menta p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0 border border-sky-100">
              <component :is="Lucide.BookOpen" class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Cursos Matriculados</span>
              <span class="text-2xl font-black text-gray-900 mt-0.5 block">
                {{ resumen_personal?.total_cursos ?? cursos.length }}
              </span>
            </div>
          </div>

          <!-- Total Evaluaciones Rendidas -->
          <div class="card-menta p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 border border-purple-100">
              <component :is="Lucide.CheckCircle2" class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Evaluaciones Rendidas</span>
              <span class="text-2xl font-black text-gray-900 mt-0.5 block">
                {{ resumen_personal?.total_evaluaciones ?? 0 }}
              </span>
            </div>
          </div>
        </div>

        <!-- Sección de Tarjetas por Curso -->
        <div>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
              <component :is="Lucide.Music" class="w-5 h-5 text-[#0F766E]" />
              Mis Cursos y Progreso Individual
            </h2>
            <span class="text-xs text-gray-500 font-medium">{{ cursos.length }} curso(s) en curso</span>
          </div>

          <div v-if="cursos.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div
              v-for="c in cursos"
              :key="c.id"
              class="card-menta p-5 hover:shadow-md transition-shadow flex flex-col justify-between"
            >
              <div>
                <!-- Top card: Header curso y semáforo -->
                <div class="flex items-start justify-between gap-2 pb-3 border-b border-[#DDEFEA]">
                  <div>
                    <span class="text-[11px] font-bold text-[#0F766E] uppercase tracking-wider font-mono">
                      {{ c.codigo_grupo }}
                    </span>
                    <h3 class="text-base font-bold text-gray-900 line-clamp-1 mt-0.5">
                      {{ c.curso_nombre }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                      <component :is="Lucide.UserCheck" class="w-3.5 h-3.5 text-gray-400" />
                      {{ c.docente_nombre }}
                    </p>
                  </div>
                  <span
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border shrink-0"
                    :class="getSemaforoBadgeClasses(c.semaforo)"
                  >
                    {{ getSemaforoLabel(c.semaforo) }}
                  </span>
                </div>

                <!-- Métricas del curso -->
                <div class="py-4 space-y-3">
                  <!-- Promedio -->
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600 flex items-center gap-1.5">
                      <component :is="Lucide.Award" class="w-4 h-4 text-gray-400" />
                      Promedio Calificación:
                    </span>
                    <span
                      class="text-sm font-black px-2 py-0.5 rounded-md"
                      :class="c.promedio !== null && c.promedio >= 51 ? 'text-emerald-700 bg-emerald-50' : c.promedio !== null ? 'text-rose-700 bg-rose-50' : 'text-gray-400 bg-gray-50'"
                    >
                      {{ c.promedio !== null ? `${c.promedio} pts` : 'Sin notas' }}
                    </span>
                  </div>

                  <!-- Asistencia -->
                  <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                      <span class="text-gray-600 flex items-center gap-1.5">
                        <component :is="Lucide.CalendarCheck" class="w-4 h-4 text-gray-400" />
                        Asistencia Ponderada:
                      </span>
                      <span class="font-bold text-gray-800">{{ c.porcentaje_asistencia }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                      <div
                        class="h-2 rounded-full transition-all"
                        :class="c.porcentaje_asistencia >= 80 ? 'bg-[#14B8A6]' : c.porcentaje_asistencia >= 65 ? 'bg-amber-400' : 'bg-rose-500'"
                        :style="{ width: `${Math.min(c.porcentaje_asistencia, 100)}%` }"
                      ></div>
                    </div>
                  </div>

                  <!-- Desglose Rápido -->
                  <div class="grid grid-cols-3 gap-1 pt-1 text-center text-[11px] bg-[#F8FFFD] p-2 rounded-lg border border-[#DDEFEA]">
                    <div>
                      <span class="text-gray-400 block text-[10px]">Asistencias</span>
                      <span class="font-bold text-emerald-700">{{ c.presentes ?? 0 }}</span>
                    </div>
                    <div>
                      <span class="text-gray-400 block text-[10px]">Faltas</span>
                      <span class="font-bold text-rose-700">{{ c.faltas ?? 0 }}</span>
                    </div>
                    <div>
                      <span class="text-gray-400 block text-[10px]">Evaluaciones</span>
                      <span class="font-bold text-teal-700">{{ c.total_evaluaciones ?? 0 }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer de la tarjeta: Botón a Ficha Integral -->
              <div class="pt-3 border-t border-[#DDEFEA]">
                <Link
                  :href="buildUrl(`/seguimiento/inscripciones/${c.id}`)"
                  class="btn-menta w-full justify-center !py-2 text-xs"
                >
                  <component :is="Lucide.FileBadge" class="w-4 h-4" />
                  Ver Ficha Integral
                </Link>
              </div>
            </div>
          </div>

          <!-- Empty State Alumno -->
          <div v-else class="card-menta p-12 text-center text-gray-500">
            <component :is="Lucide.BookOpen" class="w-12 h-12 mx-auto text-gray-300 mb-3" />
            <h3 class="text-base font-bold text-gray-800">No tienes inscripciones activas registradas</h3>
            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
              Cuando secretaría registre tus asignaturas e inscripciones grupales, podrás monitorear tu progreso aquí.
            </p>
          </div>
        </div>
      </template>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- MODO DOCENTE / ADMIN (PROPIETARIO / SECRETARIA)                        -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <template v-else>
        <!-- Header con Título Institucional -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="badge-menta">HU11</span>
              <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Progreso Académico</h1>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              Monitoreo analítico del rendimiento, asistencia global y semaforización de grupos activos.
            </p>
          </div>

          <div class="flex items-center gap-2">
            <Link
              :href="buildUrl('/seguimiento')"
              class="btn-menta-outline text-xs !py-2"
            >
              <component :is="Lucide.Users" class="w-4 h-4" />
              Directorio de Estudiantes
            </Link>
          </div>
        </div>

        <!-- Métricas / KPIs Globales -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Promedio General Global -->
          <div class="card-menta p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
              <component :is="Lucide.TrendingUp" class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Promedio Calificaciones</span>
              <div class="flex items-baseline gap-1 mt-0.5">
                <span class="text-2xl font-black text-gray-900">{{ kpis_globales?.promedio_general || 0 }}</span>
                <span class="text-xs font-semibold text-gray-500">pts</span>
              </div>
            </div>
          </div>

          <!-- Asistencia Ponderada Grupal -->
          <div class="card-menta p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0 border border-teal-100">
              <component :is="Lucide.CalendarCheck" class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Asistencia Global</span>
              <div class="flex items-baseline gap-1 mt-0.5">
                <span class="text-2xl font-black text-gray-900">{{ kpis_globales?.porcentaje_asistencia || 0 }}%</span>
              </div>
            </div>
          </div>

          <!-- Tasa de Aprobación -->
          <div class="card-menta p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
              <component :is="Lucide.Award" class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Tasa de Aprobación</span>
              <div class="flex items-baseline gap-1 mt-0.5">
                <span class="text-2xl font-black text-gray-900">{{ kpis_globales?.tasa_aprobacion || 0 }}%</span>
                <span class="text-xs text-gray-500">({{ kpis_globales?.aprobados || 0 }} aprobados)</span>
              </div>
            </div>
          </div>

          <!-- Alumnos en Riesgo -->
          <div class="card-menta p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
              <component :is="Lucide.AlertTriangle" class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Alumnos en Riesgo</span>
              <div class="flex items-baseline gap-1 mt-0.5">
                <span class="text-2xl font-black text-rose-600">{{ kpis_globales?.en_riesgo || 0 }}</span>
                <span class="text-xs text-rose-500">alerta</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Buscador de Grupos -->
        <div class="card-menta p-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
          <div class="relative w-full sm:max-w-md">
            <input
              v-model="search"
              type="text"
              placeholder="Buscar por grupo, curso o docente..."
              class="input-menta !pl-9 text-xs py-2"
            />
            <component :is="Lucide.Search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>

          <div class="text-xs text-gray-500 shrink-0">
            Mostrando <strong>{{ gruposFiltrados.length }}</strong> de <strong>{{ grupos.length }}</strong> grupo(s)
          </div>
        </div>

        <!-- Cuadrícula de Grupos -->
        <div v-if="gruposFiltrados.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="g in gruposFiltrados"
            :key="g.id"
            class="card-menta p-5 hover:shadow-md transition-shadow flex flex-col justify-between"
          >
            <div>
              <!-- Header del Grupo -->
              <div class="flex items-start justify-between gap-2 pb-3 border-b border-[#DDEFEA]">
                <div>
                  <span class="text-[11px] font-bold text-[#0F766E] uppercase tracking-wider font-mono">
                    {{ g.codigo_grupo }}
                  </span>
                  <h3 class="text-base font-bold text-gray-900 line-clamp-1 mt-0.5">
                    {{ g.curso?.nombre }}
                  </h3>
                  <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                    <component :is="Lucide.User" class="w-3.5 h-3.5 text-gray-400" />
                    {{ g.docente?.nombres }} {{ g.docente?.apellidos }}
                  </p>
                </div>
                <span
                  class="px-2.5 py-1 rounded-lg text-xs font-bold border shrink-0"
                  :class="getSemaforoBadgeClasses(g.semaforo)"
                >
                  {{ getSemaforoLabel(g.semaforo) }}
                </span>
              </div>

              <!-- Indicadores del Grupo -->
              <div class="py-4 space-y-3">
                <!-- Estudiantes inscritos -->
                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-600 flex items-center gap-1.5">
                    <component :is="Lucide.Users" class="w-4 h-4 text-gray-400" />
                    Estudiantes Inscritos:
                  </span>
                  <span class="font-bold text-gray-900">{{ g.total_alumnos }} alumnos</span>
                </div>

                <!-- Promedio Grupal -->
                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-600 flex items-center gap-1.5">
                    <component :is="Lucide.Award" class="w-4 h-4 text-gray-400" />
                    Promedio Notas:
                  </span>
                  <span
                    class="font-black px-2 py-0.5 rounded-md"
                    :class="g.promedio_general >= 51 ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50'"
                  >
                    {{ g.promedio_general }} pts
                  </span>
                </div>

                <!-- Asistencia Grupal con barra -->
                <div>
                  <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-gray-600 flex items-center gap-1.5">
                      <component :is="Lucide.CalendarCheck" class="w-4 h-4 text-gray-400" />
                      Asistencia Grupal:
                    </span>
                    <span class="font-bold text-gray-800">{{ g.porcentaje_asistencia }}%</span>
                  </div>
                  <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                    <div
                      class="h-1.5 rounded-full transition-all"
                      :class="g.porcentaje_asistencia >= 80 ? 'bg-teal-500' : g.porcentaje_asistencia >= 65 ? 'bg-amber-400' : 'bg-rose-500'"
                      :style="{ width: `${Math.min(g.porcentaje_asistencia, 100)}%` }"
                    ></div>
                  </div>
                </div>

                <!-- Indicadores de Aprobados y En Riesgo -->
                <div class="grid grid-cols-2 gap-2 pt-1 text-center text-xs">
                  <div class="bg-emerald-50/60 p-2 rounded-lg border border-emerald-100">
                    <span class="text-[10px] uppercase font-semibold text-emerald-800 block">Aprobados</span>
                    <span class="font-black text-emerald-700 text-sm">{{ g.aprobados ?? 0 }}</span>
                  </div>
                  <div class="bg-rose-50/60 p-2 rounded-lg border border-rose-100">
                    <span class="text-[10px] uppercase font-semibold text-rose-800 block">En Riesgo</span>
                    <span class="font-black text-rose-700 text-sm">{{ g.en_riesgo ?? 0 }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer: Botón a Detalle de Grupo -->
            <div class="pt-3 border-t border-[#DDEFEA]">
              <Link
                :href="buildUrl(`/progreso/grupos/${g.id}`)"
                class="btn-menta w-full justify-center !py-2 text-xs"
              >
                <component :is="Lucide.BarChart3" class="w-4 h-4" />
                Ver Detalle de Progreso
              </Link>
            </div>
          </div>
        </div>

        <!-- Empty State Grupos -->
        <div v-else class="card-menta p-12 text-center text-gray-500">
          <component :is="Lucide.Folder" class="w-12 h-12 mx-auto text-gray-300 mb-3" />
          <h3 class="text-base font-bold text-gray-800">No se encontraron grupos</h3>
          <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
            No hay grupos activos que coincidan con el criterio de búsqueda.
          </p>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  es_alumno: {
    type: Boolean,
    default: false,
  },
  rol_usuario: {
    type: String,
    default: '',
  },
  alumno: {
    type: Object,
    default: null,
  },
  resumen_personal: {
    type: Object,
    default: () => ({}),
  },
  cursos: {
    type: Array,
    default: () => [],
  },
  kpis_globales: {
    type: Object,
    default: () => ({}),
  },
  grupos: {
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

const search = ref('');

const gruposFiltrados = computed(() => {
  if (!props.grupos) return [];
  if (!search.value.trim()) return props.grupos;
  const q = search.value.toLowerCase();
  return props.grupos.filter(g =>
    g.codigo_grupo?.toLowerCase().includes(q) ||
    g.curso?.nombre?.toLowerCase().includes(q) ||
    g.docente?.nombres?.toLowerCase().includes(q) ||
    g.docente?.apellidos?.toLowerCase().includes(q)
  );
});

function iniciales(nombres, apellidos) {
  const n = (nombres || '').trim().charAt(0);
  const a = (apellidos || '').trim().charAt(0);
  return (n + a).toUpperCase() || 'AL';
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
    case 'EXCELENTE':
      return 'bg-emerald-50 text-emerald-800 border-emerald-300';
    case 'FAVORABLE':
      return 'bg-teal-50 text-teal-800 border-teal-300';
    case 'REGULAR':
      return 'bg-amber-50 text-amber-800 border-amber-300';
    case 'EN RIESGO':
      return 'bg-rose-50 text-rose-800 border-rose-300';
    default:
      return 'bg-gray-50 text-gray-600 border-gray-200';
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
</script>
