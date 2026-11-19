<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <h1 class="text-2xl font-bold text-gray-900" :style="{ color: 'var(--color-primary)' }">Detalle de Inscripción</h1>
          <p class="text-sm text-gray-500">Información general y seguimiento pedagógico del estudiante.</p>
        </div>
        <Link :href="buildUrl('/inscripciones')" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
          <span>← Volver al listado</span>
        </Link>
      </div>

      <!-- Card de Pago de Matrícula (PagoFácil QR / Efectivo) -->
      <div class="rounded-lg border border-teal-200 bg-gradient-to-r from-teal-50/70 to-white p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
          <div class="flex items-center gap-2.5">
            <span class="text-base font-bold text-gray-800">Matrícula / Inscripción:</span>
            <span
              class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold"
              :class="{
                'bg-emerald-100 text-emerald-700': inscripcion.estado_pago === 'PAGADO',
                'bg-amber-100 text-amber-700': inscripcion.estado_pago === 'PENDIENTE_QR',
                'bg-gray-100 text-gray-700': !inscripcion.estado_pago || inscripcion.estado_pago === 'PENDIENTE'
              }"
            >
              {{ inscripcion.estado_pago === 'PAGADO' ? `✓ PAGADO (${inscripcion.metodo_pago || 'QR'})` : (inscripcion.estado_pago === 'PENDIENTE_QR' ? '⏳ QR PENDIENTE DE PAGO' : 'PENDIENTE DE PAGO') }}
            </span>
            <span class="text-sm font-extrabold text-teal-700">
              Bs. {{ Number(inscripcion.monto_inscripcion || 150).toFixed(2) }}
            </span>
          </div>
          <p class="text-xs text-gray-500">
            <span v-if="inscripcion.estado_pago === 'PAGADO'">
              Comprobante emitido: <strong class="font-mono text-emerald-700">{{ inscripcion.numero_recibo }}</strong>
              <span v-if="inscripcion.fecha_pago"> — Fecha: {{ formatDate(inscripcion.fecha_pago) }}</span>
            </span>
            <span v-else>
              Paga la matrícula escaneando el código QR interoperable de PagoFácil desde cualquier app bancaria boliviana.
            </span>
          </p>
        </div>

        <button
          v-if="canPay && inscripcion.estado !== 'CANCELADA'"
          type="button"
          @click="showPagoModal = true"
          class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold shadow-xs transition cursor-pointer shrink-0"
          :class="inscripcion.estado_pago === 'PAGADO'
            ? 'bg-white border border-emerald-300 text-emerald-700 hover:bg-emerald-50'
            : 'bg-teal-600 text-white hover:bg-teal-700'"
        >
          <span>{{ inscripcion.estado_pago === 'PAGADO' ? '🧾 Ver Comprobante de Pago' : '📱 Pagar Matrícula (QR PagoFácil)' }}</span>
        </button>
      </div>

      <!-- Action Box for Pause/Resume/Cancel -->
      <div v-if="canEdit" class="rounded-lg border border-gray-200 bg-white p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-gray-800">Estado de Inscripción: 
            <span
              class="font-bold"
              :class="{
                'text-green-600': inscripcion.estado === 'ACTIVA',
                'text-yellow-600': inscripcion.estado === 'PAUSADA',
                'text-red-600': inscripcion.estado === 'CANCELADA'
              }"
            >
              {{ inscripcion.estado }}
            </span>
          </h2>
          <p class="text-sm text-gray-500 mt-1">
            <span v-if="inscripcion.estado === 'ACTIVA'">La inscripción está activa. Puedes pausarla temporalmente o registrar el retiro del alumno.</span>
            <span v-else-if="inscripcion.estado === 'PAUSADA'">La inscripción está en pausa académica. Puedes reanudarla o registrar el retiro.</span>
            <span v-else>Inscripción cancelada (alumno retirado). El registro se conserva intacto en el historial académico.</span>
          </p>
        </div>

        <div v-if="inscripcion.estado !== 'CANCELADA'" class="flex flex-wrap items-center gap-3">
          <!-- Pause Form -->
          <form v-if="inscripcion.estado === 'ACTIVA'" @submit.prevent="submitPause" class="flex items-center gap-2">
            <input v-model="pauseForm.fecha_pausa" type="date" required class="rounded-md border border-gray-300 px-3 py-1.5 text-sm" />
            <button type="submit" :disabled="pauseForm.processing" class="rounded-md bg-yellow-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-yellow-700 disabled:opacity-50 cursor-pointer">
              Pausar
            </button>
          </form>

          <!-- Resume Form -->
          <form v-if="inscripcion.estado === 'PAUSADA'" @submit.prevent="submitResume" class="flex items-center gap-2">
            <input v-model="resumeForm.fecha_retorno" type="date" required class="rounded-md border border-gray-300 px-3 py-1.5 text-sm" />
            <button type="submit" :disabled="resumeForm.processing" class="rounded-md bg-green-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50 cursor-pointer">
              Reanudar
            </button>
          </form>

          <!-- Cancel Button -->
          <button
            @click="submitCancel"
            class="rounded-md bg-red-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 cursor-pointer"
          >
            Retirar Alumno
          </button>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="border-b border-[#DDEFEA] flex items-center justify-between">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
          <button
            type="button"
            @click="activeTab = 'general'"
            :class="[
              activeTab === 'general'
                ? 'border-[#14B8A6] text-[#0F766E] font-bold'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium',
              'whitespace-nowrap pb-3 px-1 border-b-2 text-sm flex items-center gap-2 cursor-pointer transition'
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span>Información General</span>
          </button>

          <button
            type="button"
            @click="activeTab = 'observaciones'"
            :class="[
              activeTab === 'observaciones'
                ? 'border-[#14B8A6] text-[#0F766E] font-bold'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium',
              'whitespace-nowrap pb-3 px-1 border-b-2 text-sm flex items-center gap-2 cursor-pointer transition'
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Observaciones Académicas</span>
            <span
              class="ml-1 rounded-full px-2 py-0.5 text-xs font-semibold"
              :class="activeTab === 'observaciones' ? 'bg-[#D1FAE5] text-[#0F766E]' : 'bg-gray-100 text-gray-600'"
            >
              {{ observacionesList.length }}
            </span>
          </button>
        </nav>

        <!-- Quick button to add observation if on general tab -->
        <button
          v-if="canAddObservacion && activeTab === 'observaciones'"
          @click="showModal = true"
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-[#14B8A6] hover:bg-[#0D9488] shadow-xs cursor-pointer transition"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Nueva Observación</span>
        </button>
      </div>

      <!-- Tab 1: General Info Grid -->
      <div v-show="activeTab === 'general'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Student Info -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xs space-y-4">
          <h3 class="text-md font-bold text-gray-800 border-b pb-2">Información del Alumno</h3>
          <div class="space-y-2 text-sm text-gray-600">
            <p><span class="font-semibold text-gray-800">Nombre:</span> {{ inscripcion.alumno?.usuario ? `${inscripcion.alumno.usuario.nombres} ${inscripcion.alumno.usuario.apellidos}` : 'N/A' }}</p>
            <p><span class="font-semibold text-gray-800">Email:</span> {{ inscripcion.alumno?.usuario?.email }}</p>
            <p><span class="font-semibold text-gray-800">CI:</span> {{ inscripcion.alumno?.usuario?.ci }}</p>
            <p><span class="font-semibold text-gray-800">Código:</span> {{ inscripcion.alumno?.codigo }}</p>
            <p><span class="font-semibold text-gray-800">Nivel Alumno:</span> {{ inscripcion.alumno?.nivel ?? '-' }}</p>
          </div>
        </div>

        <!-- Class Info -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xs space-y-4">
          <h3 class="text-md font-bold text-gray-800 border-b pb-2">Información Académica</h3>
          <div class="space-y-2 text-sm text-gray-600">
            <p><span class="font-semibold text-gray-800">Curso:</span> {{ inscripcion.grupo?.curso?.nombre }}</p>
            <p><span class="font-semibold text-gray-800">Grupo:</span> {{ inscripcion.grupo?.codigo_grupo }}</p>
            <p><span class="font-semibold text-gray-800">Docente:</span> {{ inscripcion.grupo?.docente?.usuario ? `${inscripcion.grupo.docente.usuario.nombres} ${inscripcion.grupo.docente.usuario.apellidos}` : 'N/A' }}</p>
            <p><span class="font-semibold text-gray-800">Nivel Grupo:</span> {{ inscripcion.grupo?.nivel ?? '-' }}</p>
          </div>
        </div>

        <!-- Dates and Observaciones -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xs space-y-4">
          <h3 class="text-md font-bold text-gray-800 border-b pb-2">Fechas y Estado</h3>
          <div class="space-y-2 text-sm text-gray-600">
            <p><span class="font-semibold text-gray-800">Fecha Inscripción:</span> {{ inscripcion.fecha ? formatDate(inscripcion.fecha) : '-' }}</p>
            <p><span class="font-semibold text-gray-800">Inicio de Clases:</span> {{ inscripcion.fecha_inicio_clases ? formatDate(inscripcion.fecha_inicio_clases) : '-' }}</p>
            <p v-if="inscripcion.fecha_pausa"><span class="font-semibold text-gray-800">Fecha Pausa:</span> {{ formatDate(inscripcion.fecha_pausa) }}</p>
            <p v-if="inscripcion.fecha_retorno"><span class="font-semibold text-gray-800">Fecha Retorno:</span> {{ formatDate(inscripcion.fecha_retorno) }}</p>
            <p v-if="inscripcion.fecha_retiro"><span class="font-semibold text-red-700">Fecha Retiro:</span> {{ formatDate(inscripcion.fecha_retiro) }}</p>
            <p><span class="font-semibold text-gray-800">Observaciones:</span> {{ inscripcion.observaciones ?? 'Sin observaciones' }}</p>
          </div>
        </div>
      </div>

      <!-- Tab 2: Observaciones Académicas (Timeline) -->
      <div v-show="activeTab === 'observaciones'" class="space-y-6">
        <!-- Subheader & Actions -->
        <div class="rounded-xl border border-[#DDEFEA] bg-white p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
              <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#14B8A6]"></span>
              Bitácora de Observaciones Pedagógicas
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">
              Registro continuo del desempeño técnico, actitudinal y recomendaciones formativas del estudiante.
            </p>
          </div>
          <button
            v-if="canAddObservacion"
            @click="showModal = true"
            type="button"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-lg bg-[#14B8A6] hover:bg-[#0D9488] shadow-xs cursor-pointer transition shrink-0"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Agregar Observación</span>
          </button>
        </div>

        <!-- Empty State -->
        <div
          v-if="observacionesList.length === 0"
          class="rounded-xl border border-dashed border-[#DDEFEA] bg-white p-12 text-center"
        >
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#F0FDF9] text-[#14B8A6]">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h3 class="mt-3 text-sm font-bold text-gray-900">Sin observaciones registradas</h3>
          <p class="mt-1 text-xs text-gray-500 max-w-sm mx-auto">
            Aún no hay anotaciones pedagógicas para esta inscripción. Las observaciones registradas por el personal docente se mostrarán en esta línea de tiempo.
          </p>
          <div v-if="canAddObservacion" class="mt-4">
            <button
              @click="showModal = true"
              type="button"
              class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white rounded-lg bg-[#14B8A6] hover:bg-[#0D9488] shadow-xs cursor-pointer transition"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span>Registrar primera observación</span>
            </button>
          </div>
        </div>

        <!-- Timeline -->
        <div v-else class="relative border-l-2 border-[#DDEFEA] ml-4 md:ml-6 space-y-6 pb-4">
          <div
            v-for="obs in observacionesList"
            :key="obs.id"
            class="relative pl-6 md:pl-8 group"
          >
            <!-- Timeline Marker Dot -->
            <span
              class="absolute -left-[9px] top-1.5 flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white"
              :class="getTipoBadge(obs.tipo).dotClass"
            ></span>

            <!-- Observation Card -->
            <div class="rounded-xl border border-[#DDEFEA] bg-white p-5 shadow-xs hover:shadow-md transition">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-3">
                <div class="flex flex-wrap items-center gap-2">
                  <!-- Category Badge -->
                  <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                    :class="getTipoBadge(obs.tipo).badgeClass"
                  >
                    <component :is="getTipoBadge(obs.tipo).icon" class="w-3.5 h-3.5" />
                    {{ getTipoBadge(obs.tipo).label }}
                  </span>

                  <!-- Visibility Badge -->
                  <span
                    v-if="obs.visible_para_alumno"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-[#D1FAE5] text-[#0F766E]"
                    title="El alumno puede ver esta nota en su seguimiento"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Visible al Alumno
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-gray-100 text-gray-500"
                    title="Solo visible para docentes y administración"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                    Docente / Admin
                  </span>
                </div>

                <div class="flex items-center gap-3">
                  <!-- Date -->
                  <span class="text-xs text-gray-500 font-medium flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ formatDate(obs.fecha) }}
                  </span>

                  <!-- Delete Button -->
                  <button
                    v-if="canDeleteObservacion(obs)"
                    @click="deleteObservacion(obs)"
                    type="button"
                    title="Eliminar observación"
                    class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition cursor-pointer"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Body -->
              <div class="mt-3 space-y-2">
                <h4 class="text-sm font-bold text-gray-900">{{ obs.titulo }}</h4>
                <p class="text-xs md:text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ obs.descripcion }}</p>
              </div>

              <!-- Footer with author -->
              <div class="mt-4 pt-3 border-t border-gray-50 flex items-center justify-between text-xs text-gray-500">
                <div class="flex items-center gap-1.5">
                  <div class="w-5 h-5 rounded-full bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center font-bold text-[10px]">
                    {{ getInitials(obs.docente?.usuario) }}
                  </div>
                  <span>
                    {{ obs.docente?.usuario ? `Prof. ${obs.docente.usuario.nombres} ${obs.docente.usuario.apellidos}` : 'Equipo Docente' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para Registrar Observación Académica -->
    <ObservacionesModal
      :show="showModal"
      :inscripcion-id="inscripcion.id"
      @close="showModal = false"
      @created="onObservacionCreated"
    />

    <!-- Modal para Pago de Matrícula con QR PagoFácil / Efectivo -->
    <PagarInscripcionModal
      :show="showPagoModal"
      :inscripcion="inscripcion"
      @close="showPagoModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ObservacionesModal from '@/Components/ObservacionesModal.vue';
import PagarInscripcionModal from './PagarInscripcionModal.vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const props = defineProps({
  inscripcion: Object,
  observaciones: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const activeTab = ref('general');
const showModal = ref(false);
const showPagoModal = ref(false);

const observacionesList = computed(() => props.observaciones || []);

const authUser = computed(() => page.props.auth?.user);
const authRole = computed(() => page.props.auth?.rol);

const canEdit = computed(() => ['Propietario', 'Secretaria'].includes(authRole.value));
const canPay = computed(() => ['Propietario', 'Secretaria', 'Alumno'].includes(authRole.value));
const canAddObservacion = computed(() => ['Propietario', 'Secretaria', 'Docente'].includes(authRole.value));

function canDeleteObservacion(obs) {
  if (authRole.value === 'Propietario') return true;
  if (authRole.value === 'Docente' && obs.docente_id === authUser.value?.id) return true;
  return false;
}

const today = new Date().toISOString().split('T')[0];

const pauseForm = useForm({
  fecha_pausa: today,
});

const resumeForm = useForm({
  fecha_retorno: today,
});

function submitPause() {
  pauseForm.post(buildUrl(`/inscripciones/${props.inscripcion.id}/pausar`));
}

function submitResume() {
  resumeForm.post(buildUrl(`/inscripciones/${props.inscripcion.id}/reanudar`));
}

function submitCancel() {
  if (confirm('¿Está seguro de retirar al alumno y cancelar esta inscripción?\n\nEl estado cambiará a CANCELADA, se registrará la fecha de retiro y se conservará el historial.')) {
    router.post(buildUrl(`/inscripciones/${props.inscripcion.id}/cancelar`));
  }
}

function deleteObservacion(obs) {
  if (confirm(`¿Está seguro de eliminar la observación "${obs.titulo}"? Esta acción no se puede deshacer.`)) {
    router.delete(buildUrl(`/observaciones/${obs.id}`), {
      preserveScroll: true,
    });
  }
}

function onObservacionCreated() {
  activeTab.value = 'observaciones';
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const parts = dateStr.split('T')[0].split('-');
  if (parts.length === 3) {
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
  }
  return new Date(dateStr).toLocaleDateString('es-ES');
}

function getInitials(usuario) {
  if (!usuario) return 'ED';
  const n = usuario.nombres ? usuario.nombres[0] : '';
  const a = usuario.apellidos ? usuario.apellidos[0] : '';
  return (n + a).toUpperCase() || 'P';
}

// SVG Icon Helpers
const TechIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' })
]);

const RecomIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z' })
]);

const DiscIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' })
]);

const FelicIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' })
]);

function getTipoBadge(tipo) {
  switch (tipo) {
    case 'PROGRESO_TECNICO':
      return {
        label: 'Progreso Técnico',
        badgeClass: 'bg-teal-50 text-teal-700 border border-teal-200',
        dotClass: 'bg-[#14B8A6]',
        icon: TechIcon,
      };
    case 'RECOMENDACION':
      return {
        label: 'Recomendación',
        badgeClass: 'bg-sky-50 text-sky-700 border border-sky-200',
        dotClass: 'bg-sky-500',
        icon: RecomIcon,
      };
    case 'DISCIPLINA':
      return {
        label: 'Disciplina',
        badgeClass: 'bg-amber-50 text-amber-700 border border-amber-200',
        dotClass: 'bg-amber-500',
        icon: DiscIcon,
      };
    case 'FELICITACION':
      return {
        label: 'Felicitación',
        badgeClass: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        dotClass: 'bg-emerald-500',
        icon: FelicIcon,
      };
    default:
      return {
        label: tipo,
        badgeClass: 'bg-gray-50 text-gray-700 border border-gray-200',
        dotClass: 'bg-gray-400',
        icon: TechIcon,
      };
  }
}
</script>
