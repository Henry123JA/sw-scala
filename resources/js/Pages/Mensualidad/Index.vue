<template>
  <AppLayout>
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Mensualidades</h2>

    <!-- Flash success message -->
    <div v-if="$page.props.flash?.success" class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex items-center gap-2">
      <component :is="Lucide.CheckCircle2" class="w-5 h-5 text-green-500 shrink-0" />
      <span>{{ $page.props.flash.success }}</span>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <input v-model="buscar" @keyup.enter="filtrar" placeholder="Buscar por nombre..." class="rounded-md border border-gray-300 px-3 py-2 text-sm w-64" />
      <select v-model="estado" @change="filtrar" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        <option value="">Todos los estados</option>
        <option value="ACTIVA">ACTIVA</option>
        <option value="VENCIDA">VENCIDA</option>
        <option value="PAUSADA">PAUSADA</option>
      </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
      <table class="min-w-full text-sm text-gray-700">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
          <tr>
            <th class="px-4 py-3 text-left">Estudiante</th>
            <th class="px-4 py-3 text-left">Curso</th>
            <th class="px-4 py-3 text-left">CI</th>
            <th class="px-4 py-3 text-right">Monto mensual</th>
            <th class="px-4 py-3 text-center">Meses pagados</th>
            <th class="px-4 py-3 text-center">Vencimiento</th>
            <th class="px-4 py-3 text-center">Estado</th>
            <th class="px-4 py-3 text-center">Acción</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ins in (inscripciones?.data ?? [])" :key="ins.id" class="border-t border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3">
              <span class="font-medium text-gray-900">
                {{ ins.alumno?.usuario?.apellidos }} {{ ins.alumno?.usuario?.nombres }}
              </span>
            </td>
            <td class="px-4 py-3">{{ ins.grupo?.curso?.nombre ?? '-' }}</td>
            <td class="px-4 py-3 text-gray-500">{{ ins.alumno?.usuario?.ci ?? '-' }}</td>
            <td class="px-4 py-3 text-right font-medium">{{ formatearMonto(ins.monto_mensual) }} Bs</td>
            <td class="px-4 py-3 text-center">{{ mesesPagados(ins) || 0 }}</td>
            <td class="px-4 py-3 text-center">
              <span v-if="ins.fecha_vencimiento" class="text-xs">
                {{ new Date(ins.fecha_vencimiento).toLocaleDateString('es-ES') }}
                <span v-if="diasRestantes(ins) !== null && !isNaN(diasRestantes(ins))"
                  :class="diasRestantes(ins) < 0 ? 'text-red-600' : diasRestantes(ins) <= 7 ? 'text-yellow-600' : 'text-green-600'"
                  class="ml-1 font-semibold">
                  ({{ diasRestantes(ins) < 0 ? 'hace ' + Math.abs(diasRestantes(ins)) + 'd' : 'en ' + diasRestantes(ins) + 'd' }})
                </span>
              </span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="{
                'rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700': ins.estado === 'ACTIVA',
                'rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700': ins.estado === 'VENCIDA',
                'rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-semibold text-yellow-700': ins.estado === 'PAUSADA',
              }">
                {{ ins.estado }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <button
                v-if="canPay(ins)"
                @click="abrirPago(ins)"
                class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
              >
                Pagar
              </button>
              <button
                v-else
                @click="verHistorial(ins)"
                class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200"
              >
                Historial
              </button>
            </td>
          </tr>
          <tr v-if="!inscripciones.data?.length">
            <td colspan="8" class="px-4 py-12 text-center text-gray-400">No hay inscripciones registradas.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="inscripciones.links" class="mt-4 flex justify-center gap-2">
      <template v-for="link in inscripciones.links" :key="link.label">
        <button
          v-if="link.url"
          @click="router.get(link.url, { buscar: buscar || undefined, estado: estado || undefined }, { preserveState: true })"
          v-html="link.label"
          class="rounded-md border px-3 py-1 text-sm"
          :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50'"
        />
        <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
      </template>
    </div>

    <PagarModal
      v-if="showPagarModal && selectedInscripcion"
      :show="showPagarModal"
      :inscripcion="selectedInscripcion"
      :user-role="userRole"
      @close="cerrarPago"
      @paid="onPaidSuccess"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import PagarModal from './PagarModal.vue';
import * as Lucide from '@lucide/vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

defineProps({
  inscripciones: Object,
  filters: Object,
});

const page = usePage();
const userRole = computed(() => page.props.auth?.rol ?? '');
const canManage = computed(() => ['Propietario', 'Secretaria'].includes(userRole.value));

const buscar = ref('');
const estado = ref('');
const showPagarModal = ref(false);
const selectedInscripcion = ref(null);

function filtrar() {
  router.get(buildUrl('/mensualidades'), {
    buscar: buscar.value || undefined,
    estado: estado.value || undefined,
  }, { preserveState: true, replace: true });
}

function formatearMonto(val) {
  const num = parseFloat(val);
  return isNaN(num) ? '0.00' : num.toFixed(2);
}

function mesesPagados(ins) {
  if (!ins.mensualidades) return 0;
  const total = ins.mensualidades
    .filter(m => m.estado === 'PAGADO')
    .reduce((sum, m) => sum + (parseInt(m.meses_pagados) || 0), 0);
  return isNaN(total) ? 0 : total;
}

function diasRestantes(ins) {
  if (!ins.fecha_vencimiento) return null;
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const venc = new Date(ins.fecha_vencimiento);
  venc.setHours(0, 0, 0, 0);
  const diff = Math.ceil((venc - hoy) / (1000 * 60 * 60 * 24));
  return isNaN(diff) ? null : diff;
}

function canPay(ins) {
  if (canManage.value) return ins.estado !== 'PAUSADA';
  if (userRole.value === 'Alumno') return ins.estado !== 'PAUSADA';
  return false;
}

function abrirPago(ins) {
  selectedInscripcion.value = ins;
  showPagarModal.value = true;
}

function verHistorial(ins) {
  selectedInscripcion.value = ins;
  showPagarModal.value = true;
}

function cerrarPago() {
  showPagarModal.value = false;
  selectedInscripcion.value = null;
}

function onPaidSuccess() {
  showPagarModal.value = false;
  selectedInscripcion.value = null;
  router.reload();
}
</script>
