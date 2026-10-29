<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Welcome Card Nota Menta -->
      <div class="bg-gradient-to-r from-white via-white to-[#F0FDF9] p-6 rounded-2xl shadow-xs border border-[#DDEFEA] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center space-x-2 mb-1">
            <span class="px-2.5 py-0.5 text-xs font-bold bg-[#D1FAE5] text-[#0F766E] rounded-full uppercase tracking-wider">
              Control Académico
            </span>
          </div>
          <h1 class="text-2xl font-black text-[#1F2937] tracking-tight">
            Bienvenido a Scala Musical
          </h1>
          <p class="text-[#6B7280] text-sm mt-1">
            Gestión en tiempo real de estudiantes, cursos, grupos, horarios e inscripciones.
          </p>
        </div>
        <div class="hidden lg:flex items-center space-x-2 bg-white px-4 py-2 rounded-xl border border-[#DDEFEA] shadow-xs">
          <div class="w-3 h-3 rounded-full bg-[#14B8A6] animate-pulse"></div>
          <span class="text-xs font-semibold text-[#0F766E]">Sistema Operativo Activo</span>
        </div>
      </div>

      <!-- Quick Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div
          v-for="stat in statsData"
          :key="stat.label"
          class="bg-white p-5 rounded-2xl shadow-xs border border-[#DDEFEA] flex items-center space-x-4 hover:border-[#6EE7B7] transition-colors"
        >
          <div class="p-3.5 rounded-xl bg-[#D1FAE5] text-[#0F766E] shrink-0 shadow-2xs">
            <component :is="stat.icon" class="w-6 h-6" />
          </div>
          <div class="min-w-0">
            <p class="text-xs text-[#6B7280] font-bold uppercase tracking-wider truncate">{{ stat.label }}</p>
            <p class="text-2xl font-black text-[#1F2937] mt-0.5">{{ stat.value }}</p>
          </div>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Students per Course -->
        <div class="bg-white p-6 rounded-2xl shadow-xs border border-[#DDEFEA]">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-extrabold text-[#1F2937] uppercase tracking-wider">
              Alumnos por Curso
            </h3>
            <span class="text-xs font-semibold text-[#0F766E] bg-[#D1FAE5] px-2 py-0.5 rounded-full">
              Distribución
            </span>
          </div>
          <div class="relative" style="height: 260px">
            <canvas ref="courseChartRef"></canvas>
          </div>
        </div>

        <!-- Enrollments Trend -->
        <div class="bg-white p-6 rounded-2xl shadow-xs border border-[#DDEFEA]">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-extrabold text-[#1F2937] uppercase tracking-wider">
              Inscripciones Académicas (12 meses)
            </h3>
            <span class="text-xs font-semibold text-[#0F766E] bg-[#D1FAE5] px-2 py-0.5 rounded-full">
              Tendencia
            </span>
          </div>
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

// Paleta Nota Menta para gráficos
const paletteNotaMenta = ['#14B8A6', '#10B981', '#06B6D4', '#6EE7B7', '#0F766E', '#3B82F6', '#8B5CF6'];

function buildCourseChart() {
  if (!courseChartRef.value) return;
  const data = props.stats?.alumnos_por_curso || [];
  const labels = data.map(d => d.nombre);
  const values = data.map(d => d.cantidad);

  if (values.length === 0) {
    const ctx = courseChartRef.value;
    const context = ctx.getContext('2d');
    context.font = '13px sans-serif';
    context.fillStyle = '#9CA3AF';
    context.textAlign = 'center';
    context.fillText('Sin datos de alumnos por curso', ctx.width / 2, ctx.height / 2);
    return;
  }
  courseChart = new Chart(courseChartRef.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Alumnos',
        data: values,
        backgroundColor: paletteNotaMenta.slice(0, values.length),
        borderRadius: 6,
        barThickness: 18,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1F2937',
          padding: 10,
          cornerRadius: 8,
          callbacks: {
            label: (ctx) => `${ctx.raw} alumno(s)`
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          grid: { color: '#F0FDF9' },
          ticks: { stepSize: 1, font: { size: 11 }, color: '#6B7280' }
        },
        y: {
          grid: { display: false },
          ticks: { font: { size: 11, weight: 'bold' }, color: '#1F2937' }
        }
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
        backgroundColor: '#14B8A6CC',
        hoverBackgroundColor: '#14B8A6',
        borderColor: '#0F766E',
        borderWidth: 1,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1F2937',
          padding: 10,
          cornerRadius: 8,
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: '#F0FDF9' },
          ticks: { stepSize: 1, color: '#6B7280' }
        },
        x: {
          grid: { display: false },
          ticks: { color: '#6B7280', font: { size: 10 } }
        }
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
  setTimeout(buildCharts, 50);
});

onUnmounted(() => {
  destroyCharts();
});

watch(() => props.stats, () => {
  buildCharts();
});
</script>
