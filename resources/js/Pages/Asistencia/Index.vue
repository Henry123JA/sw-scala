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

// Búsqueda y filtros para la vista docente / admin
const search = ref('');
const fechaGlobal = ref(todayStr);

// Modal para tomar asistencia con fecha personalizada
const modalOpen = ref(false);
const grupoSeleccionado = ref(null);
const fechaModal = ref(todayStr);

const form = useForm({
  grupo_id: '',
  fecha: todayStr,
});

function tomarAsistenciaHoy(grupo) {
  form.grupo_id = grupo.id;
  form.fecha = todayStr;
  form.post(`${base}/asistencias/sesion`);
}

function abrirModalFecha(grupo) {
  grupoSeleccionado.value = grupo;
  fechaModal.value = todayStr;
  modalOpen.value = true;
}

function confirmarFechaModal() {
  if (!grupoSeleccionado.value || !fechaModal.value) return;
  form.grupo_id = grupoSeleccionado.value.id;
  form.fecha = fechaModal.value;
  form.post(`${base}/asistencias/sesion`, {
    onSuccess: () => {
      modalOpen.value = false;
    },
  });
}

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

// Helpers para estilo de estado en la vista de Alumno
function getBadgeClass(estado) {
  switch (estado) {
    case 'PRESENTE':
      return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
    case 'ATRASO':
      return 'bg-amber-100 text-amber-800 border border-amber-200';
    case 'LICENCIA':
      return 'bg-sky-100 text-sky-800 border border-sky-200';
    case 'FALTA':
      return 'bg-rose-100 text-rose-800 border border-rose-200';
    default:
      return 'bg-gray-100 text-gray-700';
  }
}
</script>

