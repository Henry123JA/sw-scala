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
  resumen: {
    type: Object,
    default: () => ({
      total_sesiones: 0,
      total_presentes: 0,
      total_faltas: 0,
      total_licencias: 0,
      total_atrasos: 0,
      total_calificados: 0,
      promedio_asistencia: 0,
    }),
  },
  sesiones: {
    type: Array,
    default: () => [],
  },
});

const base = (import.meta.env.VITE_BASE_URL || '').replace(/\/$/, '');

// Filtro rápido por fecha o texto
const filtroFecha = ref('');
const ordenAscendente = ref(false);

const sesionesFiltradas = computed(() => {
  let lista = [...props.sesiones];

  if (filtroFecha.value.trim()) {
    const q = filtroFecha.value.toLowerCase().trim();
    lista = lista.filter(s => s.fecha && s.fecha.includes(q));
  }

  if (ordenAscendente.value) {
    lista.sort((a, b) => (a.fecha > b.fecha ? 1 : -1));
  } else {
    lista.sort((a, b) => (a.fecha < b.fecha ? 1 : -1));
  }

  return lista;
});

function formatearFecha(fechaStr) {
  if (!fechaStr) return '—';
  try {
    const partes = fechaStr.slice(0, 10).split('-');
    if (partes.length === 3) {
      const year = parseInt(partes[0], 10);
      const month = parseInt(partes[1], 10) - 1;
      const day = parseInt(partes[2], 10);
      const d = new Date(year, month, day);
      return d.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    }
    return fechaStr;
  } catch (e) {
    return fechaStr;
  }
}

function getPorcentajeClass(pct) {
  if (pct >= 80) return 'text-emerald-700 bg-emerald-50 border-emerald-200';
  if (pct >= 60) return 'text-amber-700 bg-amber-50 border-amber-200';
  return 'text-rose-700 bg-rose-50 border-rose-200';
}
</script>

