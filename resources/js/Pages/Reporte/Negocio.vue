<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Reportes Financieros y de Negocio</h1>
          <p class="text-gray-500">Métricas clave e ingresos del período seleccionado.</p>
        </div>
        <div class="flex items-center gap-2">
          <!-- Excel / CSV Export -->
          <a
            :href="buildUrl('/reportes/negocio/exportar') + '?' + qs({ start_date: startDate, end_date: endDate })"
            class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition"
          >
            <!-- Excel Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Exportar CSV
          </a>
          <!-- Export PDF Button -->
          <button
            @click="handleExportarPDF"
            class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Exportar PDF
          </button>
          <!-- Export Excel Button -->
          <button
            @click="handleExportarExcel"
            class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Exportar Excel
          </button>
          <!-- Print PDF Button -->
          <a
            :href="buildUrl('/reportes/negocio/imprimir') + '?' + qs({ start_date: startDate, end_date: endDate })"
            target="_blank"
            class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition"
          >
            <!-- Print Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimir Reporte
          </a>
        </div>
      </div>

      <!-- Date Range Selector -->
      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row items-end gap-4">
        <div class="w-full sm:w-auto">
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha Desde</label>
          <input
            v-model="startDate"
            type="date"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          />
        </div>
        <div class="w-full sm:w-auto">
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha Hasta</label>
          <input
            v-model="endDate"
            type="date"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          />
        </div>
        <button
          @click="aplicarFiltro"
          class="w-full sm:w-auto px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-medium hover:bg-gray-800 transition"
        >
          Filtrar
        </button>
      </div>

      <!-- Financial Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Ingresos -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Ingresos Totales (Efectivos)</span>
            <span class="text-2xl font-bold text-gray-900 mt-1 block">{{ Number(metrics.total_ingresos).toFixed(2) }} Bs</span>
          </div>
          <div class="h-10 w-10 rounded-full bg-green-50 flex items-center justify-center text-green-600 font-bold text-xl">$</div>
        </div>

        <!-- Pagado (Facturado Periodo) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Mensualidades Pagadas</span>
          <div class="flex items-baseline justify-between mt-1">
            <span class="text-2xl font-bold text-green-600">{{ Number(metrics.pagos.pagado.monto).toFixed(2) }} Bs</span>
            <span class="text-sm text-gray-500">Qty: {{ metrics.pagos.pagado.cantidad }}</span>
          </div>
        </div>

        <!-- Pendiente (Periodo) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Mensualidades Pendientes</span>
          <div class="flex items-baseline justify-between mt-1">
            <span class="text-2xl font-bold text-yellow-600">{{ Number(metrics.pagos.pendiente.monto).toFixed(2) }} Bs</span>
            <span class="text-sm text-gray-500">Qty: {{ metrics.pagos.pendiente.cantidad }}</span>
          </div>
        </div>

        <!-- Atrasado (Periodo) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Mensualidades Vencidas</span>
          <div class="flex items-baseline justify-between mt-1">
            <span class="text-2xl font-bold text-red-600">{{ Number(metrics.pagos.atrasado.monto).toFixed(2) }} Bs</span>
            <span class="text-sm text-gray-500">Qty: {{ metrics.pagos.atrasado.cantidad }}</span>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
          <canvas ref="barChartCanvas" height="200"></canvas>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
          <canvas ref="doughnutChartCanvas" height="200"></canvas>
        </div>
      </div>

      <!-- Registraciones Activas por Curso -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
          <h3 class="text-md font-bold text-gray-800">Alumnos Activos por Curso</h3>
          <span class="text-xs text-gray-500 font-medium">Estado de Inscripción: ACTIVA</span>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="curso in metrics.inscripciones_por_curso" :key="curso.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
            <span class="font-medium text-gray-900">{{ curso.nombre }}</span>
            <div class="flex items-center gap-3">
              <span class="text-sm font-semibold text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
                {{ curso.cantidad_activos }} alumno(s) activo(s)
              </span>
            </div>
          </div>
          <div v-if="metrics.inscripciones_por_curso.length === 0" class="px-6 py-8 text-center text-gray-400">
            No se encontraron cursos registrados.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { exportarPDF, exportarExcel } from '@/Composables/useReportExport.js';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};
const qs = (params) => new URLSearchParams(params).toString();

const props = defineProps({
  metrics: Object,
  filters: Object,
});

