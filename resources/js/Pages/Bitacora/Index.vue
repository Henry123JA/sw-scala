<template>
  <AppLayout>
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Bitácora de Eventos</h1>
      </div>

      <!-- Tab Navigation -->
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
          <button
            class="pb-3 text-sm font-semibold border-b-2 transition-colors"
            :class="activeTab === 'auditoria'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            @click="switchTab('auditoria')"
          >
            Registro de Auditoría
          </button>
          <button
            v-if="auth.rol === 'Propietario'"
            class="pb-3 text-sm font-semibold border-b-2 transition-colors"
            :class="activeTab === 'recursos'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            @click="switchTab('recursos')"
          >
            Recursos Más Accedidos
          </button>
        </nav>
      </div>

      <!-- ==================== TAB: Auditoría ==================== -->
      <template v-if="activeTab === 'auditoria'">
        <!-- Filters & Search -->
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Text search -->
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Buscar (Acción, Recurso, IP)</label>
              <input
                v-model="searchTerm"
                type="text"
                placeholder="Buscar..."
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                @input="onSearch"
              />
            </div>

            <!-- User search -->
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Usuario (Nombre, Email)</label>
              <input
                v-model="searchUser"
                type="text"
                placeholder="Buscar usuario..."
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                @input="onSearch"
              />
            </div>

            <!-- Estado -->
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Estado</label>
              <select
                v-model="selectedEstado"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                @change="onSearch"
              >
                <option value="">Todos los estados</option>
                <option value="EXITO">EXITO</option>
                <option value="FALLO">FALLO</option>
              </select>
            </div>

            <!-- Date ranges -->
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Rango de Fechas</label>
              <div class="flex gap-2">
                <input
                  v-model="startDate"
                  type="date"
                  class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                  @change="onSearch"
                />
                <input
                  v-model="endDate"
                  type="date"
                  class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-xs shadow-sm focus:border-blue-500 focus:outline-none"
                  @change="onSearch"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Audit Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Usuario</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Acción</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Recurso</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Método</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">IP</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ log.fecha ? new Date(log.fecha).toLocaleString('es-ES') : '-' }}
                </td>
                <td class="px-4 py-3 font-medium text-gray-900">
                  <div v-if="log.usuario">
                    <span class="block font-semibold">{{ log.usuario.nombres }} {{ log.usuario.apellidos }}</span>
                    <span class="block text-xs text-gray-500">{{ log.usuario.email }}</span>
                  </div>
                  <span v-else class="text-gray-400 text-xs">Sistema / Anónimo</span>
                </td>
                <td class="px-4 py-3 text-gray-700 font-medium">
                  {{ log.accion }}
                </td>
                <td class="px-4 py-3 text-gray-500 font-mono text-xs max-w-xs truncate" :title="log.recurso">
                  {{ log.recurso }}
                </td>
                <td class="px-4 py-3">
                  <span class="inline-flex rounded px-1.5 py-0.5 text-xs font-bold font-mono bg-gray-100 text-gray-700">
                    {{ log.metodo }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">
                  {{ log.ip }}
                </td>
                <td class="px-4 py-3">
                  <span
                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="log.estado === 'EXITO' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                  >
                    {{ log.estado }}
                  </span>
                </td>
              </tr>
              <tr v-if="logs.data.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                  No se encontraron registros en la bitácora.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination (50 items) -->
        <div v-if="logs.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
          <span>Mostrando página {{ logs.current_page }} de {{ logs.last_page }} (Total: {{ logs.total }} logs)</span>
          <div class="flex gap-2">
            <Link
              v-if="logs.prev_page_url"
              :href="logs.prev_page_url"
              class="rounded border px-3 py-1 bg-white hover:bg-gray-50"
              :data="filters"
            >
              Anterior
            </Link>
            <Link
              v-if="logs.next_page_url"
              :href="logs.next_page_url"
              class="rounded border px-3 py-1 bg-white hover:bg-gray-50"
              :data="filters"
            >
              Siguiente
            </Link>
          </div>
        </div>
      </template>

      <!-- ==================== TAB: Recursos Más Accedidos ==================== -->
      <template v-if="activeTab === 'recursos'">
        <!-- Date Filters for Ranking -->
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Desde</label>
              <input
                v-model="rankingDesde"
                type="date"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Hasta</label>
              <input
                v-model="rankingHasta"
                type="date"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
              />
            </div>
            <div>
              <button
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors"
                @click="applyRankingFilter"
              >
                Filtrar
              </button>
            </div>
            <div v-if="dateError" class="text-sm text-red-600 font-medium">
              {{ dateError }}
            </div>
          </div>
        </div>

        <!-- Ranking Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 w-12">Posición</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Recurso</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Ruta</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Total de Visitas</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Última Visita</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(recurso, index) in recursos"
                :key="recurso.menu_id"
                class="hover:bg-blue-50 cursor-pointer transition-colors"
                @click="openDrillDown(recurso.menu_id)"
              >
                <td class="px-4 py-3 text-gray-500 font-mono text-center">
                  #{{ index + 1 }}
                </td>
                <td class="px-4 py-3 font-medium text-gray-900">
                  <div class="flex items-center gap-2">
                    <span class="text-gray-500 text-base" v-html="iconFor(recurso.menu_icono)"></span>
                    {{ recurso.menu_nombre }}
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-500 font-mono text-xs">
                  {{ recurso.menu_ruta }}
                </td>
                <td class="px-4 py-3 text-gray-900 font-bold text-right">
                  {{ recurso.total_visitas.toLocaleString('es-BO') }}
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs text-right whitespace-nowrap">
                  {{ recurso.ultima_visita ? new Date(recurso.ultima_visita).toLocaleString('es-ES') : '-' }}
                </td>
              </tr>
              <tr v-if="recursos.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                  <template v-if="rankingDesde || rankingHasta">
                    No se encontraron recursos para el período seleccionado.
                  </template>
                  <template v-else>
                    No hay datos de visitas registrados aún.
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- ==================== DRILL-DOWN MODAL ==================== -->
      <div
        v-if="detalleRecurso"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="closeDrillDown"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
          <!-- Modal header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold" :style="{ color: 'var(--color-primary)' }">
              Detalle de accesos — {{ detalleRecurso.menu_nombre }}
            </h2>
            <button
              class="rounded-full p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
              @click="closeDrillDown"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Modal body -->
          <div class="p-6">
            <template v-if="detalleRecurso.visitas && detalleRecurso.visitas.length > 0">
              <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Usuario</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Visitas</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Última Visita</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="visita in detalleRecurso.visitas" :key="visita.usuario_id" class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <span class="block font-medium text-gray-900">
                        {{ visita.usuario_nombres }} {{ visita.usuario_apellidos }}
                      </span>
                      <span class="block text-xs text-gray-500">{{ visita.usuario_email }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900">
                      {{ visita.contador.toLocaleString('es-BO') }}
                    </td>
                    <td class="px-4 py-3 text-right text-xs text-gray-500 whitespace-nowrap">
                      {{ visita.ultima_visita ? new Date(visita.ultima_visita).toLocaleString('es-ES') : '-' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </template>
            <template v-else>
              <p class="text-center text-gray-400 py-8">
                No hay visitas para este recurso.
              </p>
            </template>
          </div>

          <!-- Modal footer -->
          <div class="flex justify-end px-6 py-4 border-t border-gray-200">
            <button
              title="Cerrar"
              class="rounded-md border border-gray-300 px-2 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              @click="closeDrillDown"
            >
              <component :is="Lucide.X" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const props = defineProps({
  logs: Object,
  filters: Object,
  recursos: Array,
  tab: String,
  detalleRecurso: Object,
});

const { auth } = usePage().props;

// ── Tab state ──
const activeTab = ref(props.tab === 'recursos' ? 'recursos' : 'auditoria');

function switchTab(tab) {
  activeTab.value = tab;
  router.get(buildUrl('/bitacora'), { tab }, {
    preserveState: true,
    replace: true,
  });
}

// ── Auditoría tab state ──
const searchTerm = ref(props.filters?.buscar || '');
const searchUser = ref(props.filters?.usuario || '');
const selectedEstado = ref(props.filters?.estado || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

let debounceTimer = null;
function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    router.get(buildUrl('/bitacora'), {
      buscar: searchTerm.value,
      usuario: searchUser.value,
      estado: selectedEstado.value,
      start_date: startDate.value,
      end_date: endDate.value,
      tab: activeTab.value,
    }, {
      preserveState: true,
      replace: true,
    });
  }, 300);
}

// ── Ranking tab state ──
const rankingDesde = ref('');
const rankingHasta = ref('');
const dateError = ref('');

const recursos = computed(() => props.recursos || []);

function applyRankingFilter() {
  dateError.value = '';

  // Client-side validation: desde <= hasta
  if (rankingDesde.value && rankingHasta.value) {
    if (rankingDesde.value > rankingHasta.value) {
      dateError.value = "La fecha 'desde' no puede ser posterior a la fecha 'hasta'.";
      return;
    }
  }

  router.get(buildUrl('/bitacora'), {
    tab: 'recursos',
    ranking_desde: rankingDesde.value,
    ranking_hasta: rankingHasta.value,
  }, {
    preserveState: true,
    replace: true,
  });
}

// ── Drill-down modal ──
function openDrillDown(menuId) {
  router.get(buildUrl('/bitacora'), {
    ranking_detalle_menu_id: menuId,
    ranking_desde: rankingDesde.value || undefined,
    ranking_hasta: rankingHasta.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  });
}

function closeDrillDown() {
  router.get(buildUrl('/bitacora'), {
    tab: 'recursos',
    ranking_desde: rankingDesde.value || undefined,
    ranking_hasta: rankingHasta.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  });
}

// ── Helpers ──
function iconFor(icono) {
  // Simple SVG icon mapping for common Lucide icon names
  const icons = {
    LayoutDashboard: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>',
    Users: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" /></svg>',
    DoorOpen: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.66l7-4.67a2 2 0 012.22 0l7 4.67A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19h18" /></svg>',
    BookOpen: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>',
    Folder: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>',
    Clock: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    UserPlus: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>',
    CreditCard: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>',
    LineChart: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
    FileText: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>',
    History: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    BarChart3: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
    ShieldAlert: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>',
  };
  return icons[icono] || '📄';
}
</script>