<template>
  <Head :title="`Historial de Asistencias - ${grupo.codigo_grupo}`" />

  <AppLayout>
    <div class="space-y-6 max-w-6xl">

      <!-- ── Encabezado y Navegación ────────────────────────────────────────── -->
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <Link
            :href="`${base}/asistencias`"
            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#0F766E] hover:underline mb-2 transition-colors"
          >
            <component :is="Lucide.ArrowLeft" class="w-3.5 h-3.5" />
            Volver a Asistencias
          </Link>

          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
              <span class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
                <component :is="Lucide.History" class="w-5 h-5" />
              </span>
              Historial de Asistencias
            </h1>
            <span class="badge-menta font-mono text-xs font-extrabold tracking-wider">
              {{ grupo.codigo_grupo }}
            </span>
          </div>

          <div class="mt-2 flex flex-wrap items-center gap-y-1 gap-x-3 text-xs text-gray-600">
            <span class="font-bold text-gray-900 text-sm flex items-center gap-1">
              <component :is="Lucide.BookOpen" class="w-3.5 h-3.5 text-[#14B8A6]" />
              {{ grupo.curso?.nombre }}
            </span>
            <span class="text-gray-300">•</span>
            <span class="flex items-center gap-1">
              <component :is="Lucide.User" class="w-3.5 h-3.5 text-gray-400" />
              Docente: <strong class="text-gray-800">{{ grupo.docente?.nombre_completo || 'Sin asignar' }}</strong>
            </span>
            <span class="text-gray-300">•</span>
            <span class="flex items-center gap-1">
              <component :is="Lucide.Users" class="w-3.5 h-3.5 text-gray-400" />
              <strong class="text-[#0F766E]">{{ grupo.alumnos_inscritos ?? 0 }}</strong> alumnos activos
            </span>
            <span v-if="grupo.nivel" class="text-gray-300">•</span>
            <span v-if="grupo.nivel" class="badge-menta py-0 text-[10px]">
              {{ grupo.nivel }}
            </span>
          </div>

          <!-- Horarios asignados al grupo -->
          <div v-if="grupo.horarios && grupo.horarios.length > 0" class="mt-2 flex flex-wrap items-center gap-2">
            <span
              v-for="h in grupo.horarios"
              :key="h.id"
              class="inline-flex items-center gap-1 text-[11px] font-medium bg-[#F0FDF9] text-[#0F766E] border border-[#DDEFEA] px-2.5 py-0.5 rounded-md"
            >
              <component :is="Lucide.Clock" class="w-3 h-3 text-[#14B8A6]" />
              {{ h.dia_semana }} {{ h.hora_inicio }} - {{ h.hora_fin }}
              <span v-if="h.sala" class="text-gray-400 font-normal">({{ h.sala }})</span>
            </span>
          </div>
        </div>

        <!-- Botón para ir directo a la gestión principal -->
        <div class="flex items-center gap-2">
          <Link
            :href="`${base}/asistencias`"
            class="btn-menta-outline text-xs py-2 px-3.5 shadow-2xs"
          >
            <component :is="Lucide.LayoutGrid" class="w-4 h-4" />
            Todos los Grupos
          </Link>
        </div>
      </div>

      <!-- ── Resumen KPI Global del Grupo ──────────────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Total Sesiones -->
        <div class="card-menta p-4 flex flex-col justify-between">
          <div class="text-[11px] uppercase tracking-wider font-bold text-gray-400 flex items-center gap-1">
            <component :is="Lucide.CalendarDays" class="w-3.5 h-3.5 text-[#14B8A6]" />
            Sesiones
          </div>
          <div class="mt-2">
            <span class="text-2xl font-black text-gray-900">{{ resumen.total_sesiones }}</span>
            <span class="text-[11px] text-gray-500 block">registradas</span>
          </div>
        </div>

        <!-- Promedio Puntualidad -->
        <div class="card-menta p-4 flex flex-col justify-between">
          <div class="text-[11px] uppercase tracking-wider font-bold text-gray-400 flex items-center gap-1">
            <component :is="Lucide.TrendingUp" class="w-3.5 h-3.5 text-[#14B8A6]" />
            Asistencia
          </div>
          <div class="mt-2">
            <span class="text-2xl font-black text-[#0F766E]">{{ resumen.promedio_asistencia }}%</span>
            <span class="text-[11px] text-gray-500 block">promedio ponderado</span>
          </div>
        </div>

        <!-- Presentes Acumulados -->
        <div class="card-menta p-4 flex flex-col justify-between bg-emerald-50/40 border-emerald-100">
          <div class="text-[11px] uppercase tracking-wider font-bold text-emerald-800 flex items-center gap-1">
            <component :is="Lucide.CheckCircle2" class="w-3.5 h-3.5 text-emerald-600" />
            Presentes
          </div>
          <div class="mt-2">
            <span class="text-2xl font-black text-emerald-700">{{ resumen.total_presentes }}</span>
            <span class="text-[11px] text-emerald-600/80 block">marcas totales</span>
          </div>
        </div>

        <!-- Atrasos Acumulados -->
        <div class="card-menta p-4 flex flex-col justify-between bg-amber-50/40 border-amber-100">
          <div class="text-[11px] uppercase tracking-wider font-bold text-amber-800 flex items-center gap-1">
            <component :is="Lucide.Clock" class="w-3.5 h-3.5 text-amber-600" />
            Atrasos
          </div>
          <div class="mt-2">
            <span class="text-2xl font-black text-amber-700">{{ resumen.total_atrasos }}</span>
            <span class="text-[11px] text-amber-600/80 block">marcas totales</span>
          </div>
        </div>

        <!-- Licencias Acumuladas -->
        <div class="card-menta p-4 flex flex-col justify-between bg-sky-50/40 border-sky-100">
          <div class="text-[11px] uppercase tracking-wider font-bold text-sky-800 flex items-center gap-1">
            <component :is="Lucide.FileText" class="w-3.5 h-3.5 text-sky-600" />
            Licencias
          </div>
          <div class="mt-2">
            <span class="text-2xl font-black text-sky-700">{{ resumen.total_licencias }}</span>
            <span class="text-[11px] text-sky-600/80 block">justificadas</span>
          </div>
        </div>

        <!-- Faltas Acumuladas -->
        <div class="card-menta p-4 flex flex-col justify-between bg-rose-50/40 border-rose-100">
          <div class="text-[11px] uppercase tracking-wider font-bold text-rose-800 flex items-center gap-1">
            <component :is="Lucide.XCircle" class="w-3.5 h-3.5 text-rose-600" />
            Faltas
          </div>
          <div class="mt-2">
            <span class="text-2xl font-black text-rose-700">{{ resumen.total_faltas }}</span>
            <span class="text-[11px] text-rose-600/80 block">injustificadas</span>
          </div>
        </div>
      </div>

      <!-- ── Tabla de Sesiones Históricas ───────────────────────────────────── -->
      <div class="card-menta overflow-hidden">
        <!-- Barra de herramientas superior -->
        <div class="p-4 border-b border-[#DDEFEA] bg-[#F8FFFD] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold text-[#0F766E] uppercase tracking-wider flex items-center gap-1.5">
              <component :is="Lucide.CalendarCheck" class="w-4 h-4 text-[#14B8A6]" />
              Sesiones de Clase
            </h2>
            <span class="text-xs bg-[#D1FAE5] text-[#0F766E] font-bold px-2 py-0.5 rounded-full">
              {{ sesionesFiltradas.length }}
            </span>
          </div>

          <div class="flex items-center gap-2">
            <!-- Buscador por fecha -->
            <div class="relative w-48 sm:w-56">
              <input
                v-model="filtroFecha"
                type="text"
                placeholder="Filtrar por fecha..."
                class="input-menta text-xs py-1.5"
                style="padding-left: 2.25rem !important;"
              />
              <component :is="Lucide.Search" class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>

            <!-- Botón ordenar fecha -->
            <button
              type="button"
              class="btn-menta-outline text-xs py-1.5 px-2.5"
              :title="ordenAscendente ? 'Orden: Más antiguas primero' : 'Orden: Más recientes primero'"
              @click="ordenAscendente = !ordenAscendente"
            >
              <component :is="ordenAscendente ? Lucide.ArrowUpNarrowWide : Lucide.ArrowDownWideNarrow" class="w-3.5 h-3.5" />
              <span class="hidden sm:inline">{{ ordenAscendente ? 'Asc' : 'Desc' }}</span>
            </button>
          </div>
        </div>

        <!-- Tabla -->
        <div v-if="sesionesFiltradas.length > 0" class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="table-header-menta">
              <tr>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Fecha de Sesión</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-center">Presentes</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-center">Atrasos</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-center">Licencias</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-center">Faltas</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-center">Evaluados</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-center">% Asistencia</th>
                <th class="p-3.5 text-xs font-bold uppercase tracking-wider text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#DDEFEA]">
              <tr
                v-for="sesion in sesionesFiltradas"
                :key="sesion.id"
                class="hover:bg-[#F0FDF9] transition-colors"
              >
                <!-- Fecha -->
                <td class="p-3.5">
                  <div class="font-bold text-gray-900 capitalize">
                    {{ formatearFecha(sesion.fecha) }}
                  </div>
                  <span class="font-mono text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                    <component :is="Lucide.Calendar" class="w-3 h-3 text-[#14B8A6]" />
                    {{ sesion.fecha }}
                  </span>
                </td>

                <!-- Presentes -->
                <td class="p-3.5 text-center">
                  <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    {{ sesion.presentes }}
                  </span>
                </td>

                <!-- Atrasos -->
                <td class="p-3.5 text-center">
                  <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    {{ sesion.atrasos }}
                  </span>
                </td>

                <!-- Licencias -->
                <td class="p-3.5 text-center">
                  <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200">
                    {{ sesion.licencias }}
                  </span>
                </td>

                <!-- Faltas -->
                <td class="p-3.5 text-center">
                  <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                    {{ sesion.faltas }}
                  </span>
                </td>

                <!-- Total calificados -->
                <td class="p-3.5 text-center font-bold text-gray-800 text-xs">
                  {{ sesion.total_calificados }}
                  <span class="text-[10px] text-gray-400 font-normal block">alumnos</span>
                </td>

                <!-- % Asistencia -->
                <td class="p-3.5 text-center">
                  <span
                    :class="['px-2.5 py-1 text-xs font-black rounded-full inline-block border', getPorcentajeClass(sesion.porcentaje_asistencia)]"
                  >
                    {{ sesion.porcentaje_asistencia }}%
                  </span>
                </td>

                <!-- Botón de acción: Abrir planilla -->
                <td class="p-3.5 text-right">
                  <Link
                    :href="`${base}/asistencias/${sesion.id}`"
                    class="btn-menta text-xs py-1.5 px-3 shadow-2xs font-bold inline-flex items-center gap-1.5"
                    title="Abrir planilla de asistencia de esta sesión"
                  >
                    <component :is="Lucide.ClipboardCheck" class="w-3.5 h-3.5" />
                    Abrir Planilla
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Estado vacío si no hay sesiones -->
        <div v-else class="p-12 text-center text-gray-500">
          <component :is="Lucide.CalendarX" class="w-12 h-12 mx-auto text-gray-300 mb-3" />
          <h3 class="text-base font-bold text-gray-800">No hay sesiones de asistencia registradas</h3>
          <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
            <span v-if="filtroFecha">No se encontraron sesiones para la fecha "{{ filtroFecha }}".</span>
            <span v-else>Este grupo aún no tiene sesiones de clase con asistencia tomada en el sistema.</span>
          </p>
          <div class="mt-4">
            <Link
              :href="`${base}/asistencias`"
              class="btn-menta text-xs py-2 px-4 shadow-xs"
            >
              <component :is="Lucide.ArrowLeft" class="w-3.5 h-3.5" />
              Ir a Registrar Asistencia
            </Link>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
