<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <!-- Toolbar (no se imprime) -->
    <div class="fixed top-0 left-0 right-0 bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between z-50 no-print">
      <div class="flex items-center gap-2">
        <Link :href="buildUrl('/seguimiento')" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
          <component :is="Lucide.ArrowLeft" class="w-4 h-4" />
          Volver
        </Link>
      </div>
      <button @click="imprimir" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 cursor-pointer">
        <component :is="Lucide.Printer" class="w-4 h-4" />
        Imprimir / Guardar PDF
      </button>
    </div>

    <!-- Receipt -->
    <div ref="receiptRef" class="bg-white w-[210mm] min-h-[297mm] mx-auto shadow-2xl print:shadow-none p-8 print:p-6 flex flex-col" style="font-family: 'Courier New', monospace;">
      <!-- Header -->
      <div class="text-center border-b-2 border-gray-900 pb-4 mb-6">
        <h1 class="text-2xl font-bold uppercase tracking-wider">Academia de Música</h1>
        <p class="text-sm text-gray-600 mt-1">Sistema de Gestión Académica</p>
      </div>

      <h2 class="text-lg font-bold text-center uppercase mb-6">Comprobante de Pago</h2>

      <!-- Receipt number -->
      <div class="text-right mb-6">
        <p class="text-sm font-bold">Recibo N°: <span class="font-mono">{{ recibo.numero }}</span></p>
      </div>

      <!-- Details -->
      <table class="w-full text-sm mb-8">
        <tbody>
          <tr>
            <td class="py-1.5 font-bold w-36 uppercase">Alumno:</td>
            <td class="py-1.5">{{ recibo.alumno_nombre }}</td>
          </tr>
          <tr>
            <td class="py-1.5 font-bold uppercase">CI / RUC:</td>
            <td class="py-1.5">{{ recibo.alumno_ci || '—' }}</td>
          </tr>
          <tr>
            <td class="py-1.5 font-bold uppercase">Curso:</td>
            <td class="py-1.5">{{ recibo.curso_nombre }} <span v-if="recibo.grupo_nombre" class="text-gray-500">({{ recibo.grupo_nombre }})</span></td>
          </tr>
          <tr>
            <td class="py-1.5 font-bold uppercase">Método de pago:</td>
            <td class="py-1.5">{{ recibo.metodo }}</td>
          </tr>
          <tr>
            <td class="py-1.5 font-bold uppercase">Meses cancelados:</td>
            <td class="py-1.5">{{ recibo.meses_pagados }}</td>
          </tr>
          <tr>
            <td class="py-1.5 font-bold uppercase">Fecha de pago:</td>
            <td class="py-1.5">{{ recibo.fecha_pago }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Amount -->
      <div class="border-t-2 border-b-2 border-gray-900 py-4 my-4 text-center">
        <p class="text-xs uppercase tracking-wider text-gray-600 mb-1">Total pagado</p>
        <p class="text-3xl font-bold">Bs {{ recibo.monto }}</p>
      </div>

      <!-- Footer -->
      <div class="mt-auto pt-8 text-center text-xs text-gray-500">
        <div class="border-t border-gray-300 pt-4 mt-4">
          <p class="mb-1">Este comprobante fue generado automáticamente por el sistema.</p>
          <p>Academia de Música — Todos los derechos reservados</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

defineProps({
  recibo: Object,
});

const receiptRef = ref(null);

function imprimir() {
  window.print();
}
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
  .min-h-screen {
    padding: 0 !important;
    background: white !important;
    min-height: auto !important;
  }
  body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
