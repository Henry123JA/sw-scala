<template>
  <AppLayout>
    <Head :title="`Progreso: ${grupo?.codigo_grupo} - ${grupo?.curso?.nombre}`" />

    <div class="space-y-6">
      <!-- Top Bar / Breadcrumb -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <Link
            :href="buildUrl('/progreso')"
            class="btn-menta-outline !p-2 rounded-xl text-gray-500 hover:text-gray-800"
            title="Volver al panel de progreso"
          >
            <component :is="Lucide.ArrowLeft" class="w-5 h-5" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="badge-menta font-mono">{{ grupo?.codigo_grupo }}</span>
              <span class="text-xs text-gray-500 font-medium">Detalle Analítico de Grupo</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight mt-0.5">
              {{ grupo?.curso?.nombre }}
            </h1>
          </div>
        </div>

        <!-- Acciones rápidas -->
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

      <!-- Banner Informativo del Grupo -->
      <div class="card-menta p-6 bg-gradient-to-r from-white via-[#F8FFFD] to-[#F0FDF9] border border-[#DDEFEA]">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
          <div class="space-y-1">
            <span class="text-xs uppercase font-semibold text-gray-400 tracking-wider">Docente Asignado</span>
            <div class="flex items-center gap-2 text-gray-900 font-bold">
              <component :is="Lucide.UserCheck" class="w-4 h-4 text-[#0F766E]" />
              <span>{{ grupo?.docente?.nombres }} {{ grupo?.docente?.apellidos }}</span>
            </div>
            <p v-if="grupo?.docente?.email" class="text-xs text-gray-500">{{ grupo.docente.email }}</p>
          </div>

          <div class="space-y-1">
            <span class="text-xs uppercase font-semibold text-gray-400 tracking-wider">Estudiantes Inscritos</span>
            <div class="flex items-center gap-2 text-gray-900 font-bold">
              <component :is="Lucide.GraduationCap" class="w-4 h-4 text-[#0F766E]" />
              <span>{{ metricas?.total_alumnos || alumnos.length }} estudiantes matriculados</span>
            </div>
            <p class="text-xs text-gray-500">Inscripciones con seguimiento activo</p>
          </div>

          <!-- Distribución rápida del semáforo -->
          <div class="space-y-1">
            <span class="text-xs uppercase font-semibold text-gray-400 tracking-wider">Distribución Semáforo</span>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">
                {{ metricas?.distribucion?.EXCELENTE ?? 0 }} Exc.
              </span>
              <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-teal-100 text-teal-800">
                {{ metricas?.distribucion?.FAVORABLE ?? 0 }} Fav.
              </span>
              <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800">
                {{ metricas?.distribucion?.REGULAR ?? 0 }} Reg.
              </span>
              <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800">
                {{ metricas?.distribucion?.['EN RIESGO'] ?? metricas?.en_riesgo ?? 0 }} Riesgo
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- KPIs Globales del Grupo -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Promedio Grupal -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
            <component :is="Lucide.Award" class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Promedio Calificaciones</span>
            <div class="flex items-baseline gap-1 mt-0.5">
              <span class="text-2xl font-black text-gray-900">{{ metricas?.promedio_general ?? 0 }}</span>
              <span class="text-xs font-semibold text-gray-500">pts</span>
            </div>
          </div>
        </div>

        <!-- Asistencia Grupal -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0 border border-teal-100">
            <component :is="Lucide.CalendarCheck" class="w-6 h-6" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Asistencia Grupal</span>
            <div class="flex items-baseline gap-1 mt-0.5">
              <span class="text-2xl font-black text-gray-900">{{ metricas?.porcentaje_asistencia ?? 0 }}%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1 overflow-hidden">
              <div
                class="h-1.5 rounded-full transition-all"
                :class="(metricas?.porcentaje_asistencia ?? 0) >= 80 ? 'bg-teal-500' : (metricas?.porcentaje_asistencia ?? 0) >= 65 ? 'bg-amber-400' : 'bg-rose-500'"
                :style="{ width: `${Math.min(metricas?.porcentaje_asistencia ?? 0, 100)}%` }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Tasa de Aprobación -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
            <component :is="Lucide.CheckCircle2" class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Tasa de Aprobación</span>
            <div class="flex items-baseline gap-1 mt-0.5">
              <span class="text-2xl font-black text-gray-900">{{ metricas?.tasa_aprobacion ?? 0 }}%</span>
              <span class="text-xs text-gray-500">({{ metricas?.aprobados ?? 0 }} aprobados)</span>
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
              <span class="text-2xl font-black text-rose-600">{{ metricas?.en_riesgo ?? 0 }}</span>
              <span class="text-xs text-rose-500">requieren apoyo</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Buscador y Filtro por Semáforo -->
      <div class="card-menta p-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div class="relative w-full sm:max-w-md">
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Buscar alumno por nombre, apellido o código..."
            class="input-menta !pl-9 text-xs py-2"
          />
          <component :is="Lucide.Search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>

        <!-- Filtro rápido por Semáforo -->
        <div class="flex items-center gap-1.5 flex-wrap">
          <button
            type="button"
            @click="filtroSemaforo = ''"
            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
            :class="filtroSemaforo === '' ? 'bg-[#0F766E] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          >
            Todos ({{ alumnos.length }})
          </button>
          <button
            type="button"
            @click="filtroSemaforo = 'EXCELENTE'"
            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
            :class="filtroSemaforo === 'EXCELENTE' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100'"
          >
            Excelente
          </button>
          <button
            type="button"
            @click="filtroSemaforo = 'FAVORABLE'"
            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
            :class="filtroSemaforo === 'FAVORABLE' ? 'bg-teal-600 text-white' : 'bg-teal-50 text-teal-800 hover:bg-teal-100'"
          >
            Favorable
          </button>
          <button
            type="button"
            @click="filtroSemaforo = 'REGULAR'"
            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
            :class="filtroSemaforo === 'REGULAR' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'"
          >
            Regular
          </button>
          <button
            type="button"
            @click="filtroSemaforo = 'EN RIESGO'"
            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
            :class="filtroSemaforo === 'EN RIESGO' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-800 hover:bg-rose-100'"
          >
            En Riesgo
          </button>
        </div>
      </div>

      <!-- Tabla de Alumnos del Grupo -->
      <div class="card-menta overflow-hidden">
        <div class="p-4 border-b border-[#DDEFEA] bg-[#F8FFFD] flex items-center justify-between">
          <h2 class="text-sm font-bold text-[#0F766E] uppercase tracking-wider flex items-center gap-2">
            <component :is="Lucide.Users" class="w-4 h-4" />
            Nómina de Estudiantes y Rendimiento
          </h2>
          <span class="text-xs text-gray-500">
            {{ alumnosFiltrados.length }} estudiante(s) listado(s)
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="table-header-menta">
              <tr>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Estudiante</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Asistencia %</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Promedio Notas</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Evaluaciones</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Semáforo</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#DDEFEA]">
              <tr
                v-for="a in alumnosFiltrados"
                :key="a.inscripcion_id"
                class="hover:bg-[#F0FDF9] transition-colors"
              >
                <!-- Alumno -->
                <td class="p-3.5">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-9 h-9 rounded-full overflow-hidden flex items-center justify-center font-bold text-white text-xs uppercase shrink-0 shadow-xs ring-2 ring-[#D1FAE5]"
                      :style="{ backgroundColor: '#14B8A6' }"
                    >
                      <img
                        v-if="a.foto_url"
                        :src="a.foto_url"
                        :alt="`${a.nombres} ${a.apellidos}`"
                        class="w-full h-full object-cover"
                      />
                      <span v-else>{{ iniciales(a.nombres, a.apellidos) }}</span>
                    </div>
                    <div>
                      <span class="font-bold text-gray-900 block leading-tight">
                        {{ a.nombres }} {{ a.apellidos }}
                      </span>
                      <span class="text-[11px] text-gray-400 font-mono">
                        {{ a.codigo }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Asistencia % -->
                <td class="p-3.5">
                  <div class="w-32">
                    <div class="flex items-center justify-between text-xs mb-1">
                      <span class="font-bold text-gray-800">{{ a.porcentaje_asistencia }}%</span>
                      <span class="text-[10px] text-gray-400">
                        {{ a.asistencias_presentes }}P / {{ a.asistencias_faltas }}F
                      </span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                      <div
                        class="h-1.5 rounded-full"
                        :class="a.porcentaje_asistencia >= 80 ? 'bg-teal-500' : a.porcentaje_asistencia >= 65 ? 'bg-amber-400' : 'bg-rose-500'"
                        :style="{ width: `${Math.min(a.porcentaje_asistencia, 100)}%` }"
                      ></div>
                    </div>
                  </div>
                </td>

                <!-- Promedio Notas -->
                <td class="p-3.5">
                  <span
                    v-if="a.promedio !== null"
                    class="text-xs font-black px-2.5 py-1 rounded-lg inline-block border"
                    :class="a.promedio >= 51 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200'"
                  >
                    {{ a.promedio }} pts
                  </span>
                  <span v-else class="text-xs text-gray-400 italic">
                    Sin calificaciones
                  </span>
                </td>

                <!-- Evaluaciones rendidas -->
                <td class="p-3.5 text-xs text-gray-600">
                  <span class="font-bold text-gray-800">{{ a.evaluaciones_rendidas ?? 0 }}</span> evaluacion(es)
                </td>

                <!-- Semáforo -->
                <td class="p-3.5">
                  <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border"
                    :class="getSemaforoBadgeClasses(a.semaforo)"
                  >
                    <span class="w-2 h-2 rounded-full" :class="getSemaforoDotClasses(a.semaforo)"></span>
                    {{ getSemaforoLabel(a.semaforo) }}
                  </span>
                </td>

                <!-- Botón directo a Ficha Integral -->
                <td class="p-3.5 text-right">
                  <Link
                    :href="buildUrl(`/seguimiento/inscripciones/${a.inscripcion_id}`)"
                    class="btn-menta !py-1.5 !px-3 text-xs inline-flex items-center gap-1.5"
                  >
                    <component :is="Lucide.FileBadge" class="w-3.5 h-3.5" />
                    <span>Ficha Integral</span>
                  </Link>
                </td>
              </tr>

              <!-- Empty state de búsqueda -->
              <tr v-if="alumnosFiltrados.length === 0">
                <td colspan="6" class="p-8 text-center text-gray-400">
                  <component :is="Lucide.Users" class="w-8 h-8 mx-auto text-gray-300 mb-2" />
                  <p class="font-semibold text-gray-600 text-sm">No se encontraron estudiantes</p>
                  <p class="text-xs text-gray-400 mt-0.5">Intenta cambiar el término de búsqueda o el filtro de semáforo.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  grupo: {
    type: Object,
    required: true,
  },
  metricas: {
    type: Object,
    default: () => ({}),
  },
  alumnos: {
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

const searchTerm = ref('');
const filtroSemaforo = ref('');

const alumnosFiltrados = computed(() => {
  if (!props.alumnos) return [];
  return props.alumnos.filter(a => {
    // Filtro semáforo
    if (filtroSemaforo.value && a.semaforo !== filtroSemaforo.value) {
      return false;
    }
    // Filtro texto
    if (!searchTerm.value.trim()) return true;
    const q = searchTerm.value.toLowerCase();
    return (
      a.nombres?.toLowerCase().includes(q) ||
      a.apellidos?.toLowerCase().includes(q) ||
      a.codigo?.toLowerCase().includes(q) ||
      a.email?.toLowerCase().includes(q)
    );
  });
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
</script>
