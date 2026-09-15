<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Bitácora de Accesos y Seguridad</h1>
          <p class="text-gray-500">Historial completo de acciones y operaciones realizadas en el sistema.</p>
        </div>
        <div class="flex items-center gap-2">
          <a
            :href="buildUrl('/reportes/acceso/exportar') + '?' + qs(currentFilters)"
            class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-green-700 transition"
          >
            <!-- CSV Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Exportar CSV
          </a>
          <button
            @click="handleExportarPDF"
            class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Exportar PDF
          </button>
          <button
            @click="handleExportarExcel"
            class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Exportar Excel
          </button>
        </div>
      </div>

      <!-- Filters Panel -->
      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Usuario / Email</label>
          <input
            v-model="filters.usuario"
            type="text"
            placeholder="Buscar usuario o email..."
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
            @input="debouncedSearch"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Acción</label>
          <input
            v-model="filters.accion"
            type="text"
            placeholder="Filtrar por acción..."
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
            @input="debouncedSearch"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Estado</label>
          <select
            v-model="filters.estado"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
            @change="aplicarFiltros"
          >
            <option value="">Todos</option>
            <option value="EXITO">EXITO</option>
            <option value="FALLO">FALLO</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Desde</label>
          <input
            v-model="filters.start_date"
            type="date"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
            @change="aplicarFiltros"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Hasta</label>
          <input
            v-model="filters.end_date"
            type="date"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
            @change="aplicarFiltros"
          />
        </div>
      </div>

      <!-- Logs Table -->
      <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">ID</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Usuario</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Acción</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Recurso</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Método</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Estado</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">IP</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha / Hora</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-semibold text-gray-500">{{ log.id }}</td>
              <td class="px-4 py-3">
                <div v-if="log.usuario" class="flex flex-col">
                  <span class="font-medium text-gray-900">{{ log.usuario.nombres }} {{ log.usuario.apellidos }}</span>
                  <span class="text-xs text-gray-400">{{ log.usuario.email }}</span>
                </div>
                <span v-else class="text-gray-400 italic">No Autenticado</span>
              </td>
              <td class="px-4 py-3 text-gray-700 font-medium">{{ log.accion }}</td>
              <td class="px-4 py-3 text-gray-500 font-mono text-xs max-w-xs truncate" :title="log.recurso">{{ log.recurso ?? '-' }}</td>
              <td class="px-4 py-3 text-gray-500">
                <span class="rounded px-1.5 py-0.5 text-xs font-bold font-mono" :class="metodoColor(log.metodo)">
                  {{ log.metodo ?? '-' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="log.estado === 'EXITO' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                >
                  {{ log.estado }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ log.ip ?? '-' }}</td>
              <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ formatFecha(log.fecha) }}</td>
            </tr>
            <tr v-if="logs.data.length === 0">
              <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                No se encontraron registros en la bitácora con los criterios seleccionados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="logs.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
        <span>Página {{ logs.current_page }} de {{ logs.last_page }}</span>
        <div class="flex gap-2">
          <Link
            v-if="logs.prev_page_url"
            :href="logs.prev_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="currentFilters"
          >
            Anterior
          </Link>
          <Link
            v-if="logs.next_page_url"
            :href="logs.next_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="currentFilters"
          >
            Siguiente
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
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
  logs: Object,
  filters: Object,
});

const filters = ref({
  usuario: props.filters.usuario || '',
  accion: props.filters.accion || '',
  estado: props.filters.estado || '',
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || '',
});

const currentFilters = computed(() => {
  const params = {};
  if (filters.value.usuario) params.usuario = filters.value.usuario;
  if (filters.value.accion) params.accion = filters.value.accion;
  if (filters.value.estado) params.estado = filters.value.estado;
  if (filters.value.start_date) params.start_date = filters.value.start_date;
  if (filters.value.end_date) params.end_date = filters.value.end_date;
  return params;
});

let debounceTimer = null;
function debouncedSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    aplicarFiltros();
  }, 400);
}

function aplicarFiltros() {
  router.get(buildUrl('/reportes/acceso'), currentFilters.value, {
    replace: true,
  });
}

function formatFecha(fecha) {
  if (!fecha) return '-';
  const d = new Date(fecha);
  return d.toLocaleString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
}

function metodoColor(m) {
  if (!m) return 'bg-gray-100 text-gray-700';
  switch (m.toUpperCase()) {
    case 'GET': return 'bg-green-50 text-green-700 border border-green-200';
    case 'POST': return 'bg-blue-50 text-blue-700 border border-blue-200';
    case 'PUT': case 'PATCH': return 'bg-yellow-50 text-yellow-700 border border-yellow-200';
    case 'DELETE': return 'bg-red-50 text-red-700 border border-red-200';
    default: return 'bg-gray-50 text-gray-700 border border-gray-200';
  }
}

// --- Export columns ---
const exportColumns = [
  { header: 'ID', key: 'id' },
  { header: 'Usuario', key: 'usuario' },
  { header: 'Email', key: 'email' },
  { header: 'Acción', key: 'accion' },
  { header: 'Recurso', key: 'recurso' },
  { header: 'Método', key: 'metodo' },
  { header: 'Estado', key: 'estado' },
  { header: 'IP', key: 'ip' },
  { header: 'Fecha', key: 'fecha' },
];

async function handleExportarPDF() {
  try {
    const res = await fetch(buildUrl('/reportes/acceso/detalle') + '?' + qs(currentFilters.value));
    const rawData = await res.json();
    const mappedData = rawData.map(log => ({
      id: log.id,
      usuario: log.usuario ? `${log.usuario.nombres} ${log.usuario.apellidos}` : 'No Autenticado',
      email: log.usuario?.email ?? '',
      accion: log.accion,
      recurso: log.recurso ?? '',
      metodo: log.metodo ?? '',
      estado: log.estado,
      ip: log.ip ?? '',
      fecha: log.fecha ? new Date(log.fecha).toLocaleString('es-ES') : '',
    }));
    exportarPDF(
      'Bitácora de Accesos y Seguridad',
      mappedData,
      exportColumns,
      'reporte_accesos'
    );
  } catch (err) {
    console.error('Error al exportar PDF:', err);
  }
}

async function handleExportarExcel() {
  try {
    const res = await fetch(buildUrl('/reportes/acceso/detalle') + '?' + qs(currentFilters.value));
    const rawData = await res.json();
    const mappedData = rawData.map(log => ({
      id: log.id,
      usuario: log.usuario ? `${log.usuario.nombres} ${log.usuario.apellidos}` : 'No Autenticado',
      email: log.usuario?.email ?? '',
      accion: log.accion,
      recurso: log.recurso ?? '',
      metodo: log.metodo ?? '',
      estado: log.estado,
      ip: log.ip ?? '',
      fecha: log.fecha ? new Date(log.fecha).toLocaleString('es-ES') : '',
    }));
    exportarExcel(mappedData, exportColumns, 'reporte_accesos');
  } catch (err) {
    console.error('Error al exportar Excel:', err);
  }
}
</script>
