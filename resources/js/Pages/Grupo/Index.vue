<template>
  <AppLayout>
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Gestión de Grupos</h1>
        <Link
          v-if="canEdit"
          :href="buildUrl('/grupos/crear')"
          class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition cursor-pointer"
          title="Nuevo Grupo"
        >
          <component :is="Lucide.Plus" class="w-5 h-5" />
        </Link>
      </div>

      <!-- Filters & Search -->
      <div class="flex flex-col sm:flex-row gap-4 items-center">
        <input
          v-model="searchTerm"
          type="text"
          placeholder="Buscar por código de grupo..."
          class="w-full sm:max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          @input="onSearch"
        />
        <select
          v-model="selectedCursoId"
          class="w-full sm:max-w-xs rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          @change="onSearch"
        >
          <option value="0">Todos los cursos</option>
          <option v-for="c in cursos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
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
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Código de Grupo</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Curso</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Docente</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Capacidad Máxima</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Nivel</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Estado</th>
              <th v-if="canEdit || canDelete" class="px-4 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="grupo in grupos.data" :key="grupo.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-semibold text-gray-900">{{ grupo.codigo_grupo }}</td>
              <td class="px-4 py-3 text-gray-600">{{ grupo.curso?.nombre ?? '-' }}</td>
              <td class="px-4 py-3 text-gray-600">
                {{ grupo.docente?.usuario ? `${grupo.docente.usuario.nombres} ${grupo.docente.usuario.apellidos}` : '-' }}
              </td>
              <td class="px-4 py-3 text-gray-600">{{ grupo.capacidad_maxima }}</td>
              <td class="px-4 py-3 text-gray-600">{{ grupo.nivel ?? '-' }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="{
                    'bg-green-100 text-green-700': grupo.estado === 'ACTIVO',
                    'bg-yellow-100 text-yellow-700': grupo.estado === 'CERRADO',
                    'bg-red-100 text-red-700': grupo.estado === 'CANCELADO',
                    'bg-gray-100 text-gray-700': grupo.estado === 'INACTIVO',
                  }"
                >
                  {{ grupo.estado }}
                </span>
              </td>
              <td v-if="canEdit" class="px-4 py-3">
                <div class="flex items-center">
                  <Link
                    v-if="canEdit"
                    :href="buildUrl(`/grupos/${grupo.id}/editar`)"
                    class="rounded px-2 py-1 text-xs bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 cursor-pointer"
                    title="Editar"
                  >
                    <component :is="Lucide.Pencil" class="w-4 h-4 shrink-0" />
                  </Link>
                  <button
                    v-if="canDelete"
                    @click="confirmarEliminar(grupo)"
                    class="rounded px-2 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 cursor-pointer"
                    title="Eliminar"
                  >
                    <component :is="Lucide.Trash2" class="w-4 h-4 shrink-0" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="grupos.data.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                No se encontraron grupos.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="grupos.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
        <span>Página {{ grupos.current_page }} de {{ grupos.last_page }}</span>
        <div class="flex gap-2">
          <Link
            v-slot="{ href }"
            v-if="grupos.prev_page_url"
            :href="grupos.prev_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="{ buscar: searchTerm, curso_id: selectedCursoId }"
          >
            Anterior
          </Link>
          <Link
            v-slot="{ href }"
            v-if="grupos.next_page_url"
            :href="grupos.next_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="{ buscar: searchTerm, curso_id: selectedCursoId }"
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
  grupos: Object,
  cursos: Array,
  buscar: { type: String, default: '' },
  cursoId: { type: Number, default: 0 },
});

const page = usePage();
const canEdit = computed(() => ['Propietario', 'Secretaria'].includes(page.props.auth?.rol));
const canDelete = computed(() => page.props.auth?.rol === 'Propietario');

const searchTerm = ref(props.buscar);
const selectedCursoId = ref(props.cursoId);

let debounceTimer = null;
function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    router.get(buildUrl('/grupos'), {
      buscar: searchTerm.value,
      curso_id: selectedCursoId.value,
    }, {
      preserveState: true,
      replace: true,
    });
  }, 300);
}

function confirmarEliminar(grupo) {
  if (confirm(`¿Está seguro de eliminar el grupo "${grupo.codigo_grupo}"?`)) {
    router.delete(buildUrl(`/grupos/${grupo.id}`));
  }
}
</script>
