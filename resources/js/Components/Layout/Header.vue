<template>
  <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 z-10 shadow-sm">
    <!-- Left Section: Page/Module Name -->
    <div class="flex items-center space-x-4">
      <h2 class="text-lg font-semibold text-gray-800 capitalize">
        {{ currentRouteName }}
      </h2>
    </div>

    <!-- Right Section: Theme Selector & User Actions -->
    <div class="flex items-center space-x-6">
      <!-- ─── Theme Selection Dropdown (comentado, disponible para futura activación) ───
      <div class="flex items-center space-x-2">
        <label for="theme-selector" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
          Tema:
        </label>
        <select
          id="theme-selector"
          :value="currentThemeId"
          @change="handleThemeChange"
          class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1.5 focus:outline-none transition-colors duration-200 cursor-pointer"
        >
          <option v-for="t in themes" :key="t.id" :value="t.id">
            {{ t.descripcion }}
          </option>
        </select>
      </div>
      ──────────────────────────────────────────────────────────────────── -->

      <!-- User avatar (with initials fallback) -->
      <div
        v-if="page.props.auth?.user"
        class="w-8 h-8 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center font-bold text-gray-700 text-sm shrink-0"
        :title="`${page.props.auth.user.nombres} ${page.props.auth.user.apellidos}`"
      >
        <img
          v-if="page.props.auth.user.foto_url"
          :src="page.props.auth.user.foto_url"
          :alt="page.props.auth.user.nombres"
          class="w-full h-full object-cover"
          data-testid="header-avatar"
        />
        <span v-else data-testid="header-initials">
          {{ page.props.auth.user.nombres[0] }}{{ page.props.auth.user.apellidos[0] }}
        </span>
      </div>

      <!-- Log Out Button -->
      <button
        @click="logout"
        class="flex items-center space-x-1.5 px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-200 cursor-pointer"
      >
        <component :is="Lucide.LogOut" class="w-4 h-4" />
        <span>Cerrar Sesión</span>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';
import * as Lucide from '@lucide/vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const page = usePage();
const { saveTheme } = useTheme();

const themes = computed(() => {
  return page.props.temasDisponibles || [];
});

const currentThemeId = computed(() => {
  const activeName = page.props.temaActual || 'adultos';
  const found = themes.value.find(t => t.nombre === activeName);
  return found ? found.id : themes.value[0]?.id ?? null;
});

const routeNames = {
  'dashboard': 'Dashboard',
  'perfil': 'Mi Perfil',
  'alumnos': 'Alumnos',
  'docentes': 'Docentes',
  'secretarias': 'Secretarias',
  'usuarios': 'Usuarios',
  'aulas': 'Aulas',
  'cursos': 'Cursos',
  'grupos': 'Grupos',
  'horarios': 'Horarios',
  'inscripciones': 'Inscripciones',
  'mensualidades': 'Mensualidades',
  'seguimiento': 'Seguimiento',
  'reportes': 'Reportes',
  'bitacora': 'Bitácora de Eventos',
};

const currentRouteName = computed(() => {
  const path = page.url.split('?')[0].replace(/^\/+/g, '').split('/')[0];
  return routeNames[path] || path.charAt(0).toUpperCase() + path.slice(1) || 'Dashboard';
});

const handleThemeChange = (event) => {
  const selectedId = parseInt(event.target.value);
  saveTheme(selectedId);
};

const logout = () => {
  router.post(buildUrl('/logout'));
};
</script>
