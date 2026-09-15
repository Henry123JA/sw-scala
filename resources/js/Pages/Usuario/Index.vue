<template>
  <AppLayout>
    <div class="space-y-4">
      <!-- Header & Create Actions -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Gestión de Usuarios</h1>
          <p class="text-xs text-gray-500">Listado general y administración de cuentas de usuario del sistema.</p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link
            v-if="canManage"
            :href="buildUrl('/alumnos/crear')"
            class="rounded bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 shadow-sm transition cursor-pointer"
            title="Nuevo Alumno"
          >
            <component :is="Lucide.Plus" class="w-5 h-5" />
          </Link>
          <Link
            v-if="canManage"
            :href="buildUrl('/docentes/crear')"
            class="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm transition cursor-pointer"
            title="Nuevo Docente"
          >
            <component :is="Lucide.Plus" class="w-5 h-5" />
          </Link>
          <Link
            v-if="isPropietario"
            :href="buildUrl('/secretarias/crear')"
            class="rounded bg-emerald-600 p-2 text-xs font-semibold text-white hover:bg-emerald-700 shadow-sm transition cursor-pointer"
            title="Nueva Secretaria"
          >
            <component :is="Lucide.Plus" class="w-5 h-5" />
          </Link>
        </div>
      </div>

      <!-- Filters & Search -->
      <div class="flex flex-col sm:flex-row gap-4 items-center">
        <input
          v-model="searchTerm"
          type="text"
          placeholder="Buscar por nombre, CI, email o rol..."
          class="w-full sm:max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          @input="onSearch"
        />
      </div>

      <!-- Table -->
      <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">CI</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Rol</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Estado</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="u in usuarios.data" :key="u.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">
                {{ u.nombres }} {{ u.apellidos }}
              </td>
              <td class="px-4 py-3 text-gray-600">
                {{ u.email }}
              </td>
              <td class="px-4 py-3 text-gray-600 font-mono">
                {{ u.ci }}
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex rounded bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-700">
                  {{ u.rol?.nombre }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="u.estado === 'ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                >
                  {{ u.estado || 'ACTIVO' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <Link
                  v-if="canEdit(u)"
                  :href="getEditRoute(u)"
                  class="text-blue-600 hover:text-blue-800 font-semibold text-xs cursor-pointer"
                  title="Editar"
                >
                  <component :is="Lucide.Pencil" class="w-4 h-4 shrink-0" />
                </Link>
                <button
                  v-if="isPropietario"
                  @click="confirmBaja(u)"
                  class="rounded px-2 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 cursor-pointer"
                  title="Dar de baja"
                >
                  <component :is="Lucide.UserX" class="w-4 h-4 shrink-0" />
                </button>
              </td>
            </tr>
            <tr v-if="usuarios.data.length === 0">
              <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                No se encontraron usuarios.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="usuarios.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
        <span>Página {{ usuarios.current_page }} de {{ usuarios.last_page }}</span>
        <div class="flex gap-2">
          <Link
            v-if="usuarios.prev_page_url"
            :href="usuarios.prev_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="{ buscar: buscar }"
          >
            Anterior
          </Link>
          <Link
            v-if="usuarios.next_page_url"
            :href="usuarios.next_page_url"
            class="rounded border px-3 py-1 hover:bg-gray-50"
            :data="{ buscar: buscar }"
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
  usuarios: Object,
  buscar: String,
});

const page = usePage();
const userRole = computed(() => page.props.auth?.rol);
const isPropietario = computed(() => userRole.value === 'Propietario');
const canManage = computed(() => ['Propietario', 'Secretaria'].includes(userRole.value));

const searchTerm = ref(props.buscar || '');

let debounceTimer = null;
function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    router.get(buildUrl('/usuarios'), {
      buscar: searchTerm.value,
    }, {
      preserveState: true,
      replace: true,
    });
  }, 300);
}

function canEdit(u) {
  if (userRole.value === 'Secretaria') {
    // Secretaria cannot edit Propietarios or Secretarias
    return ['Alumno', 'Docente'].includes(u.rol?.nombre);
  }
  return isPropietario.value;
}

function canDelete(u) {
  if (page.props.auth?.user?.id === u.id) {
    return false; // Cannot delete oneself
  }
  if (userRole.value === 'Secretaria') {
    // Secretaria can only delete Alumnos
    return u.rol?.nombre === 'Alumno';
  }
  return isPropietario.value;
}

function getEditRoute(u) {
  if (u.rol?.nombre === 'Alumno') {
    return buildUrl(`/alumnos/${u.id}/editar`);
  }
  if (u.rol?.nombre === 'Docente') {
    return buildUrl(`/docentes/${u.id}/editar`);
  }
  if (u.rol?.nombre === 'Secretaria') {
    return buildUrl(`/secretarias/${u.id}/editar`);
  }
  return '#';
}

function confirmBaja(u) {
  if (confirm(`¿Está seguro de que desea dar de baja al usuario ${u.nombres} ${u.apellidos}?`)) {
    router.delete(buildUrl(`/usuarios/${u.id}`));
  }
}
</script>
