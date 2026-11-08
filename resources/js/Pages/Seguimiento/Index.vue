<template>
  <AppLayout>
    <Head title="Seguimiento Académico - Directorio" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="badge-menta">HU13</span>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Seguimiento Académico</h1>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            Directorio institucional de estudiantes, expedientes digitales y fichas integrales de rendimiento.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Link
            :href="buildUrl('/progreso')"
            class="btn-menta-outline text-xs !py-2"
          >
            <component :is="Lucide.TrendingUp" class="w-4 h-4" />
            Panel de Progreso
          </Link>
        </div>
      </div>

      <!-- Resumen de KPIs de Seguimiento -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Estudiantes -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
            <component :is="Lucide.Users" class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Total Expedientes</span>
            <span class="text-2xl font-black text-gray-900 mt-0.5 block">{{ inscripciones.length }}</span>
          </div>
        </div>

        <!-- Inscripciones Activas -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0 border border-teal-100">
            <component :is="Lucide.CheckCircle" class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Inscripciones Activas</span>
            <span class="text-2xl font-black text-gray-900 mt-0.5 block">{{ totalActivas }}</span>
          </div>
        </div>

        <!-- Alumnos en Riesgo -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
            <component :is="Lucide.AlertTriangle" class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">En Riesgo Académico</span>
            <div class="flex items-baseline gap-1 mt-0.5">
              <span class="text-2xl font-black text-rose-600">{{ totalEnRiesgo }}</span>
              <span class="text-xs text-rose-500">atención</span>
            </div>
          </div>
        </div>

        <!-- Desempeño Favorable / Excelente -->
        <div class="card-menta p-4 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
            <component :is="Lucide.Award" class="w-6 h-6" />
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider block">Sobresalientes / Favorables</span>
            <span class="text-2xl font-black text-emerald-700 mt-0.5 block">{{ totalDestacados }}</span>
          </div>
        </div>
      </div>

      <!-- Barra de Filtros y Búsqueda -->
      <div class="card-menta p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
          <!-- Input Búsqueda -->
          <div class="relative sm:col-span-2">
            <input
              v-model="filtroTexto"
              type="text"
              placeholder="Buscar por alumno, CI, código, curso o grupo..."
              class="input-menta !pl-9 text-xs py-2"
            />
            <component :is="Lucide.Search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>

          <!-- Filtro de Estado -->
          <div>
            <select
              v-model="filtroEstado"
              class="input-menta text-xs py-2"
            >
              <option value="">Todos los Estados</option>
              <option value="ACTIVA">Activa</option>
              <option value="PAUSADA">Pausada</option>
              <option value="CANCELADA">Cancelada</option>
            </select>
          </div>

          <!-- Filtro de Semáforo -->
          <div>
            <select
              v-model="filtroSemaforo"
              class="input-menta text-xs py-2"
            >
              <option value="">Todos los Semáforos</option>
              <option value="EXCELENTE">Excelente</option>
              <option value="FAVORABLE">Favorable</option>
              <option value="REGULAR">Regular</option>
              <option value="EN RIESGO">En Riesgo</option>
            </select>
          </div>
        </div>

        <!-- Barra inferior de filtros: contador y switch de vista -->
        <div class="flex items-center justify-between pt-2 border-t border-[#DDEFEA] text-xs text-gray-500">
          <div>
            Mostrando <strong>{{ inscripcionesFiltradas.length }}</strong> de <strong>{{ inscripciones.length }}</strong> estudiante(s)
          </div>

          <!-- Conmutador Cuadrícula / Tabla -->
          <div class="flex items-center gap-1 bg-gray-100 p-0.5 rounded-lg border border-gray-200">
            <button
              type="button"
              @click="vistaModo = 'cards'"
              class="p-1 rounded-md transition cursor-pointer"
              :class="vistaModo === 'cards' ? 'bg-white text-[#0F766E] shadow-xs' : 'text-gray-400 hover:text-gray-600'"
              title="Vista en Tarjetas"
            >
              <component :is="Lucide.LayoutGrid" class="w-4 h-4" />
            </button>
            <button
              type="button"
              @click="vistaModo = 'table'"
              class="p-1 rounded-md transition cursor-pointer"
              :class="vistaModo === 'table' ? 'bg-white text-[#0F766E] shadow-xs' : 'text-gray-400 hover:text-gray-600'"
              title="Vista en Tabla"
            >
              <component :is="Lucide.List" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- VISTA 1: TARJETAS DE ESTUDIANTES                                      -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div v-if="vistaModo === 'cards' && inscripcionesFiltradas.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="ins in inscripcionesFiltradas"
          :key="ins.id"
          class="card-menta p-5 hover:shadow-md transition-shadow flex flex-col justify-between"
        >
          <div>
            <!-- Header Estudiante con Avatar y Estado -->
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-[#DDEFEA]">
              <div class="flex items-center gap-3">
                <div
                  class="w-11 h-11 rounded-2xl flex items-center justify-center font-bold text-white text-sm uppercase shrink-0 shadow-xs ring-2 ring-[#D1FAE5]"
                  :style="{ backgroundColor: '#14B8A6' }"
                >
                  <img
                    v-if="ins.alumno?.foto_url"
                    :src="ins.alumno.foto_url"
                    :alt="`${ins.alumno.nombres} ${ins.alumno.apellidos}`"
                    class="w-full h-full rounded-2xl object-cover"
                  />
                  <span v-else>{{ iniciales(ins.alumno?.nombres, ins.alumno?.apellidos) }}</span>
                </div>
                <div>
                  <h3 class="font-bold text-gray-900 text-sm line-clamp-1 leading-tight">
                    {{ ins.alumno?.nombres }} {{ ins.alumno?.apellidos }}
                  </h3>
                  <span class="text-[11px] font-mono text-gray-400 block mt-0.5">
                    {{ ins.alumno?.codigo }}
                  </span>
                </div>
              </div>

              <!-- Badge Estado Inscripción -->
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 border"
                :class="getEstadoBadgeClasses(ins.estado)"
              >
                {{ ins.estado }}
              </span>
            </div>

            <!-- Datos Académicos (Curso, Grupo, Docente) -->
            <div class="py-3 space-y-2 text-xs border-b border-[#DDEFEA]">
              <div class="flex items-center justify-between">
                <span class="text-gray-500 flex items-center gap-1.5">
                  <component :is="Lucide.BookOpen" class="w-3.5 h-3.5 text-gray-400" />
                  Curso:
                </span>
                <span class="font-bold text-gray-900 line-clamp-1 text-right">{{ ins.grupo?.curso?.nombre }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-gray-500 flex items-center gap-1.5">
                  <component :is="Lucide.Folder" class="w-3.5 h-3.5 text-gray-400" />
                  Grupo:
                </span>
                <span class="font-mono font-semibold text-[#0F766E]">{{ ins.grupo?.codigo_grupo }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-gray-500 flex items-center gap-1.5">
                  <component :is="Lucide.UserCheck" class="w-3.5 h-3.5 text-gray-400" />
                  Docente:
                </span>
                <span class="text-gray-700 line-clamp-1 text-right">
                  {{ ins.grupo?.docente?.nombres }} {{ ins.grupo?.docente?.apellidos }}
                </span>
              </div>
            </div>

            <!-- Indicadores Rápidos (Semáforo, Promedio, Asistencia) -->
            <div class="py-3 space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500 uppercase font-semibold tracking-wider text-[10px]">
                  Rendimiento:
                </span>
                <span
                  class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-bold border"
                  :class="getSemaforoBadgeClasses(ins.progreso?.semaforo)"
                >
                  <span class="w-2 h-2 rounded-full" :class="getSemaforoDotClasses(ins.progreso?.semaforo)"></span>
                  {{ getSemaforoLabel(ins.progreso?.semaforo) }}
                </span>
              </div>

              <div class="grid grid-cols-2 gap-2 text-center text-xs">
                <!-- Promedio -->
                <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                  <span class="text-[10px] text-gray-400 block uppercase font-medium">Promedio</span>
                  <span
                    class="font-black text-sm"
                    :class="ins.progreso?.promedio !== null && ins.progreso?.promedio >= 51 ? 'text-emerald-700' : ins.progreso?.promedio !== null ? 'text-rose-700' : 'text-gray-400'"
                  >
                    {{ ins.progreso?.promedio !== null ? `${ins.progreso?.promedio} pts` : '—' }}
                  </span>
                </div>

                <!-- Asistencia -->
                <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                  <span class="text-[10px] text-gray-400 block uppercase font-medium">Asistencia</span>
                  <span class="font-black text-sm text-gray-800">
                    {{ ins.progreso?.porcentaje_asistencia ?? 100 }}%
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Botón de acción: Ficha Integral -->
          <div class="pt-3 border-t border-[#DDEFEA]">
            <Link
              :href="buildUrl(`/seguimiento/inscripciones/${ins.id}`)"
              class="btn-menta w-full justify-center !py-2 text-xs"
            >
              <component :is="Lucide.FileBadge" class="w-4 h-4" />
              Ver Ficha Integral
            </Link>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- VISTA 2: TABLA DE ESTUDIANTES                                         -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div v-else-if="vistaModo === 'table' && inscripcionesFiltradas.length > 0" class="card-menta overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="table-header-menta">
              <tr>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Estudiante</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Curso / Grupo</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Docente</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Promedio</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Asistencia</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Semáforo</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Estado</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#DDEFEA]">
              <tr
                v-for="ins in inscripcionesFiltradas"
                :key="ins.id"
                class="hover:bg-[#F0FDF9] transition-colors"
              >
                <td class="p-3.5">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-white text-xs uppercase shrink-0 ring-1 ring-[#D1FAE5]"
                      :style="{ backgroundColor: '#14B8A6' }"
                    >
                      <img
                        v-if="ins.alumno?.foto_url"
                        :src="ins.alumno.foto_url"
                        :alt="`${ins.alumno.nombres} ${ins.alumno.apellidos}`"
                        class="w-full h-full rounded-full object-cover"
                      />
                      <span v-else>{{ iniciales(ins.alumno?.nombres, ins.alumno?.apellidos) }}</span>
                    </div>
                    <div>
                      <span class="font-bold text-gray-900 block leading-tight">
                        {{ ins.alumno?.nombres }} {{ ins.alumno?.apellidos }}
                      </span>
                      <span class="text-[11px] text-gray-400 font-mono">{{ ins.alumno?.codigo }}</span>
                    </div>
                  </div>
                </td>

                <td class="p-3.5">
                  <span class="font-semibold text-gray-900 block leading-tight">{{ ins.grupo?.curso?.nombre }}</span>
                  <span class="text-[11px] font-mono text-[#0F766E]">{{ ins.grupo?.codigo_grupo }}</span>
                </td>

                <td class="p-3.5 text-xs text-gray-600">
                  {{ ins.grupo?.docente?.nombres }} {{ ins.grupo?.docente?.apellidos }}
                </td>

                <td class="p-3.5">
                  <span
                    v-if="ins.progreso?.promedio !== null"
                    class="text-xs font-black px-2 py-0.5 rounded"
                    :class="ins.progreso?.promedio >= 51 ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50'"
                  >
                    {{ ins.progreso?.promedio }} pts
                  </span>
                  <span v-else class="text-xs text-gray-400 italic">—</span>
                </td>

                <td class="p-3.5 text-xs font-bold text-gray-800">
                  {{ ins.progreso?.porcentaje_asistencia ?? 100 }}%
                </td>

                <td class="p-3.5">
                  <span
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold border"
                    :class="getSemaforoBadgeClasses(ins.progreso?.semaforo)"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="getSemaforoDotClasses(ins.progreso?.semaforo)"></span>
                    {{ getSemaforoLabel(ins.progreso?.semaforo) }}
                  </span>
                </td>

                <td class="p-3.5">
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                    :class="getEstadoBadgeClasses(ins.estado)"
                  >
                    {{ ins.estado }}
                  </span>
                </td>

                <td class="p-3.5 text-right">
                  <Link
                    :href="buildUrl(`/seguimiento/inscripciones/${ins.id}`)"
                    class="btn-menta !py-1.5 !px-3 text-xs inline-flex items-center gap-1"
                  >
                    <component :is="Lucide.FileBadge" class="w-3.5 h-3.5" />
                    <span>Ficha</span>
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="inscripcionesFiltradas.length === 0" class="card-menta p-12 text-center text-gray-500">
        <component :is="Lucide.Users" class="w-12 h-12 mx-auto text-gray-300 mb-3" />
        <h3 class="text-base font-bold text-gray-800">No se encontraron estudiantes</h3>
        <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
          No hay estudiantes inscritos que coincidan con los filtros aplicados.
        </p>
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
  es_alumno: {
    type: Boolean,
    default: false,
  },
  rol_usuario: {
    type: String,
    default: '',
  },
  inscripciones: {
    type: Array,
    default: () => [],
  },
  filtros: {
    type: Object,
    default: () => ({}),
  },
  cursos: {
    type: Array,
    default: () => [],
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

const filtroTexto = ref(props.filtros?.search || '');
const filtroEstado = ref(props.filtros?.estado || '');
const filtroSemaforo = ref('');
const vistaModo = ref('cards');

const inscripcionesFiltradas = computed(() => {
  if (!props.inscripciones) return [];
  return props.inscripciones.filter(ins => {
    // Filtro estado
    if (filtroEstado.value && ins.estado !== filtroEstado.value) {
      return false;
    }
    // Filtro semáforo
    if (filtroSemaforo.value && ins.progreso?.semaforo !== filtroSemaforo.value) {
      return false;
    }
    // Filtro texto
    if (!filtroTexto.value.trim()) return true;
    const q = filtroTexto.value.toLowerCase();
    return (
      ins.alumno?.nombres?.toLowerCase().includes(q) ||
      ins.alumno?.apellidos?.toLowerCase().includes(q) ||
      ins.alumno?.codigo?.toLowerCase().includes(q) ||
      ins.grupo?.curso?.nombre?.toLowerCase().includes(q) ||
      ins.grupo?.codigo_grupo?.toLowerCase().includes(q)
    );
  });
});

const totalActivas = computed(() => {
  return props.inscripciones.filter(i => i.estado === 'ACTIVA').length;
});

const totalEnRiesgo = computed(() => {
  return props.inscripciones.filter(i => i.progreso?.semaforo === 'EN RIESGO').length;
});

const totalDestacados = computed(() => {
  return props.inscripciones.filter(i => i.progreso?.semaforo === 'EXCELENTE' || i.progreso?.semaforo === 'FAVORABLE').length;
});

function iniciales(nombres, apellidos) {
  const n = (nombres || '').trim().charAt(0);
  const a = (apellidos || '').trim().charAt(0);
  return (n + a).toUpperCase() || 'AL';
}

function getEstadoBadgeClasses(estado) {
  switch (estado) {
    case 'ACTIVA':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'PAUSADA':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'CANCELADA':
      return 'bg-rose-50 text-rose-700 border-rose-200';
    default:
      return 'bg-gray-50 text-gray-600 border-gray-200';
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
