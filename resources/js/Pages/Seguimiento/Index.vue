<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Seguimiento Académico</h1>
        <p class="text-sm text-gray-500">Supervisión de mensualidades atrasadas y próximas a vencer.</p>
      </div>

      <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm flex flex-col sm:flex-row gap-4 items-center">
        <div class="w-full sm:flex-1">
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Buscar Alumno</label>
          <input v-model="searchTerm" type="text" placeholder="Buscar por nombres, apellidos..."
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" @input="onSearch" />
        </div>
        <div class="w-full sm:w-48">
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Días próximos</label>
          <input v-model.number="limitDays" type="number" min="1" max="90"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" @input="onSearch" />
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- ATRASADOS (VENCIDAS) -->
        <div class="bg-white rounded-lg border border-red-200 shadow-sm overflow-hidden">
          <div class="bg-red-50 border-b border-red-200 px-4 py-3 flex items-center justify-between">
            <h2 class="text-lg font-bold text-red-700 flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
              Alumnos Vencidos
            </h2>
            <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-800">{{ atrasados.total ?? 0 }}</span>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600">Alumno</th>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600">Curso / Grupo</th>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600">Vencimiento</th>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600">Monto / Retraso</th>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in (atrasados.data ?? [])" :key="item.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-gray-900">
                    <div>{{ item.alumno?.usuario?.nombres ?? '-' }} {{ item.alumno?.usuario?.apellidos ?? '' }}</div>
                    <div class="text-xs text-gray-400">{{ item.alumno?.usuario?.ci ?? '-' }}</div>
                  </td>
                  <td class="px-4 py-3 text-gray-600">
                    <span class="font-semibold text-gray-800">{{ item.grupo?.curso?.nombre ?? '-' }}</span>
                    <span class="block text-xs text-gray-500">Grupo: {{ item.grupo?.codigo_grupo ?? '-' }}</span>
                  </td>
                  <td class="px-4 py-3 text-gray-600">
                    {{ item.fecha_vencimiento ? new Date(item.fecha_vencimiento).toLocaleDateString('es-ES') : '-' }}
                  </td>
                  <td class="px-4 py-3">
                    <span class="font-semibold text-red-600">{{ fmonto(item.monto_mensual) }} Bs</span>
                    <span class="block text-xs font-bold text-red-500">{{ diasAtraso(item) }} días</span>
                  </td>
                  <td class="px-4 py-3">
                    <Link
                      :href="item.alumno_id ? buildUrl(`/seguimiento/${item.alumno_id}`) : '#'"
                      class="rounded px-2.5 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 transition font-medium cursor-pointer"
                      title="Ver detalle">
                      <component :is="Lucide.Eye" class="w-4 h-4 shrink-0" />
                    </Link>
                  </td>
                </tr>
                <tr v-if="!atrasados.data?.length">
                  <td colspan="5" class="px-4 py-8 text-center text-gray-400">No hay alumnos vencidos.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- PRÓXIMOS A VENCER -->
        <div class="bg-white rounded-lg border border-yellow-200 shadow-sm overflow-hidden">
          <div class="bg-yellow-50 border-b border-yellow-200 px-4 py-3 flex items-center justify-between">
            <h2 class="text-lg font-bold text-yellow-700 flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
              Próximos a Vencer
            </h2>
            <span class="rounded bg-yellow-100 px-2 py-0.5 text-xs font-bold text-yellow-800">{{ proximos.total ?? 0 }}</span>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600">Alumno</th>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600">Curso</th>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600">Vence en</th>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in (proximos.data ?? [])" :key="item.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-gray-900">
                    {{ item.alumno?.usuario?.nombres ?? '-' }} {{ item.alumno?.usuario?.apellidos ?? '' }}
                  </td>
                  <td class="px-4 py-3 text-gray-600">{{ item.grupo?.curso?.nombre ?? '-' }}</td>
                  <td class="px-4 py-3">
                    <span class="font-semibold text-yellow-700">{{ diasRestantes(item) }} días</span>
                    <span class="block text-xs text-gray-400">{{ item.fecha_vencimiento ? new Date(item.fecha_vencimiento).toLocaleDateString('es-ES') : '-' }}</span>
                  </td>
                  <td class="px-4 py-3">
                    <Link
                      :href="item.alumno_id ? buildUrl(`/seguimiento/${item.alumno_id}`) : '#'"
                      class="rounded px-2.5 py-1 text-xs bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 transition font-medium cursor-pointer"
                      title="Ver detalle">
                      <component :is="Lucide.Eye" class="w-4 h-4 shrink-0" />
                    </Link>
                  </td>
                </tr>
                <tr v-if="!proximos.data?.length">
                  <td colspan="4" class="px-4 py-8 text-center text-gray-400">No hay vencimientos próximos.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
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
  atrasados: Object,
  proximos: Object,
  buscar: String,
  dias: Number,
});

const searchTerm = ref('');
const limitDays = ref(7);

function onSearch() {
  router.get(buildUrl('/seguimiento'), {
    buscar: searchTerm.value || undefined,
    dias: limitDays.value || 7,
  }, { preserveState: true, replace: true });
}

function fmonto(val) {
  const n = parseFloat(val);
  return isNaN(n) ? '0.00' : n.toFixed(2);
}

function diasAtraso(ins) {
  if (!ins.fecha_vencimiento) return 0;
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const venc = new Date(ins.fecha_vencimiento);
  venc.setHours(0, 0, 0, 0);
  const diff = Math.ceil((hoy - venc) / (1000 * 60 * 60 * 24));
  return isNaN(diff) ? 0 : Math.max(0, diff);
}

function diasRestantes(ins) {
  if (!ins.fecha_vencimiento) return 0;
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const venc = new Date(ins.fecha_vencimiento);
  venc.setHours(0, 0, 0, 0);
  const diff = Math.ceil((venc - hoy) / (1000 * 60 * 60 * 24));
  return isNaN(diff) ? 0 : Math.max(0, diff);
}
</script>
