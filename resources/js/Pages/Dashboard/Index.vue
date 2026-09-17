<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Welcome Card -->
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{ color: 'var(--color-primary)' }">
            Bienvenido al Sistema de Gestión Académica
          </h1>
          <p class="text-gray-500 text-sm">
            Control académico de estudiantes, cursos, grupos, horarios e inscripciones en tiempo real.
          </p>
        </div>
      </div>

      <!-- Quick Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="stat in statsData" :key="stat.label" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div class="p-3 rounded-lg bg-gray-50" :style="{ color: 'var(--color-primary)' }">
            <component :is="stat.icon" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">{{ stat.label }}</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-0.5">{{ stat.value }}</p>
          </div>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Students per Course -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Alumnos por Curso</h3>
          <div class="relative" style="height: 260px">
            <canvas ref="courseChartRef"></canvas>
          </div>
        </div>

        <!-- Enrollments Trend -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Inscripciones Académicas (12 meses)</h3>
          <div class="relative" style="height: 260px">
            <canvas ref="enrollmentChartRef"></canvas>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';
import {
  Chart,
  LineController,
  BarController,
  DoughnutController,
  LinearScale,
  CategoryScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
} from 'chart.js';

Chart.register(
  LineController, BarController, DoughnutController,
  LinearScale, CategoryScale,
  PointElement, LineElement, BarElement, ArcElement,
  Tooltip, Legend
);

const props = defineProps({
  stats: Object,
});

const statsData = computed(() => [
  {
    label: 'Alumnos Activos',
    value: props.stats?.alumnos_activos ?? 0,
    icon: Lucide.Users
  },
  {
    label: 'Grupos Abiertos',
    value: props.stats?.grupos_abiertos ?? 0,
    icon: Lucide.Folder
  },
  {
    label: 'Visitas Globales',
    value: props.stats?.visitas_globales ?? 0,
    icon: Lucide.Activity
  }
]);

// ── Chart refs ──────────────────────────────────────────────────────────────
const courseChartRef = ref(null);
const enrollmentChartRef = ref(null);

let courseChart = null;
let enrollmentChart = null;

const PRIMARY = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#2563eb';
const palette = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];

function buildCourseChart() {
  if (!courseChartRef.value) return;
  const data = props.stats?.alumnos_por_curso || [];
  const labels = data.map(d => d.nombre);
  const values = data.map(d => d.cantidad);

  if (values.length === 0) {
    // Show "sin datos" message
    const ctx = courseChartRef.value;
    ctx.getContext('2d').font = '13px sans-serif';
    ctx.getContext('2d').fillStyle = '#9CA3AF';
    ctx.getContext('2d').textAlign = 'center';
    ctx.getContext('2d').fillText('Sin datos de alumnos por curso', ctx.width / 2, ctx.height / 2);
    return;
  }
  courseChart = new Chart(courseChartRef.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Alumnos',
        data: values,
        backgroundColor: palette.slice(0, values.length),
        borderRadius: 4,
        barThickness: 20,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => `${ctx.raw} alumno(s)`
          }
        }
      },
      scales: {
        x: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } },
        y: { grid: { display: false }, ticks: { font: { size: 11 } } }
      }
    }
  });
}

function buildEnrollmentChart() {
  if (!enrollmentChartRef.value) return;
  const data = props.stats?.inscripciones_12_meses || [];
  const labels = data.map(d => d.mes);
  const values = data.map(d => d.inscripciones);

  enrollmentChart = new Chart(enrollmentChartRef.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Inscripciones',
        data: values,
        backgroundColor: PRIMARY + '80',
        borderColor: PRIMARY,
        borderWidth: 1,
        borderRadius: 4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } },
        x: { grid: { display: false } }
      }
    }
  });
}

function destroyCharts() {
  [courseChart, enrollmentChart].forEach(c => c?.destroy());
  courseChart = enrollmentChart = null;
}

function buildCharts() {
  destroyCharts();
  buildCourseChart();
  buildEnrollmentChart();
}

onMounted(() => {
  // Need a tick for the computed PRIMARY to be available
  setTimeout(buildCharts, 50);
});

onUnmounted(() => {
  destroyCharts();
});

// Rebuild if stats data changes (e.g. soft navigation)
watch(() => props.stats, () => {
  buildCharts();
});
</script>
