<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Historial del Alumno</h1>
          <p class="text-sm text-gray-500">Inscripciones y pagos realizados.</p>
        </div>
        <Link :href="buildUrl('/seguimiento')"
          class="inline-flex items-center gap-2 rounded-md bg-gray-100 border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition">
          &larr; Volver
        </Link>
      </div>

      <!-- Student profile -->
      <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm flex flex-col md:flex-row gap-6 justify-between items-start md:items-center">
        <div>
          <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">Ficha del Alumno</div>
          <h2 class="text-xl font-bold text-gray-900">{{ alumno.usuario?.nombres }} {{ alumno.usuario?.apellidos }}</h2>
          <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-1 text-sm text-gray-600">
            <div><span class="font-semibold">Código:</span> {{ alumno.codigo || '-' }}</div>
            <div><span class="font-semibold">Nivel:</span> {{ alumno.nivel || '-' }}</div>
            <div><span class="font-semibold">CI:</span> {{ alumno.usuario?.ci || '-' }}</div>
            <div><span class="font-semibold">Teléfono:</span> {{ alumno.telefono || '-' }}</div>
          </div>
        </div>
        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold"
          :class="alumno.estado === 'ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
          {{ alumno.estado }}
        </span>
      </div>

      <!-- Inscripciones -->
      <div v-if="inscripciones.length > 0" class="space-y-6">
        <div v-for="ins in inscripciones" :key="ins.id"
          class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">

          <div class="bg-gray-50 border-b border-gray-200 p-4 flex flex-col md:flex-row justify-between gap-4">
            <div>
              <span class="rounded bg-blue-100 text-blue-800 px-2 py-0.5 text-xs font-bold mr-2">Curso</span>
              <span class="font-bold text-gray-900">{{ ins.grupo?.curso?.nombre }}</span>
              <span class="text-gray-500"> ({{ ins.grupo?.codigo_grupo }})</span>
              <div class="text-xs text-gray-500 mt-1">
                Docente: <span class="font-medium">{{ ins.grupo?.docente?.usuario?.nombres ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="text-left md:text-right text-sm">
              <div>Monto mensual: <span class="font-bold text-gray-800">{{ fmonto(ins.monto_mensual) }} Bs</span></div>
              <div :class="ins.estado === 'VENCIDA' ? 'text-red-600' : 'text-green-600'" class="mt-1 font-semibold">
                {{ ins.estado }}
              </div>
              <div class="text-xs text-gray-400 mt-1">
                Vence: {{ ins.fecha_vencimiento ? new Date(ins.fecha_vencimiento).toLocaleDateString('es-ES') : '-' }}
              </div>
              <div class="text-xs text-gray-400">
                Inicio: {{ ins.fecha_inicio_clases ? new Date(ins.fecha_inicio_clases).toLocaleDateString('es-ES') : '-' }}
              </div>
            </div>
          </div>

          <!-- Pagos realizados -->
          <div class="p-6">
            <h3 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wider">Pagos realizados</h3>

            <table v-if="ins.mensualidades?.length" class="min-w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-center w-10">Recibo</th>
                  <th class="px-3 py-2 text-left">Fecha pago</th>
                  <th class="px-3 py-2 text-right">Monto</th>
                  <th class="px-3 py-2 text-center">Meses</th>
                  <th class="px-3 py-2 text-left">Método</th>
                  <th class="px-3 py-2 text-center"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="pago in ins.mensualidades" :key="pago.id"
                  :class="pago.estado === 'PAGADO' ? 'bg-green-50/30' : 'bg-yellow-50/30'">
                  <td class="px-3 py-2 font-mono text-xs">{{ pago.numero_recibo || '-' }}</td>
                  <td class="px-3 py-2">{{ pago.fecha_pago ? new Date(pago.fecha_pago).toLocaleDateString('es-ES') : '-' }}</td>
                  <td class="px-3 py-2 text-right font-semibold">{{ fmonto(pago.monto_base) }} Bs</td>
                  <td class="px-3 py-2 text-center">{{ pago.meses_pagados || 1 }}</td>
                  <td class="px-3 py-2">
                    <span class="rounded px-1.5 py-0.5 text-xs font-semibold"
                      :class="pago.tipo_comprobante === 'QR' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'">
                      {{ pago.tipo_comprobante || 'EFECTIVO' }}
                    </span>
                    <span v-if="pago.estado === 'PENDIENTE_PAGO_QR'" class="ml-1 text-yellow-600 text-xs">(esperando pago)</span>
                  </td>
                  <td class="px-3 py-2 text-center w-10">
                    <Link
                      v-if="pago.estado === 'PAGADO' && pago.id"
                      :href="buildUrl('/recibos/' + pago.id)"
                      class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer"
                      title="Ver recibo"
                    >
                      <component :is="Lucide.Printer" class="w-5 h-5" />
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-else class="text-center text-gray-400 py-4">No hay pagos registrados para esta inscripción.</div>
          </div>
        </div>
      </div>

      <div v-else class="bg-white p-12 rounded-lg border border-gray-200 text-center text-gray-400 shadow-sm">
        El alumno no cuenta con inscripciones registradas.
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

defineProps({
  alumno: Object,
  inscripciones: Array,
});

function fmonto(val) {
  const n = parseFloat(val);
  return isNaN(n) ? '0.00' : n.toFixed(2);
}
</script>