const startDate = ref(props.filters.start_date || props.metrics.start_date);
const endDate = ref(props.filters.end_date || props.metrics.end_date);

// --- Chart refs and instances ---
const barChartCanvas = ref(null);
const doughnutChartCanvas = ref(null);
let chartInstances = { bar: null, doughnut: null };
let ChartClass = null;

// --- Export columns ---
const exportColumns = [
  { header: 'ID', key: 'id' },
  { header: 'Alumno', key: 'alumno' },
  { header: 'Curso', key: 'curso' },
  { header: 'Nro Mes', key: 'numero_mes' },
  { header: 'Monto Base', key: 'monto_base' },
  { header: 'Vencimiento', key: 'fecha_vencimiento' },
  { header: 'Fecha Pago', key: 'fecha_pago' },
  { header: 'Estado', key: 'estado' },
  { header: 'Nro Recibo', key: 'numero_recibo' },
];

// --- Chart functions ---
async function getChartJs() {
  if (!ChartClass) {
    const mod = await import('chart.js');
    ChartClass = mod.Chart;
    ChartClass.register(...mod.registerables);
  }
  return ChartClass;
}

async function crearGraficos() {
  // Destroy existing charts
  Object.values(chartInstances).forEach(c => c?.destroy());
  chartInstances = { bar: null, doughnut: null };

  if (!props.metrics?.pagos || !props.metrics?.inscripciones_por_curso) return;

  await nextTick();

  const Chart = await getChartJs();

  // --- Bar chart: Pagado / Pendiente / Atrasado ---
  if (barChartCanvas.value) {
    const ctx = barChartCanvas.value.getContext('2d');
    if (ctx) {
      chartInstances.bar = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Pagado', 'Pendiente', 'Atrasado'],
          datasets: [{
            label: 'Monto (Bs)',
            data: [
              props.metrics.pagos.pagado.monto,
              props.metrics.pagos.pendiente.monto,
              props.metrics.pagos.atrasado.monto,
            ],
            backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
            borderRadius: 6,
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false },
            title: { display: true, text: 'Mensualidades por Estado (Bs)', font: { size: 14 } },
          },
          scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Monto (Bs)' } },
          },
        }
      });
    }
  }

  // --- Doughnut chart: Alumnos Activos por Curso ---
  if (doughnutChartCanvas.value) {
    const ctx = doughnutChartCanvas.value.getContext('2d');
    if (ctx) {
      const cursos = props.metrics.inscripciones_por_curso;
      const palette = ['#1D3557', '#A8DADC', '#6C63FF', '#FF6F61', '#2ECC71', '#E67E22', '#9B59B6', '#1ABC9C'];
      chartInstances.doughnut = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: cursos.map(c => c.nombre),
          datasets: [{
            data: cursos.map(c => c.cantidad_activos),
            backgroundColor: cursos.map((_, i) => palette[i % palette.length]),
            borderWidth: 2,
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } },
            title: { display: true, text: 'Alumnos Activos por Curso', font: { size: 14 } },
          },
        }
      });
    }
  }
}

// --- Export handlers ---
async function handleExportarPDF() {
  try {
    const res = await fetch(buildUrl('/reportes/negocio/detalle') + '?' + qs({ start_date: startDate.value, end_date: endDate.value }));
    const json = await res.json();
    const data = json.data || json;
    exportarPDF(
      'Reporte Financiero y de Negocio',
      data,
      exportColumns,
      'reporte_negocio',
      { subtitle: `Período: ${startDate.value} a ${endDate.value}` }
    );
  } catch (err) {
    console.error('Error al exportar PDF:', err);
  }
}

async function handleExportarExcel() {
  try {
    const res = await fetch(buildUrl('/reportes/negocio/detalle') + '?' + qs({ start_date: startDate.value, end_date: endDate.value }));
    const json = await res.json();
    const data = json.data || json;
    exportarExcel(data, exportColumns, 'reporte_negocio');
  } catch (err) {
    console.error('Error al exportar Excel:', err);
  }
}

// --- Lifecycle ---
onMounted(() => {
  crearGraficos();
});

watch(() => props.metrics, () => {
  if (props.metrics?.pagos) {
    crearGraficos();
  }
}, { deep: true });

onUnmounted(() => {
  Object.values(chartInstances).forEach(c => c?.destroy());
});

function aplicarFiltro() {
  router.get(buildUrl('/reportes/negocio'), {
    start_date: startDate.value,
    end_date: endDate.value,
  });
}
</script>
