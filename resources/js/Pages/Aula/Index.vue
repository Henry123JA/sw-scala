<template>
  <AppLayout>
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Gestión de Aulas</h1>
        <Link
          :href="buildUrl('/aulas/crear')"
          class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition"
        >
          + Nueva Aula
        </Link>
      </div>

      <!-- Filters & Search -->
      <div class="flex flex-col sm:flex-row gap-4 items-center">
        <input
          v-model="searchTerm"
          type="text"
          placeholder="Buscar por nombre..."
          class="w-full sm:max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          @input="onSearch"
        />
        <select
          v-model="selectedTipo"
          class="w-full sm:max-w-xs rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          @change="onSearch"
        >
          <option value="">Todos los tipos</option>
          <option v-for="t in tipos" :key="t" :value="t">{{ t }}</option>
        </select>
      </div>

      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success" class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.errors?.general" class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        {{ $page.props.errors.general }}
      </div>

      <!-- Table -->
      <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Capacidad</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Ubicación/Piso</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Equipamiento</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Estado</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="aula in aulas.data" :key="aula.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ aula.nombre }}</td>
              <td class="px-4 py-3 text-gray-600">{{ aula.tipo ?? '-' }}</td>
              <td class="px-4 py-3 text-gray-600">{{ aula.capacidad }}</td>
              <td class="px-4 py-3 text-gray-600">{{ aula.ubicacion_piso ?? '-' }}</td>
              <td class="px-4 py-3 text-gray-600">{{ aula.equipamiento ?? '-' }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="aula.estado === 'ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                >
                  {{ aula.estado }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center">
                  <Link
                    :href="buildUrl(`/aulas/${aula.id}/editar`)"
                    class="rounded px-2 py-1 text-xs bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 cursor-pointer"
                    title="Editar"
                  >
                    <component :is="Lucide.Pencil" class="w-4 h-4 shrink-0" />
                  </Link>
                  <button
                    @click="confirmarEliminar(aula)"
                    class="rounded px-2 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 cursor-pointer"
                    title="Eliminar"
                  >
                    <component :is="Lucide.Trash2" class="w-4 h-4 shrink-0" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="aulas.data.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                No se encontraron aulas.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="aulas.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
        <span>Página {{ aulas.current_page }} de {{ aulas.last_page }}</span>
        <div class="flex gap-2">
          <Link
            v-if="aulas.prev_page_url"
            :href="aulas.prev_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="{ buscar: searchTerm, tipo: selectedTipo }"
          >
            Anterior
          </Link>
          <Link
            v-if="aulas.next_page_url"
            :href="aulas.next_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="{ buscar: searchTerm, tipo: selectedTipo }"
          >
            Siguiente
          </Link>
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
  aulas: Object,
  tipos: Array,
  buscar: { type: String, default: '' },
  tipo: { type: String, default: '' },
});

const searchTerm = ref(props.buscar);
const selectedTipo = ref(props.tipo);

let debounceTimer = null;
function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    router.get(buildUrl('/aulas'), {
      buscar: searchTerm.value,
      tipo: selectedTipo.value,
    }, {
      preserveState: true,
      replace: true,
    });
  }, 300);
}

function confirmarEliminar(aula) {
  if (confirm(`¿Está seguro de eliminar el aula "${aula.nombre}"?`)) {
    router.delete(buildUrl(`/aulas/${aula.id}`));
  }
}
</script>
