<template>
  <header class="h-16 bg-white border-b border-[#DDEFEA] flex items-center justify-between px-6 z-10 shadow-2xs select-none">
    <!-- Left Section: Page/Module Name -->
    <div class="flex items-center space-x-3">
      <div class="w-2.5 h-2.5 rounded-full bg-[#14B8A6]"></div>
      <h2 class="text-base font-extrabold text-[#1F2937] tracking-tight">
        {{ currentRouteName }}
      </h2>
    </div>

    <!-- Right Section: User Actions -->
    <div class="flex items-center space-x-4">
      <!-- User avatar with link to /perfil -->
      <Link
        v-if="page.props.auth?.user"
        :href="buildUrl('/perfil')"
        class="flex items-center space-x-2.5 p-1 rounded-full hover:bg-[#F0FDF9] transition-colors cursor-pointer"
        :title="`${page.props.auth.user.nombres} ${page.props.auth.user.apellidos} — Ver Perfil`"
      >
        <div
          class="w-8 h-8 rounded-full overflow-hidden bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center font-bold text-xs shrink-0 ring-2 ring-[#D1FAE5]"
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
        <span class="hidden md:inline text-xs font-bold text-gray-800">
          {{ page.props.auth.user.nombres }}
        </span>
      </Link>

      <!-- Log Out Button -->
      <button
        type="button"
        @click="logout"
        class="flex items-center space-x-1.5 px-3 py-1.5 text-xs font-bold text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-150 cursor-pointer"
        title="Cerrar Sesión"
      >
        <component :is="Lucide.LogOut" class="w-3.5 h-3.5" />
        <span class="hidden sm:inline">Salir</span>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const page = usePage();

const routeNames = {
  'dashboard': 'Panel Principal / Dashboard',
  'perfil': 'Mi Perfil',
  'alumnos': 'Gestión de Alumnos',
  'docentes': 'Gestión de Docentes',
  'secretarias': 'Gestión de Secretarias',
  'usuarios': 'Gestión de Usuarios',
  'aulas': 'Salas y Aulas de Música',
  'cursos': 'Oferta de Cursos',
  'grupos': 'Grupos y Clases',
  'horarios': 'Programación de Horarios',
  'inscripciones': 'Inscripciones Académicas',
  'reportes': 'Centro de Reportes',
  'bitacora': 'Bitácora de Auditoría',
};

const currentRouteName = computed(() => {
  const path = page.url.split('?')[0].replace(/^\/+/g, '').split('/')[0];
  return routeNames[path] || path.charAt(0).toUpperCase() + path.slice(1) || 'Dashboard';
});

const logout = () => {
  router.post(buildUrl('/logout'));
};
</script>
