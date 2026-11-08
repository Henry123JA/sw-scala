<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  es_alumno: {
    type: Boolean,
    default: false,
  },
  grupos: {
    type: Array,
    default: () => [],
  },
  rol_usuario: {
    type: String,
    default: '',
  },
  resumen_cursos: {
    type: Array,
    default: () => [],
  },
  historial: {
    type: Array,
    default: () => [],
  },
});

const base = (import.meta.env.VITE_BASE_URL || '').replace(/\/$/, '');
const todayStr = new Date().toLocaleDateString('en-CA');

// ── Búsqueda y Filtros ────────────────────────────────────────────────────────
const search = ref('');

const gruposFiltrados = computed(() => {
  if (!props.grupos) return [];
  if (!search.value.trim()) return props.grupos;
  const q = search.value.toLowerCase();
  return props.grupos.filter(g =>
    g.codigo_grupo?.toLowerCase().includes(q) ||
    g.curso?.nombre?.toLowerCase().includes(q) ||
    g.docente?.usuario?.nombres?.toLowerCase().includes(q) ||
    g.docente?.usuario?.apellidos?.toLowerCase().includes(q)
  );
});

// ── Modales Docente / Admin ───────────────────────────────────────────────────
const modalCrearOpen = ref(false);
const modalListaOpen = ref(false);
const grupoSeleccionado = ref(null);

const formNuevaEvaluacion = useForm({
  nombre: '',
  tipo: 'PRACTICA',
  fecha: todayStr,
  ponderacion: 100,
  descripcion: '',
});

function abrirModalCrear(grupo) {
  grupoSeleccionado.value = grupo;
  formNuevaEvaluacion.reset();
  formNuevaEvaluacion.fecha = todayStr;
  formNuevaEvaluacion.ponderacion = 100;
  formNuevaEvaluacion.tipo = 'PRACTICA';
  modalCrearOpen.value = true;
}

function cerrarModalCrear() {
  modalCrearOpen.value = false;
  grupoSeleccionado.value = null;
}

function crearEvaluacion() {
  if (!grupoSeleccionado.value) return;
  formNuevaEvaluacion.post(`${base}/evaluaciones/grupos/${grupoSeleccionado.value.id}`, {
    onSuccess: () => {
      cerrarModalCrear();
    },
  });
}

function abrirModalLista(grupo) {
  grupoSeleccionado.value = grupo;
  modalListaOpen.value = true;
}

function cerrarModalLista() {
  modalListaOpen.value = false;
  grupoSeleccionado.value = null;
}

// ── Helpers Visuales ──────────────────────────────────────────────────────────
function getBadgeTipoClass(tipo) {
  switch (tipo) {
    case 'PRACTICA':
      return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    case 'TEORIA':
      return 'bg-sky-100 text-sky-800 border-sky-200';
    case 'AUDICION':
      return 'bg-purple-100 text-purple-800 border-purple-200';
    case 'RECITAL':
      return 'bg-amber-100 text-amber-800 border-amber-200';
    default:
      return 'bg-gray-100 text-gray-700 border-gray-200';
  }
}

function getBadgeNotaClass(nota) {
  if (nota === null || nota === undefined) return 'bg-gray-100 text-gray-500';
  if (nota >= 80) return 'bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold';
  if (nota >= 51) return 'bg-amber-100 text-amber-800 border border-amber-300 font-bold';
  return 'bg-rose-100 text-rose-800 border border-rose-300 font-bold';
}
</script>

