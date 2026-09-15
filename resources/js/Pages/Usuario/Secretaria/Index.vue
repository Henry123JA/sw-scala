<template>
  <AppLayout>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Secretarias</h1>
        <Link
          :href="buildUrl('/secretarias/crear')"
          class="inline-flex items-center justify-center rounded-md bg-blue-600 p-2 text-white shadow hover:bg-blue-700 transition cursor-pointer"
          title="Nueva Secretaria"
        >
          <component :is="Lucide.Plus" class="w-5 h-5" />
        </Link>
      </div>

      <div class="flex items-center gap-2">
        <input
          v-model="searchTerm"
          type="text"
          placeholder="Buscar por nombre, apellido o CI..."
          class="w-full max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          @input="onSearch"
        />
      </div>

      <div v-if="$page.props.flash?.success" class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
        {{ $page.props.flash.success }}
      </div>

      <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">CI</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="sec in secretarias.data" :key="sec.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ sec.nombres }} {{ sec.apellidos }}</td>
              <td class="px-4 py-3 text-gray-600">{{ sec.ci }}</td>
              <td class="px-4 py-3 text-gray-600">{{ sec.email }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center">
                  <Link
                    :href="buildUrl(`/secretarias/${sec.id}/editar`)"
                    class="rounded px-2 py-1 text-xs bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 cursor-pointer"
                    title="Editar"
                  >
                    <component :is="Lucide.Pencil" class="w-4 h-4 shrink-0" />
                  </Link>
                  <button
                    @click="confirmarBaja(sec)"
                    class="rounded px-2 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 cursor-pointer"
                    title="Dar de baja"
                  >
                    <component :is="Lucide.UserX" class="w-4 h-4 shrink-0" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="secretarias.data.length === 0">
              <td colspan="4" class="px-4 py-8 text-center text-gray-400">No se encontraron secretarias.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="secretarias.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
        <span>Página {{ secretarias.current_page }} de {{ secretarias.last_page }}</span>
        <div class="flex gap-2">
          <Link v-if="secretarias.prev_page_url" :href="secretarias.prev_page_url" class="rounded border px-3 py-1 hover:bg-gray-50">Anterior</Link>
          <Link v-if="secretarias.next_page_url" :href="secretarias.next_page_url" class="rounded border px-3 py-1 hover:bg-gray-50">Siguiente</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
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
  secretarias: Object,
  buscar: { type: String, default: '' },
});

const searchTerm = ref(props.buscar);

let debounceTimer = null;
function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    router.get(buildUrl('/secretarias'), { buscar: searchTerm.value }, {
      preserveState: true,
      replace: true,
    });
  }, 300);
}

function confirmarBaja(sec) {
  if (confirm(`¿Dar de baja a ${sec.nombres} ${sec.apellidos}?`)) {
    router.delete(buildUrl(`/secretarias/${sec.id}`));
  }
}
</script>
