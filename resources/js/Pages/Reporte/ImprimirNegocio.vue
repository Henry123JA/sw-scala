<template>
  <div class="p-8 max-w-4xl mx-auto space-y-8 text-black bg-white">
    <!-- Header/Title -->
    <div class="border-b-2 border-gray-900 pb-4 flex justify-between items-end">
      <div>
        <h1 class="text-3xl font-bold uppercase tracking-tight">Academia de Música Web</h1>
        <p class="text-md text-gray-600 font-medium">Informe de Estado de Negocio y Finanzas</p>
      </div>
      <div class="text-right text-sm">
        <p class="font-semibold">Fecha Generado: <span class="font-normal">{{ nowFormatted }}</span></p>
        <p class="font-semibold">Período: <span class="font-normal">{{ metrics.start_date }} a {{ metrics.end_date }}</span></p>
      </div>
    </div>

    <!-- Summary Indicators -->
    <div class="grid grid-cols-4 gap-4">
      <div class="border border-gray-300 p-4 rounded bg-gray-50">
        <span class="text-xs uppercase font-bold text-gray-500 block">Total Ingresos</span>
        <span class="text-lg font-bold">{{ Number(metrics.total_ingresos).toFixed(2) }} Bs</span>
      </div>
      <div class="border border-gray-300 p-4 rounded bg-gray-50">
        <span class="text-xs uppercase font-bold text-gray-500 block">Pagadas Qty: {{ metrics.pagos.pagado.cantidad }}</span>
        <span class="text-lg font-bold text-green-700">{{ Number(metrics.pagos.pagado.monto).toFixed(2) }} Bs</span>
      </div>
      <div class="border border-gray-300 p-4 rounded bg-gray-50">
        <span class="text-xs uppercase font-bold text-gray-500 block">Pendientes Qty: {{ metrics.pagos.pendiente.cantidad }}</span>
        <span class="text-lg font-bold text-yellow-700">{{ Number(metrics.pagos.pendiente.monto).toFixed(2) }} Bs</span>
      </div>
      <div class="border border-gray-300 p-4 rounded bg-gray-50">
        <span class="text-xs uppercase font-bold text-gray-500 block">Vencidas Qty: {{ metrics.pagos.atrasado.cantidad }}</span>
        <span class="text-lg font-bold text-red-700">{{ Number(metrics.pagos.atrasado.monto).toFixed(2) }} Bs</span>
      </div>
    </div>

    <!-- Active registrations by course table -->
    <div class="space-y-3">
      <h2 class="text-xl font-bold uppercase border-b border-gray-300 pb-1">Alumnos Activos por Curso</h2>
      <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead>
          <tr class="bg-gray-100">
            <th class="border border-gray-300 px-4 py-2 text-left">Curso</th>
            <th class="border border-gray-300 px-4 py-2 text-right">Inscripciones Activas</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="curso in metrics.inscripciones_por_curso" :key="curso.id">
            <td class="border border-gray-300 px-4 py-2 font-medium">{{ curso.nombre }}</td>
            <td class="border border-gray-300 px-4 py-2 text-right">{{ curso.cantidad_activos }}</td>
          </tr>
          <tr v-if="metrics.inscripciones_por_curso.length === 0">
            <td colspan="2" class="border border-gray-300 px-4 py-4 text-center text-gray-400">
              No hay cursos registrados.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Signature / Print stamp -->
    <div class="pt-16 flex justify-between text-xs text-gray-500">
      <div>
        <p class="border-t border-gray-400 pt-1 px-8 text-center">Firma de Propietario / Administrador</p>
      </div>
      <div>
        <p>Reporte generado automáticamente por el sistema.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue';

const props = defineProps({
  metrics: Object,
});

const nowFormatted = computed(() => {
  const d = new Date();
  return d.toLocaleString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
});

onMounted(() => {
  // Let the DOM render completely, then trigger standard browser print dialog
  setTimeout(() => {
    window.print();
  }, 500);
});
</script>

<style>
@media print {
  body {
    background-color: white;
    color: black;
  }
}
</style>
