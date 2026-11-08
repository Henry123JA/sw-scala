<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  evaluacion: {
    type: Object,
    required: true,
  },
  alumnos: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const base = (import.meta.env.VITE_BASE_URL || '').replace(/\/$/, '');

// ── Búsqueda de alumnos en la planilla ─────────────────────────────────────────
const search = ref('');

const form = useForm({
  calificaciones: props.alumnos.map(alumno => ({
    inscripcion_id: alumno.inscripcion_id,
    nota: alumno.nota !== null && alumno.nota !== undefined ? alumno.nota : '',
    observaciones: alumno.observaciones || '',
  })),
});

// Mapa reactivo para acceso rápido por inscripcion_id
const calificacionesMap = computed(() => {
  const map = {};
  form.calificaciones.forEach(c => {
    map[c.inscripcion_id] = c;
  });
  return map;
});

const alumnosFiltrados = computed(() => {
  if (!props.alumnos) return [];
  if (!search.value.trim()) return props.alumnos;
  const q = search.value.toLowerCase();
  return props.alumnos.filter(a =>
    a.nombre?.toLowerCase().includes(q) ||
    a.codigo_alumno?.toLowerCase().includes(q)
  );
});

// ── Estadísticas en tiempo real ───────────────────────────────────────────────
const stats = computed(() => {
  let conNota = 0;
  let sumaNotas = 0;
  let aprobados = 0;
  let reprobados = 0;

  form.calificaciones.forEach(c => {
    if (c.nota !== '' && c.nota !== null && !isNaN(c.nota)) {
      const val = parseFloat(c.nota);
      conNota++;
      sumaNotas += val;
      if (val >= 51) {
        aprobados++;
      } else {
        reprobados++;
      }
    }
  });

  const promedio = conNota > 0 ? (sumaNotas / conNota).toFixed(1) : null;

  return {
    total: props.alumnos.length,
    conNota,
    promedio,
    aprobados,
    reprobados,
  };
});

// ── Acciones Rápidas ──────────────────────────────────────────────────────────
function asignarPuntajeMaximo() {
  const max = props.evaluacion.ponderacion || 100;
  form.calificaciones.forEach(c => {
    if (c.nota === '' || c.nota === null) {
      c.nota = max;
    }
  });
}

function limpiarNotas() {
  if (confirm('¿Estás seguro de vaciar las notas no guardadas de todos los alumnos en esta planilla?')) {
    form.calificaciones.forEach(c => {
      c.nota = '';
    });
  }
}

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