<template>
  <Head title="Control de Asistencias" />
  <AppLayout>
    <div class="space-y-6">

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- VISTA PARA ALUMNOS (SOLO LECTURA DE SU PROPIO HISTORIAL)                 -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div v-if="es_alumno" class="space-y-6">
        <!-- Encabezado Alumno -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
              <span class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
                <component :is="Lucide.UserCheck" class="w-5 h-5" />
              </span>
              Mis Asistencias
            </h1>
            <p class="text-sm text-gray-500 mt-1">
              Consulta tu porcentaje de puntualidad y el historial de asistencias de tus cursos inscritos.
            </p>
          </div>
        </div>

        <!-- Tarjetas de Resumen por Curso -->
        <div v-if="resumen_cursos.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="curso in resumen_cursos"
            :key="curso.inscripcion_id"
            class="card-menta p-5 flex flex-col justify-between hover:shadow-md transition-shadow"
          >
            <div>
              <div class="flex items-start justify-between gap-2 mb-2">
                <span class="badge-menta font-mono text-[11px]">{{ curso.codigo_grupo }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="curso.porcentaje >= 80 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                  {{ curso.porcentaje }}% Asistencia
                </span>
              </div>
              <h3 class="text-base font-bold text-gray-900 line-clamp-1">{{ curso.curso }}</h3>
              <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                <component :is="Lucide.User" class="w-3.5 h-3.5 text-gray-400" />
                Docente: <strong class="text-gray-700 font-medium">{{ curso.docente }}</strong>
              </p>
            </div>

            <!-- Mini estadísticas del curso -->
            <div class="mt-4 pt-4 border-t border-[#DDEFEA] grid grid-cols-4 gap-1 text-center">
              <div class="p-1 rounded bg-[#F0FDF9]">
                <span class="block text-xs font-bold text-emerald-700">{{ curso.presentes }}</span>
                <span class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Pres.</span>
              </div>
              <div class="p-1 rounded bg-amber-50">
                <span class="block text-xs font-bold text-amber-700">{{ curso.atrasos }}</span>
                <span class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Atr.</span>
              </div>
              <div class="p-1 rounded bg-sky-50">
                <span class="block text-xs font-bold text-sky-700">{{ curso.licencias }}</span>
                <span class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Lic.</span>
              </div>
              <div class="p-1 rounded bg-rose-50">
                <span class="block text-xs font-bold text-rose-700">{{ curso.faltas }}</span>
                <span class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Fal.</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="card-menta p-8 text-center text-gray-500">
          <component :is="Lucide.CalendarX" class="w-10 h-10 mx-auto text-gray-300 mb-2" />
          <p class="font-medium text-gray-700">No tienes asistencias registradas aún.</p>
          <p class="text-xs text-gray-400 mt-1">Aparecerán aquí conforme tus docentes tomen asistencia en tus clases.</p>
        </div>

        <!-- Tabla de Historial Cronológico -->
        <div v-if="historial.length > 0" class="card-menta overflow-hidden">
          <div class="p-4 border-b border-[#DDEFEA] bg-[#F8FFFD] flex items-center justify-between">
            <h2 class="text-sm font-bold text-[#0F766E] uppercase tracking-wider flex items-center gap-2">
              <component :is="Lucide.History" class="w-4 h-4" />
              Detalle de Clases
            </h2>
            <span class="text-xs text-gray-500">{{ historial.length }} registro(s)</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="table-header-menta">
                <tr>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Fecha</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Curso / Grupo</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Estado</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Observación</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#DDEFEA]">
                <tr v-for="item in historial" :key="item.id" class="hover:bg-[#F0FDF9] transition-colors">
                  <td class="p-3.5 font-medium text-gray-800 font-mono text-xs">
                    {{ item.fecha ? item.fecha.slice(0, 10) : '—' }}
                  </td>
                  <td class="p-3.5">
                    <div class="font-semibold text-gray-900">{{ item.curso }}</div>
                    <span class="text-[11px] text-gray-500 font-mono">{{ item.codigo_grupo }}</span>
                  </td>
                  <td class="p-3.5">
                    <span :class="['px-2.5 py-1 text-xs font-bold rounded-full inline-block', getBadgeClass(item.estado)]">
                      {{ item.estado }}
                    </span>
                  </td>
                  <td class="p-3.5 text-xs text-gray-600">
                    {{ item.observacion || '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- VISTA EN TARJETAS PARA DOCENTE, SECRETARIA Y PROPIETARIO                 -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div v-else class="space-y-6">
        <!-- Notificación Flash de éxito si viene redirigido tras guardar -->
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

        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
              <span class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
                <component :is="Lucide.UserCheck" class="w-5 h-5" />
              </span>
              Gestión de Asistencias
            </h1>
            <p class="text-sm text-gray-500 mt-1">
              <span v-if="rol_usuario === 'Docente'">Tus grupos asignados. Selecciona un grupo para tomar o actualizar la asistencia.</span>
              <span v-else>Todos los grupos de la academia musical disponibles para supervisión y registro de asistencia.</span>
            </p>
          </div>

          <!-- Buscador rápido de grupos -->
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
            <!-- Contenido superior de la tarjeta -->
            <div class="space-y-3">
              <!-- Header tarjeta: Código y Estado -->
              <div class="flex items-start justify-between gap-2">
                <span class="badge-menta font-mono tracking-wider text-xs font-extrabold">
                  {{ grupo.codigo_grupo }}
                </span>
                
                <!-- Badge condicional si ya se tomó la asistencia hoy -->
                <span
                  v-if="grupo.asistencia_hoy_tomada"
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1 shadow-2xs"
                >
                  <component :is="Lucide.Check" class="w-3 h-3 text-emerald-600" />
                  Asistencia tomada hoy
                </span>
                <span
                  v-else
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200"
                >
                  Pendiente hoy
                </span>
              </div>

              <!-- Nombre del Curso -->
              <div>
                <h3 class="text-base font-bold text-gray-900 line-clamp-1 group-hover:text-[#0F766E]">
                  {{ grupo.curso?.nombre || 'Curso sin asignar' }}
                </h3>
                <p class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                  <component :is="Lucide.Award" class="w-3.5 h-3.5 text-[#14B8A6]" />
                  Nivel: <strong class="text-gray-700 font-semibold">{{ grupo.nivel || 'Estándar' }}</strong>
                </p>
              </div>

              <!-- Detalles clave: Alumnos, Docente, Horario -->
              <div class="space-y-1.5 text-xs text-gray-600 bg-[#F8FFFD] p-3 rounded-lg border border-[#DDEFEA]">
                <!-- Docente (visible si es admin o si existe) -->
                <div v-if="rol_usuario !== 'Docente'" class="flex items-center justify-between">
                  <span class="text-gray-400 flex items-center gap-1">
                    <component :is="Lucide.User" class="w-3.5 h-3.5" /> Docente:
                  </span>
                  <span class="font-semibold text-gray-800 truncate max-w-[150px]">
                    {{ grupo.docente?.usuario ? `${grupo.docente.usuario.nombres} ${grupo.docente.usuario.apellidos}` : 'Sin asignar' }}
                  </span>
                </div>

                <!-- Alumnos inscritos -->
                <div class="flex items-center justify-between">
                  <span class="text-gray-400 flex items-center gap-1">
                    <component :is="Lucide.Users" class="w-3.5 h-3.5" /> Alumnos activos:
                  </span>
                  <span class="font-bold text-[#0F766E]">
                    {{ grupo.alumnos_inscritos ?? 0 }} inscritos
                  </span>
                </div>

                <!-- Horario / Sala -->
                <div v-if="grupo.horarios && grupo.horarios.length > 0" class="pt-1 border-t border-[#DDEFEA] text-[11px] text-gray-500">
                  <div v-for="h in grupo.horarios.slice(0, 2)" :key="h.id" class="flex items-center justify-between">
                    <span>{{ h.dia_semana }}</span>
                    <span class="font-mono text-gray-700">{{ h.hora_inicio?.slice(0, 5) }} - {{ h.hora_fin?.slice(0, 5) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botones de Acción -->
            <div class="mt-4 pt-4 border-t border-[#DDEFEA] flex items-center gap-2">
              <!-- Si ya se tomó la asistencia hoy, ofrece Ver/Editar Asistencia -->
              <button
                v-if="grupo.asistencia_hoy_tomada"
                type="button"
                class="btn-menta-outline flex-1 text-xs py-2 shadow-2xs font-bold text-[#0F766E] border-emerald-300 hover:bg-emerald-50/50"
                :disabled="form.processing"
                @click="tomarAsistenciaHoy(grupo)"
              >
                <component :is="Lucide.CheckCircle2" class="w-4 h-4 text-emerald-600" />
                Ver / Modificar Hoy
              </button>

              <!-- Si está pendiente hoy, botón verde para tomar la asistencia de hoy -->
              <button
                v-else
                type="button"
                class="btn-menta flex-1 text-xs py-2 shadow-xs"
                :disabled="form.processing"
                @click="tomarAsistenciaHoy(grupo)"
              >
                <component :is="Lucide.ClipboardCheck" class="w-4 h-4" />
                Tomar Asistencia Hoy
              </button>

              <!-- Botón secundario para fechas pasadas o personalizadas -->
              <button
                type="button"
                class="btn-menta-outline text-xs px-2.5 py-2"
                title="Elegir otra fecha para este grupo"
                @click="abrirModalFecha(grupo)"
              >
                <component :is="Lucide.Calendar" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Estado vacío cuando no hay grupos -->
        <div v-else class="card-menta p-12 text-center text-gray-500">
          <component :is="Lucide.Users" class="w-12 h-12 mx-auto text-gray-300 mb-3" />
          <h3 class="text-base font-bold text-gray-800">No se encontraron grupos</h3>
          <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
            <span v-if="search">No hay grupos que coincidan con "{{ search }}".</span>
            <span v-else>No tienes grupos académicos asignados actualmente para tomar asistencia.</span>
          </p>
        </div>
      </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: SELECCIONAR FECHA ESPECÍFICA PARA UN GRUPO                       -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div
      v-if="modalOpen"
      class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="modalOpen = false"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-[#DDEFEA] space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-[#DDEFEA]">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center">
              <component :is="Lucide.Calendar" class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-gray-900">Elegir fecha de sesión</h3>
              <p class="text-xs text-gray-500 font-mono">{{ grupoSeleccionado?.codigo_grupo }}</p>
            </div>
          </div>
          <button type="button" class="text-gray-400 hover:text-gray-600 p-1" @click="modalOpen = false">
            <component :is="Lucide.X" class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-3">
          <p class="text-xs text-gray-600">
            Ingresa la fecha de la clase para abrir o revisar la planilla de asistencia de este grupo:
          </p>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Fecha de la clase</label>
            <input
              v-model="fechaModal"
              type="date"
              class="input-menta text-sm"
              required
            />
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#DDEFEA]">
          <button
            type="button"
            class="btn-menta-outline text-xs"
            @click="modalOpen = false"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="btn-menta text-xs"
            :disabled="form.processing || !fechaModal"
            @click="confirmarFechaModal"
          >
            Abrir Planilla
          </button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>
