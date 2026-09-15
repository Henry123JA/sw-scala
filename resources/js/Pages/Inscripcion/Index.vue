<template>
  <AppLayout>
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Gestión de Inscripciones</h1>
        <Link
          v-if="canEdit"
          :href="buildUrl('/inscripciones/crear')"
          class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition"
        >
          + Nueva Inscripción
        </Link>
      </div>

      <!-- Filters & Search -->
      <div class="flex flex-col sm:flex-row gap-4 items-center">
        <input
          v-model="searchTerm"
          type="text"
          placeholder="Buscar por alumno o curso..."
          class="w-full sm:max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          @input="onSearch"
        />
        <select
          v-model="selectedEstado"
          class="w-full sm:max-w-xs rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          @change="onSearch"
        >
          <option value="">Todos los estados</option>
          <option value="ACTIVA">Activa</option>
          <option value="PAUSADA">Pausada</option>
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
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Alumno</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Grupo / Curso</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha Inscripción</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Inicio de Clases</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Monto Mensual</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Estado</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="ins in inscripciones.data" :key="ins.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">
                {{ ins.alumno?.usuario ? `${ins.alumno.usuario.nombres} ${ins.alumno.usuario.apellidos}` : 'N/A' }}
              </td>
              <td class="px-4 py-3 text-gray-600">
                <span class="font-semibold text-gray-800">{{ ins.grupo?.codigo_grupo }}</span>
                <span class="block text-xs text-gray-500">{{ ins.grupo?.curso?.nombre }}</span>
              </td>
              <td class="px-4 py-3 text-gray-600">
                {{ ins.fecha ? new Date(ins.fecha).toLocaleDateString('es-ES') : '-' }}
              </td>
              <td class="px-4 py-3 text-gray-600">
                {{ ins.fecha_inicio_clases ? new Date(ins.fecha_inicio_clases).toLocaleDateString('es-ES') : '-' }}
              </td>
              <td class="px-4 py-3 text-gray-600 font-semibold">
                {{ Number(ins.monto_mensual).toFixed(2) }} Bs
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="ins.estado === 'ACTIVA' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                >
                  {{ ins.estado }}
                </span>
              </td>
              <td v-if="canEdit" class="px-4 py-3">
                <div class="flex items-center">
                  <Link
                    :href="buildUrl(`/inscripciones/${ins.id}`)"
                    class="rounded px-2 py-1 text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 cursor-pointer"
                    title="Ver detalle"
                  >
                    <component :is="Lucide.Eye" class="w-4 h-4 shrink-0" />
                  </Link>
                  <Link
                    v-if="canEdit"
                    :href="buildUrl(`/inscripciones/${ins.id}/editar`)"
                    class="rounded px-2 py-1 text-xs bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 cursor-pointer"
                    title="Editar"
                  >
                    <component :is="Lucide.Pencil" class="w-4 h-4 shrink-0" />
                  </Link>
                  <button
                    v-if="canDelete"
                    @click="confirmarEliminar(ins)"
                    class="rounded px-2 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 cursor-pointer"
                    title="Eliminar"
                  >
                    <component :is="Lucide.Trash2" class="w-4 h-4 shrink-0" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="inscripciones.data.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                No se encontraron inscripciones.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="inscripciones.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
        <span>Página {{ inscripciones.current_page }} de {{ inscripciones.last_page }}</span>
        <div class="flex gap-2">
          <Link
            v-slot="{ href }"
            v-if="inscripciones.prev_page_url"
            :href="inscripciones.prev_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="{ buscar: searchTerm, estado: selectedEstado }"
          >
            Anterior
          </Link>
          <Link
            v-slot="{ href }"
            v-if="inscripciones.next_page_url"
            :href="inscripciones.next_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="{ buscar: searchTerm, estado: selectedEstado }"
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
  inscripciones: Object,
  buscar: { type: String, default: '' },
  estado: { type: String, default: '' },
});

const page = usePage();
const canEdit = computed(() => ['Propietario', 'Secretaria'].includes(page.props.auth?.rol));
const canDelete = computed(() => page.props.auth?.rol === 'Propietario');

const searchTerm = ref(props.buscar);
const selectedEstado = ref(props.estado);

let debounceTimer = null;
function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    router.get(buildUrl('/inscripciones'), {
      buscar: searchTerm.value,
      estado: selectedEstado.value,
    }, {
      preserveState: true,
      replace: true,
    });
  }, 300);
}

function confirmarEliminar(ins) {
  const nombre = ins.alumno?.usuario ? `${ins.alumno.usuario.nombres} ${ins.alumno.usuario.apellidos}` : 'Inscripción';
  if (confirm(`¿Está seguro de eliminar la inscripción de "${nombre}" en el grupo "${ins.grupo?.codigo_grupo}"?`)) {
    router.delete(buildUrl(`/inscripciones/${ins.id}`));
  }
}
</script>