function guardarPlanilla() {
  form.put(`${base}/evaluaciones/${props.evaluacion.id}`, {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head :title="`Planilla: ${evaluacion.nombre}`" />
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto">

      <!-- Encabezado y Navegación de Retorno -->
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <Link
            :href="`${base}/evaluaciones`"
            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#0F766E] hover:underline mb-2 transition-colors"
          >
            <component :is="Lucide.ArrowLeft" class="w-3.5 h-3.5" />
            Volver a Evaluaciones
          </Link>

          <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">
              {{ evaluacion.nombre }}
            </h1>
            <span :class="['px-2.5 py-0.5 text-xs font-bold rounded-full border', getBadgeTipoClass(evaluacion.tipo)]">
              {{ evaluacion.tipo }}
            </span>
            <span class="badge-menta font-mono text-xs">
              {{ evaluacion.grupo?.codigo_grupo }}
            </span>
          </div>

          <p class="text-sm text-gray-600 mt-1 flex flex-wrap items-center gap-3">
            <span class="font-semibold text-gray-800">{{ evaluacion.grupo?.curso?.nombre }}</span>
            <span>•</span>
            <span class="font-mono text-gray-500 flex items-center gap-1">
              <component :is="Lucide.Calendar" class="w-3.5 h-3.5 text-[#14B8A6]" />
              {{ evaluacion.fecha ? evaluacion.fecha.slice(0, 10) : 'Sin fecha' }}
            </span>
            <span>•</span>
            <span>Ponderación: <strong class="text-[#0F766E] font-extrabold">{{ evaluacion.ponderacion }} pts</strong></span>
            <span v-if="evaluacion.grupo?.docente?.usuario">
              • Docente: <strong>{{ evaluacion.grupo.docente.usuario.nombres }} {{ evaluacion.grupo.docente.usuario.apellidos }}</strong>
            </span>
          </p>

          <p v-if="evaluacion.descripcion" class="text-xs text-gray-500 mt-2 bg-gray-50 p-2 rounded-lg border border-gray-200">
            <strong>Criterio:</strong> {{ evaluacion.descripcion }}
          </p>
        </div>

        <!-- Botones de Acción Superior -->
        <div class="flex items-center gap-2 shrink-0">
          <button
            type="button"
            class="btn-menta-outline text-xs py-2 px-3"
            title="Asignar puntuación máxima a quienes no tengan nota"
            @click="asignarPuntajeMaximo"
          >
            <component :is="Lucide.CheckCheck" class="w-3.5 h-3.5 text-emerald-600" />
            Puntaje Máximo a Vacíos
          </button>
          <button
            type="button"
            class="btn-menta-outline text-xs py-2 px-3 text-rose-600 hover:text-rose-700"
            title="Limpiar todas las notas"
            @click="limpiarNotas"
          >
            <component :is="Lucide.Eraser" class="w-3.5 h-3.5 text-rose-500" />
            Limpiar
          </button>
        </div>
      </div>

      <!-- Tarjetas de Métricas en Vivo -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="card-menta p-4 bg-white">
          <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block">Total Estudiantes</span>
          <span class="text-2xl font-black text-gray-900 mt-1 block">{{ stats.total }}</span>
        </div>

        <div class="card-menta p-4 bg-white">
          <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block">Evaluados</span>
          <div class="flex items-baseline gap-1 mt-1">
            <span class="text-2xl font-black text-[#0F766E]">{{ stats.conNota }}</span>
            <span class="text-xs text-gray-400">/ {{ stats.total }}</span>
          </div>
        </div>

        <div class="card-menta p-4 bg-white">
          <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block">Promedio Curso</span>
          <span class="text-2xl font-black text-gray-900 mt-1 block">
            {{ stats.promedio !== null ? `${stats.promedio} pts` : '—' }}
          </span>
        </div>

        <div class="card-menta p-4 bg-white">
          <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block">Aprobados / Reprobados</span>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-sm font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
              {{ stats.aprobados }} Apr.
            </span>
            <span class="text-sm font-extrabold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
              {{ stats.reprobados }} Rep.
            </span>
          </div>
        </div>
      </div>

      <!-- Alertas Flash -->
      <div
        v-if="page.props.flash?.success || form.recentlySuccessful"
        class="card-menta p-4 bg-emerald-50/80 border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center justify-between shadow-xs animate-in fade-in"
      >
        <div class="flex items-center gap-2">
          <component :is="Lucide.CheckCircle" class="w-5 h-5 text-emerald-600 shrink-0" />
          <span>{{ page.props.flash?.success || 'Calificaciones registradas correctamente.' }}</span>
        </div>
        <span class="text-xs text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full font-bold">
          Guardado
        </span>
      </div>

      <div
        v-if="Object.keys(form.errors).length > 0"
        class="card-menta p-4 bg-rose-50 border-rose-200 text-rose-800 text-sm font-medium animate-in fade-in"
      >
        <div class="flex items-center gap-2 font-bold mb-1">
          <component :is="Lucide.AlertCircle" class="w-5 h-5 text-rose-600 shrink-0" />
          <span>Existen errores de validación en la planilla:</span>
        </div>
        <ul class="list-disc list-inside text-xs space-y-0.5">
          <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
        </ul>
      </div>

      <!-- Estado vacío si el grupo no tiene alumnos inscritos -->
      <div v-if="!alumnos.length" class="card-menta p-12 text-center text-gray-500">
        <component :is="Lucide.Users" class="w-12 h-12 mx-auto text-gray-300 mb-3" />
        <h3 class="text-base font-bold text-gray-800">Este grupo no tiene alumnos inscritos.</h3>
        <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
          Inscribe estudiantes en el grupo para poder asentar sus calificaciones en esta evaluación.
        </p>
      </div>

      <!-- Formulario de Planilla de Calificaciones -->
      <form v-else class="space-y-4" @submit.prevent="guardarPlanilla">
        <div class="card-menta overflow-hidden">
          <!-- Barra superior de la tabla: Buscador e Indicador -->
          <div class="p-3.5 bg-[#F8FFFD] border-b border-[#DDEFEA] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-2">
              <component :is="Lucide.GraduationCap" class="w-4 h-4 text-[#0F766E]" />
              <span class="text-xs font-bold text-[#0F766E] uppercase tracking-wider">
                Planilla de Estudiantes (Escala 0 a 100)
              </span>
            </div>

            <div class="relative w-full sm:w-64">
              <input
                v-model="search"
                type="text"
                placeholder="Buscar alumno en planilla..."
                class="input-menta pl-8 text-xs py-1.5"
              />
              <component :is="Lucide.Search" class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5 pointer-events-none" />
            </div>
          </div>

          <!-- Tabla de Calificaciones -->
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="table-header-menta">
                <tr>
                  <th class="p-3 text-xs font-bold uppercase tracking-wider w-12 text-center">#</th>
                  <th class="p-3 text-xs font-bold uppercase tracking-wider">Estudiante</th>
                  <th class="p-3 text-xs font-bold uppercase tracking-wider w-40 text-center">
                    Nota (0 - 100) *
                  </th>
                  <th class="p-3 text-xs font-bold uppercase tracking-wider w-28 text-center">Estado</th>
                  <th class="p-3 text-xs font-bold uppercase tracking-wider">Observaciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#DDEFEA]">
                <tr
                  v-for="(alumno, idx) in alumnosFiltrados"
                  :key="alumno.inscripcion_id"
                  class="hover:bg-[#F9FEFB] transition-colors"
                >
                  <!-- Número secuencial -->
                  <td class="p-3 text-center text-xs text-gray-400 font-mono">
                    {{ idx + 1 }}
                  </td>

                  <!-- Datos del Alumno -->
                  <td class="p-3">
                    <div class="font-bold text-gray-900 text-sm">{{ alumno.nombre }}</div>
                    <div class="text-[11px] text-gray-500 font-mono mt-0.5 flex items-center gap-2">
                      <span>Cód: {{ alumno.codigo_alumno }}</span>
                      <span>•</span>
                      <span class="text-gray-400">Inscripción #{{ alumno.inscripcion_id }}</span>
                    </div>
                  </td>

                  <!-- Input de Nota -->
                  <td class="p-3 text-center">
                    <div class="relative inline-block w-28">
                      <input
                        v-if="calificacionesMap[alumno.inscripcion_id]"
                        v-model="calificacionesMap[alumno.inscripcion_id].nota"
                        type="number"
                        min="0"
                        max="100"
                        step="0.5"
                        placeholder="—"
                        class="input-menta text-center font-mono font-bold text-sm py-1.5 px-2 focus:ring-2"
                        :class="[
                          calificacionesMap[alumno.inscripcion_id].nota !== '' && calificacionesMap[alumno.inscripcion_id].nota !== null
                            ? (parseFloat(calificacionesMap[alumno.inscripcion_id].nota) >= 51
                                ? 'border-emerald-300 bg-emerald-50/40 text-emerald-900'
                                : 'border-rose-300 bg-rose-50/40 text-rose-900')
                            : 'border-gray-200'
                        ]"
                      />
                    </div>
                  </td>

                  <!-- Estado Cualitativo (Aprobado / Reprobado / Pendiente) -->
                  <td class="p-3 text-center">
                    <template v-if="calificacionesMap[alumno.inscripcion_id] && calificacionesMap[alumno.inscripcion_id].nota !== '' && calificacionesMap[alumno.inscripcion_id].nota !== null">
                      <span
                        v-if="parseFloat(calificacionesMap[alumno.inscripcion_id].nota) >= 51"
                        class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200"
                      >
                        Aprobado
                      </span>
                      <span
                        v-else
                        class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-rose-100 text-rose-800 border border-rose-200"
                      >
                        Reprobado
                      </span>
                    </template>
                    <span v-else class="text-[11px] font-semibold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                      Sin calificar
                    </span>
                  </td>

                  <!-- Input de Observaciones -->
                  <td class="p-3">
                    <input
                      v-if="calificacionesMap[alumno.inscripcion_id]"
                      v-model="calificacionesMap[alumno.inscripcion_id].observaciones"
                      type="text"
                      maxlength="255"
                      placeholder="Comentario cualitativo..."
                      class="input-menta text-xs py-1.5"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Barra de Guardado Inferior Fija / Destacada -->
          <div class="p-4 bg-[#F8FFFD] border-t border-[#DDEFEA] flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-gray-500">
              <span class="font-bold text-gray-700">{{ stats.conNota }}</span> de
              <span class="font-bold text-gray-700">{{ stats.total }}</span> alumnos calificados en esta planilla.
            </div>

            <div class="flex items-center gap-3">
              <Link
                :href="`${base}/evaluaciones`"
                class="btn-menta-outline text-xs py-2 px-4"
              >
                Cancelar
              </Link>

              <button
                type="submit"
                class="btn-menta text-xs py-2 px-6 shadow-sm"
                :disabled="form.processing"
              >
                <component :is="Lucide.Save" class="w-4 h-4" />
                <span v-if="form.processing">Guardando...</span>
                <span v-else>Guardar Calificaciones</span>
              </button>
            </div>
          </div>
        </div>
      </form>

    </div>
  </AppLayout>
</template>
