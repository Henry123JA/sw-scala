<template>
  <AppLayout>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Docentes</h1>
        <Link
          :href="buildUrl('/docentes/crear')"
          class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition cursor-pointer"
          title="Nuevo Docente"
        >
          <component :is="Lucide.Plus" class="w-5 h-5" />
        </Link>
      </div>

      <div class="flex items-center gap-2">
        <input
          v-model="searchTerm"
          type="text"
          placeholder="Buscar por nombre, apellido, CI o código..."
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
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Especialidades</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Estado</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="docente in docentes.data" :key="docente.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ docente.nombres }} {{ docente.apellidos }}</td>
              <td class="px-4 py-3 text-gray-600">{{ docente.ci }}</td>
              <td class="px-4 py-3 text-gray-600">{{ docente.email }}</td>
              <td class="px-4 py-3 text-gray-600">
                <span v-for="esp in docente.docente?.especialidades" :key="esp.id"
                  class="mr-1 inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700">
                  {{ esp.nombre }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="docente.docente?.estado === 'ACTIVO'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-red-100 text-red-700'"
                >
                  {{ docente.docente?.estado ?? 'ACTIVO' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center">
                  <Link
                    :href="buildUrl(`/docentes/${docente.id}/editar`)"
                    class="rounded px-2 py-1 text-xs bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 cursor-pointer"
                    title="Editar"
                  >
                    <component :is="Lucide.Pencil" class="w-4 h-4 shrink-0" />
                  </Link>
                  <button
                    @click="confirmarBaja(docente)"
                    class="rounded px-2 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 cursor-pointer"
                    title="Dar de baja"
                  >
                    <component :is="Lucide.UserX" class="w-4 h-4 shrink-0" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="docentes.data.length === 0">
              <td colspan="6" class="px-4 py-8 text-center text-gray-400">No se encontraron docentes.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="docentes.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
        <span>Página {{ docentes.current_page }} de {{ docentes.last_page }}</span>
        <div class="flex gap-2">
          <Link v-if="docentes.prev_page_url" :href="docentes.prev_page_url" class="rounded border px-3 py-1 hover:bg-gray-50">Anterior</Link>
          <Link v-if="docentes.next_page_url" :href="docentes.next_page_url" class="rounded border px-3 py-1 hover:bg-gray-50">Siguiente</Link>
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
  docentes: Object,
  buscar: { type: String, default: '' },
});

const searchTerm = ref(props.buscar);

let debounceTimer = null;
function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    router.get(buildUrl('/docentes'), { buscar: searchTerm.value }, {
      preserveState: true,
      replace: true,
    });
  }, 300);
}

function confirmarBaja(docente) {
  if (confirm(`¿Dar de baja a ${docente.nombres} ${docente.apellidos}?`)) {
    router.delete(buildUrl(`/docentes/${docente.id}`));
  }
}
</script>