<template>
  <Head title="Evaluaciones y Calificaciones" />
  <AppLayout>
    <div class="space-y-6">

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- VISTA ALUMNO: LIBRETA PERSONAL DE CALIFICACIONES                       -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div v-if="es_alumno" class="space-y-6">
        <!-- Encabezado Alumno -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
              <span class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
                <component :is="Lucide.Award" class="w-5 h-5" />
              </span>
              Mis Calificaciones
            </h1>
            <p class="text-sm text-gray-500 mt-1">
              Consulta tu libreta de notas, promedio ponderado y detalle de evaluaciones por curso.
            </p>
          </div>
        </div>

        <!-- Tarjetas de Resumen por Curso -->
        <div v-if="resumen_cursos.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div
            v-for="curso in resumen_cursos"
            :key="curso.inscripcion_id"
            class="card-menta p-6 flex flex-col justify-between hover:shadow-md transition-shadow"
          >
            <div>
              <div class="flex items-start justify-between gap-2 mb-3">
                <span class="badge-menta font-mono text-xs">{{ curso.codigo_grupo }}</span>
                <span
                  class="text-xs px-2.5 py-1 rounded-full font-bold"
                  :class="getBadgeNotaClass(curso.promedio)"
                >
                  <span v-if="curso.promedio !== null">Promedio: {{ curso.promedio }} / 100</span>
                  <span v-else>Sin calificaciones aún</span>
                </span>
              </div>

              <h3 class="text-lg font-bold text-gray-900">{{ curso.curso }}</h3>
              <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                <component :is="Lucide.User" class="w-3.5 h-3.5 text-gray-400" />
                Docente: <strong class="text-gray-700 font-medium">{{ curso.docente }}</strong>
              </p>

              <!-- Métricas del curso -->
              <div class="mt-4 grid grid-cols-2 gap-3 py-3 px-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA]">
                <div>
                  <span class="text-[11px] uppercase tracking-wider text-gray-500 font-semibold block">Evaluaciones</span>
                  <span class="text-base font-extrabold text-[#0F766E]">{{ curso.evaluaciones_rendidas }} de {{ curso.total_evaluaciones }}</span>
                </div>
                <div>
                  <span class="text-[11px] uppercase tracking-wider text-gray-500 font-semibold block">Estado</span>
                  <span class="text-xs font-bold text-gray-700">{{ curso.estado_inscripcion }}</span>
                </div>
              </div>

              <!-- Lista de Evaluaciones del Curso -->
              <div class="mt-4 space-y-2">
                <div class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1">
                  <component :is="Lucide.FileText" class="w-3.5 h-3.5 text-[#14B8A6]" />
                  Evaluaciones Programadas
                </div>

                <div v-if="curso.evaluaciones && curso.evaluaciones.length > 0" class="divide-y divide-[#DDEFEA] border border-[#DDEFEA] rounded-lg overflow-hidden bg-white">
                  <div
                    v-for="ev in curso.evaluaciones"
                    :key="ev.id"
                    class="p-3 flex items-center justify-between gap-3 text-xs hover:bg-[#F9FEFB] transition-colors"
                  >
                    <div class="min-w-0">
                      <div class="font-bold text-gray-900 truncate">{{ ev.nombre }}</div>
                      <div class="text-[11px] text-gray-500 flex items-center gap-2 mt-0.5">
                        <span :class="['px-1.5 py-0.5 text-[10px] rounded font-semibold border', getBadgeTipoClass(ev.tipo)]">
                          {{ ev.tipo }}
                        </span>
                        <span>{{ ev.fecha || 'Sin fecha' }}</span>
                        <span>• Ponderación: {{ ev.ponderacion }}%</span>
                      </div>
                      <p v-if="ev.observaciones" class="text-[11px] text-gray-600 italic mt-1 bg-amber-50/50 p-1 rounded">
                        "{{ ev.observaciones }}"
                      </p>
                    </div>

                    <div class="text-right shrink-0">
                      <span
                        v-if="ev.nota !== null"
                        class="text-sm font-black px-2.5 py-1 rounded-lg inline-block"
                        :class="ev.nota >= 51 ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-rose-700 bg-rose-50 border border-rose-200'"
                      >
                        {{ ev.nota }} pts
                      </span>
                      <span v-else class="text-[11px] font-semibold text-gray-400 bg-gray-50 px-2 py-1 rounded-md border border-gray-200">
                        Pendiente
                      </span>
                    </div>
                  </div>
                </div>

                <div v-else class="p-3 text-center text-xs text-gray-400 border border-dashed border-[#DDEFEA] rounded-lg">
                  No hay evaluaciones programadas para este grupo aún.
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="card-menta p-10 text-center text-gray-500">
          <component :is="Lucide.Award" class="w-10 h-10 mx-auto text-gray-300 mb-2" />
          <p class="font-semibold text-gray-700">No tienes cursos o calificaciones registradas.</p>
          <p class="text-xs text-gray-400 mt-1">Tus notas aparecerán aquí conforme tus docentes realicen evaluaciones.</p>
        </div>

        <!-- Tabla Histórica Cronológica -->
        <div v-if="historial.length > 0" class="card-menta overflow-hidden">
          <div class="p-4 border-b border-[#DDEFEA] bg-[#F8FFFD] flex items-center justify-between">
            <h2 class="text-sm font-bold text-[#0F766E] uppercase tracking-wider flex items-center gap-2">
              <component :is="Lucide.History" class="w-4 h-4" />
              Historial de Calificaciones
            </h2>
            <span class="text-xs text-gray-500">{{ historial.length }} registro(s)</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="table-header-menta">
                <tr>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Fecha</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Curso / Grupo</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Evaluación</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Tipo</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Nota Obtenida</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Observaciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#DDEFEA]">
                <tr v-for="item in historial" :key="item.id" class="hover:bg-[#F0FDF9] transition-colors">
                  <td class="p-3.5 font-medium text-gray-800 font-mono text-xs">
                    {{ item.fecha || '—' }}
                  </td>
                  <td class="p-3.5">
                    <div class="font-semibold text-gray-900">{{ item.curso }}</div>
                    <span class="text-[11px] text-gray-500 font-mono">{{ item.codigo_grupo }}</span>
                  </td>
                  <td class="p-3.5 font-semibold text-gray-800">
                    {{ item.evaluacion }}
                  </td>
                  <td class="p-3.5">
                    <span :class="['px-2 py-0.5 text-xs font-bold rounded-md inline-block border', getBadgeTipoClass(item.tipo)]">
                      {{ item.tipo }}
                    </span>
                  </td>
                  <td class="p-3.5">
                    <span
                      class="px-2.5 py-1 text-xs font-extrabold rounded-md inline-block"
                      :class="item.nota >= 51 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                    >
                      {{ item.nota }} / {{ item.ponderacion }}
                    </span>
                  </td>
                  <td class="p-3.5 text-xs text-gray-600">
                    {{ item.observaciones || '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- VISTA DOCENTE, SECRETARIA Y PROPIETARIO: GESTIÓN POR GRUPOS             -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div v-else class="space-y-6">
        <!-- Notificación Flash de éxito -->
        <div
          v-if="$page.props.flash?.success"
          class="card-menta p-4 bg-emerald-50/80 border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center justify-between shadow-xs animate-in fade-in"
        >
          <div class="flex items-center gap-2">
            <component :is="Lucide.CheckCircle" class="w-5 h-5 text-emerald-600 shrink-0" />
            <span>{{ $page.props.flash.success }}</span>
          </div>
          <span class="text-xs text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full font-bold">
            Listo
          </span>
        </div>

        <!-- Encabezado Docente / Admin -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
              <span class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
                <component :is="Lucide.Award" class="w-5 h-5" />
              </span>
              Gestión de Evaluaciones y Calificaciones
            </h1>
            <p class="text-sm text-gray-500 mt-1">
              <span v-if="rol_usuario === 'Docente'">Tus grupos asignados. Crea evaluaciones y asienta las notas de tus estudiantes.</span>
              <span v-else>Administración integral de evaluaciones de la academia musical.</span>
            </p>
          </div>

          <!-- Buscador rápido -->
          <div class="relative w-full sm:w-72">
            <input
              v-model="search"
              type="text"
              placeholder="Buscar curso, grupo o docente..."
              class="input-menta pl-9 text-xs"
            />
            <component :is="Lucide.Search" class="w-4 h-4 text-gray-400 absolute left-3 top-2.5 pointer-events-none" />
          </div>
        </div>

        <!-- Cuadrícula de Tarjetas de Grupos -->
        <div v-if="gruposFiltrados.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="grupo in gruposFiltrados"
            :key="grupo.id"
            class="card-menta p-5 flex flex-col justify-between hover:shadow-md transition-all duration-200 border-l-4 border-l-[#14B8A6]"
          >
            <!-- Contenido superior -->
            <div class="space-y-3">
              <div class="flex items-start justify-between gap-2">
                <span class="badge-menta font-mono tracking-wider text-xs font-extrabold">
                  {{ grupo.codigo_grupo }}
                </span>
                <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-[#E6FBF2] text-[#0F766E] border border-[#A7F3D0]">
                  {{ grupo.total_evaluaciones ?? (grupo.evaluaciones ? grupo.evaluaciones.length : 0) }} evaluaciones
                </span>
              </div>

              <div>
                <h3 class="text-base font-bold text-gray-900 line-clamp-1">
                  {{ grupo.curso?.nombre || 'Curso sin asignar' }}
                </h3>
                <p class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                  <component :is="Lucide.BookOpen" class="w-3.5 h-3.5 text-[#14B8A6]" />
                  Nivel: <strong class="text-gray-700 font-semibold">{{ grupo.nivel || 'Estándar' }}</strong>
                </p>
                <p class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                  <component :is="Lucide.User" class="w-3.5 h-3.5 text-gray-400" />
                  Docente: <strong class="text-gray-700 font-semibold">{{ grupo.docente?.usuario ? `${grupo.docente.usuario.nombres} ${grupo.docente.usuario.apellidos}` : 'Sin asignar' }}</strong>
                </p>
              </div>

              <!-- Estadísticas rápidas -->
              <div class="grid grid-cols-2 gap-2 py-2 px-3 rounded-lg bg-[#F8FFFD] border border-[#DDEFEA] text-center">
                <div>
                  <span class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold block">Alumnos Activos</span>
                  <span class="text-xs font-bold text-gray-800">{{ grupo.alumnos_inscritos ?? 0 }}</span>
                </div>
                <div>
                  <span class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold block">Evaluaciones</span>
                  <span class="text-xs font-bold text-[#0F766E]">{{ grupo.total_evaluaciones ?? (grupo.evaluaciones ? grupo.evaluaciones.length : 0) }}</span>
                </div>
              </div>
            </div>

            <!-- Acciones inferiores -->
            <div class="mt-5 pt-3 border-t border-[#DDEFEA] flex flex-col sm:flex-row gap-2">
              <button
                type="button"
                class="btn-menta flex-1 text-xs py-2"
                @click="abrirModalCrear(grupo)"
              >
                <component :is="Lucide.PlusCircle" class="w-3.5 h-3.5" />
                Nueva Evaluación
              </button>

              <button
                type="button"
                class="btn-menta-outline flex-1 text-xs py-2"
                @click="abrirModalLista(grupo)"
              >
                <component :is="Lucide.ListChecks" class="w-3.5 h-3.5" />
                Ver Evaluaciones
              </button>
            </div>
          </div>
        </div>

        <div v-else class="card-menta p-12 text-center text-gray-500">
          <component :is="Lucide.SearchX" class="w-10 h-10 mx-auto text-gray-300 mb-2" />
          <p class="font-bold text-gray-700">No se encontraron grupos disponibles.</p>
          <p class="text-xs text-gray-400 mt-1">Intenta con otro término de búsqueda o verifica tus permisos.</p>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- MODAL: NUEVA EVALUACIÓN                                                 -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div
        v-if="modalCrearOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs animate-in fade-in"
      >
        <div class="card-menta w-full max-w-lg p-6 bg-white shadow-2xl relative">
          <div class="flex items-center justify-between pb-3 border-b border-[#DDEFEA]">
            <div>
              <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                <component :is="Lucide.Award" class="w-5 h-5 text-[#14B8A6]" />
                Nueva Evaluación
              </h3>
              <p class="text-xs text-gray-500 mt-0.5">
                Grupo <strong class="font-mono text-gray-700">{{ grupoSeleccionado?.codigo_grupo }}</strong> — {{ grupoSeleccionado?.curso?.nombre }}
              </p>
            </div>
            <button
              type="button"
              class="text-gray-400 hover:text-gray-600 rounded-lg p-1.5 transition-colors"
              @click="cerrarModalCrear"
            >
              <component :is="Lucide.X" class="w-5 h-5" />
            </button>
          </div>

          <form class="space-y-4 mt-4" @submit.prevent="crearEvaluacion">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                Nombre de la Evaluación *
              </label>
              <input
                v-model="formNuevaEvaluacion.nombre"
                type="text"
                required
                maxlength="100"
                placeholder="ej: Examen de Medio Término, Evaluación Práctica #1"
                class="input-menta text-sm"
              />
              <span v-if="formNuevaEvaluacion.errors.nombre" class="text-xs text-rose-600 mt-1 block">
                {{ formNuevaEvaluacion.errors.nombre }}
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  Tipo *
                </label>
                <select
                  v-model="formNuevaEvaluacion.tipo"
                  class="input-menta text-sm"
                  required
                >
                  <option value="PRACTICA">PRACTICA</option>
                  <option value="TEORIA">TEORIA</option>
                  <option value="AUDICION">AUDICION</option>
                  <option value="RECITAL">RECITAL</option>
                </select>
                <span v-if="formNuevaEvaluacion.errors.tipo" class="text-xs text-rose-600 mt-1 block">
                  {{ formNuevaEvaluacion.errors.tipo }}
                </span>
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  Fecha *
                </label>
                <input
                  v-model="formNuevaEvaluacion.fecha"
                  type="date"
                  required
                  class="input-menta text-sm font-mono"
                />
                <span v-if="formNuevaEvaluacion.errors.fecha" class="text-xs text-rose-600 mt-1 block">
                  {{ formNuevaEvaluacion.errors.fecha }}
                </span>
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  Ponderación *
                </label>
                <input
                  v-model.number="formNuevaEvaluacion.ponderacion"
                  type="number"
                  min="1"
                  max="100"
                  required
                  placeholder="100"
                  class="input-menta text-sm font-mono"
                />
                <span v-if="formNuevaEvaluacion.errors.ponderacion" class="text-xs text-rose-600 mt-1 block">
                  {{ formNuevaEvaluacion.errors.ponderacion }}
                </span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                Descripción / Criterios (opcional)
              </label>
              <textarea
                v-model="formNuevaEvaluacion.descripcion"
                rows="3"
                maxlength="255"
                placeholder="Observaciones o criterios de evaluación..."
                class="input-menta text-sm resize-none"
              ></textarea>
              <span v-if="formNuevaEvaluacion.errors.descripcion" class="text-xs text-rose-600 mt-1 block">
                {{ formNuevaEvaluacion.errors.descripcion }}
              </span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#DDEFEA]">
              <button
                type="button"
                class="btn-menta-outline text-xs"
                @click="cerrarModalCrear"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="btn-menta text-xs"
                :disabled="formNuevaEvaluacion.processing"
              >
                <component :is="Lucide.Check" class="w-4 h-4" />
                Crear y Calificar
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- MODAL: LISTA DE EVALUACIONES DEL GRUPO                                  -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div
        v-if="modalListaOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs animate-in fade-in"
      >
        <div class="card-menta w-full max-w-2xl p-6 bg-white shadow-2xl relative max-h-[85vh] flex flex-col">
          <div class="flex items-center justify-between pb-3 border-b border-[#DDEFEA] shrink-0">
            <div>
              <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                <component :is="Lucide.ListChecks" class="w-5 h-5 text-[#14B8A6]" />
                Evaluaciones del Grupo
              </h3>
              <p class="text-xs text-gray-500 mt-0.5">
                Grupo <strong class="font-mono text-gray-700">{{ grupoSeleccionado?.codigo_grupo }}</strong> — {{ grupoSeleccionado?.curso?.nombre }}
              </p>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                class="btn-menta text-xs py-1.5 px-3"
                @click="modalListaOpen = false; abrirModalCrear(grupoSeleccionado)"
              >
                <component :is="Lucide.Plus" class="w-3.5 h-3.5" />
                Nueva
              </button>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600 rounded-lg p-1.5 transition-colors"
                @click="cerrarModalLista"
              >
                <component :is="Lucide.X" class="w-5 h-5" />
              </button>
            </div>
          </div>

          <div class="overflow-y-auto flex-1 mt-4 space-y-3 pr-1">
            <div
              v-if="grupoSeleccionado?.evaluaciones && grupoSeleccionado.evaluaciones.length > 0"
              class="divide-y divide-[#DDEFEA] border border-[#DDEFEA] rounded-xl overflow-hidden bg-white"
            >
              <div
                v-for="ev in grupoSeleccionado.evaluaciones"
                :key="ev.id"
                class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-[#F9FEFB] transition-colors"
              >
                <div class="space-y-1">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-900 text-sm">{{ ev.nombre }}</span>
                    <span :class="['px-2 py-0.5 text-[10px] font-bold rounded-md border', getBadgeTipoClass(ev.tipo)]">
                      {{ ev.tipo }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-500 flex items-center gap-3">
                    <span class="flex items-center gap-1 font-mono">
                      <component :is="Lucide.Calendar" class="w-3.5 h-3.5 text-[#14B8A6]" />
                      {{ ev.fecha ? ev.fecha.slice(0, 10) : 'Sin fecha' }}
                    </span>
                    <span>•</span>
                    <span>Pond: <strong>{{ ev.ponderacion }}%</strong></span>
                    <span>•</span>
                    <span class="text-emerald-700 font-semibold">{{ ev.calificaciones_count ?? 0 }} notas registradas</span>
                  </div>
                  <p v-if="ev.descripcion" class="text-xs text-gray-600 mt-1">
                    {{ ev.descripcion }}
                  </p>
                </div>

                <div class="shrink-0 flex items-center gap-2">
                  <Link
                    :href="`${base}/evaluaciones/${ev.id}`"
                    class="btn-menta text-xs py-1.5 px-3.5 shadow-xs"
                  >
                    <component :is="Lucide.Edit3" class="w-3.5 h-3.5" />
                    Ingresar Notas
                  </Link>
                </div>
              </div>
            </div>

            <div v-else class="p-8 text-center text-gray-400 border border-dashed border-[#DDEFEA] rounded-xl">
              <component :is="Lucide.ClipboardX" class="w-8 h-8 mx-auto mb-2 text-gray-300" />
              <p class="font-semibold text-gray-600 text-sm">No hay evaluaciones registradas en este grupo.</p>
              <p class="text-xs text-gray-400 mt-1">Crea la primera evaluación con el botón "+ Nueva".</p>
            </div>
          </div>

          <div class="pt-3 border-t border-[#DDEFEA] flex justify-end shrink-0 mt-3">
            <button
              type="button"
              class="btn-menta-outline text-xs"
              @click="cerrarModalLista"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
