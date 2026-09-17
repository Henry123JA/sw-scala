<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <h1 class="text-2xl font-bold text-gray-900" :style="{ color: 'var(--color-primary)' }">Detalle de Inscripción</h1>
          <p class="text-sm text-gray-500">Información general y estado académico de la inscripción.</p>
        </div>
        <Link :href="buildUrl('/inscripciones')" class="text-sm text-gray-500 hover:text-gray-700">
          ← Volver al listado
        </Link>
      </div>

      <!-- Action Box for Pause/Resume/Cancel -->
      <div v-if="canEdit" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
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

      <!-- Detail Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Student Info -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-4">
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
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-4">
          <h3 class="text-md font-bold text-gray-800 border-b pb-2">Información Académica</h3>
          <div class="space-y-2 text-sm text-gray-600">
            <p><span class="font-semibold text-gray-800">Curso:</span> {{ inscripcion.grupo?.curso?.nombre }}</p>
            <p><span class="font-semibold text-gray-800">Grupo:</span> {{ inscripcion.grupo?.codigo_grupo }}</p>
            <p><span class="font-semibold text-gray-800">Docente:</span> {{ inscripcion.grupo?.docente?.usuario ? `${inscripcion.grupo.docente.usuario.nombres} ${inscripcion.grupo.docente.usuario.apellidos}` : 'N/A' }}</p>
            <p><span class="font-semibold text-gray-800">Nivel Grupo:</span> {{ inscripcion.grupo?.nivel ?? '-' }}</p>
          </div>
        </div>

        <!-- Dates and Observaciones -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-4">
          <h3 class="text-md font-bold text-gray-800 border-b pb-2">Fechas y Estado</h3>
          <div class="space-y-2 text-sm text-gray-600">
            <p><span class="font-semibold text-gray-800">Fecha Inscripción:</span> {{ inscripcion.fecha ? new Date(inscripcion.fecha).toLocaleDateString('es-ES') : '-' }}</p>
            <p><span class="font-semibold text-gray-800">Inicio de Clases:</span> {{ inscripcion.fecha_inicio_clases ? new Date(inscripcion.fecha_inicio_clases).toLocaleDateString('es-ES') : '-' }}</p>
            <p v-if="inscripcion.fecha_pausa"><span class="font-semibold text-gray-800">Fecha Pausa:</span> {{ new Date(inscripcion.fecha_pausa).toLocaleDateString('es-ES') }}</p>
            <p v-if="inscripcion.fecha_retorno"><span class="font-semibold text-gray-800">Fecha Retorno:</span> {{ new Date(inscripcion.fecha_retorno).toLocaleDateString('es-ES') }}</p>
            <p v-if="inscripcion.fecha_retiro"><span class="font-semibold text-red-700">Fecha Retiro:</span> {{ new Date(inscripcion.fecha_retiro).toLocaleDateString('es-ES') }}</p>
            <p><span class="font-semibold text-gray-800">Observaciones:</span> {{ inscripcion.observaciones ?? 'Sin observaciones' }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const props = defineProps({
  inscripcion: Object,
});

const page = usePage();
const canEdit = computed(() => ['Propietario', 'Secretaria'].includes(page.props.auth?.rol));

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
</script>
