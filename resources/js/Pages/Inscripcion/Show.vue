<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <h1 class="text-2xl font-bold text-gray-900" :style="{ color: 'var(--color-primary)' }">Detalle de Inscripción</h1>
          <p class="text-sm text-gray-500">Información general y control de pagos mensuales.</p>
        </div>
        <Link :href="buildUrl('/inscripciones')" class="text-sm text-gray-500 hover:text-gray-700">
          ← Volver al listado
        </Link>
      </div>

      <!-- Action Box for Pause/Resume -->
      <div v-if="canEdit" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-gray-800">Estado de Inscripción: 
            <span :class="inscripcion.estado === 'ACTIVA' ? 'text-green-600' : 'text-yellow-600'">{{ inscripcion.estado }}</span>
          </h2>
          <p class="text-sm text-gray-500 mt-1">
            <span v-if="inscripcion.estado === 'ACTIVA'">Puedes pausar esta inscripción. Se anularán los pagos futuros pendientes.</span>
            <span v-else>Puedes reanudar esta inscripción. Se reactivarán los pagos anulados pendientes.</span>
          </p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Pause Form -->
          <form v-if="inscripcion.estado === 'ACTIVA'" @submit.prevent="submitPause" class="flex items-center gap-2">
            <input v-model="pauseForm.fecha_pausa" type="date" required class="rounded-md border border-gray-300 px-3 py-1.5 text-sm" />
            <button type="submit" :disabled="pauseForm.processing" class="rounded-md bg-yellow-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-yellow-700 disabled:opacity-50">
              Pausar
            </button>
          </form>

          <!-- Resume Form -->
          <form v-if="inscripcion.estado === 'PAUSADA'" @submit.prevent="submitResume" class="flex items-center gap-2">
            <input v-model="resumeForm.fecha_retorno" type="date" required class="rounded-md border border-gray-300 px-3 py-1.5 text-sm" />
            <button type="submit" :disabled="resumeForm.processing" class="rounded-md bg-green-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
              Reanudar
            </button>
          </form>
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
            <p><span class="font-semibold text-gray-800">Precio Mensual:</span> {{ Number(inscripcion.monto_mensual).toFixed(2) }} Bs</p>
          </div>
        </div>

        <!-- Dates and Observaciones -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-4">
          <h3 class="text-md font-bold text-gray-800 border-b pb-2">Fechas y Notas</h3>
          <div class="space-y-2 text-sm text-gray-600">
            <p><span class="font-semibold text-gray-800">Fecha Inscripción:</span> {{ inscripcion.fecha ? new Date(inscripcion.fecha).toLocaleDateString('es-ES') : '-' }}</p>
            <p><span class="font-semibold text-gray-800">Inicio de Clases:</span> {{ inscripcion.fecha_inicio_clases ? new Date(inscripcion.fecha_inicio_clases).toLocaleDateString('es-ES') : '-' }}</p>
            <p v-if="inscripcion.fecha_pausa"><span class="font-semibold text-gray-800">Fecha Pausa:</span> {{ new Date(inscripcion.fecha_pausa).toLocaleDateString('es-ES') }}</p>
            <p v-if="inscripcion.fecha_retorno"><span class="font-semibold text-gray-800">Fecha Retorno:</span> {{ new Date(inscripcion.fecha_retorno).toLocaleDateString('es-ES') }}</p>
            <p><span class="font-semibold text-gray-800">Observaciones:</span> {{ inscripcion.observaciones ?? 'Sin observaciones' }}</p>
          </div>
        </div>
      </div>

      <!-- Monthly Payments List -->
      <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
          <h3 class="font-bold text-gray-800">Mensualidades Generadas (Plan de Pagos)</h3>
          <span class="text-xs text-gray-500 font-semibold">12 Meses</span>
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-white">
            <tr>
              <th class="px-6 py-3 text-left font-semibold text-gray-600">Mes</th>
              <th class="px-6 py-3 text-left font-semibold text-gray-600">Monto Base</th>
              <th class="px-6 py-3 text-left font-semibold text-gray-600">Fecha Vencimiento</th>
              <th class="px-6 py-3 text-left font-semibold text-gray-600">Fecha Pago</th>
              <th class="px-6 py-3 text-left font-semibold text-gray-600">Estado</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-for="m in inscripcion.mensualidades" :key="m.id" class="hover:bg-gray-50">
              <td class="px-6 py-3 font-semibold text-gray-700">Mes {{ m.numero_mes }}</td>
              <td class="px-6 py-3 text-gray-900 font-medium">{{ Number(m.monto_base).toFixed(2) }} Bs</td>
              <td class="px-6 py-3 text-gray-600">{{ new Date(m.fecha_vencimiento).toLocaleDateString('es-ES') }}</td>
              <td class="px-6 py-3 text-gray-600">{{ m.fecha_pago ? new Date(m.fecha_pago).toLocaleDateString('es-ES') : '-' }}</td>
              <td class="px-6 py-3">
                <span
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                  :class="{
                    'bg-green-100 text-green-700': m.estado === 'PAGADO',
                    'bg-yellow-100 text-yellow-700': m.estado === 'PENDIENTE',
                    'bg-red-100 text-red-700': m.estado === 'ATRASADO',
                    'bg-gray-100 text-gray-700': m.estado === 'ANULADO',
                  }"
                >
                  {{ m.estado }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
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
const canDelete = computed(() => page.props.auth?.rol === 'Propietario');

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
</script>
