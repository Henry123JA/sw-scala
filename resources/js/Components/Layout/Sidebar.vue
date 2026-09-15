<template>
  <aside
    class="bg-gray-950 text-gray-300 flex flex-col h-full border-r border-gray-800 transition-all duration-250 ease-in-out"
    :class="collapsed ? 'w-16' : 'w-64 overflow-hidden'"
    @mouseleave="collapsed && scheduleCloseFlyout()"
  >
    <!-- Brand / Title -->
    <div class="h-16 flex items-center border-b border-gray-800 shrink-0" :class="collapsed ? 'justify-center px-0' : 'px-6'">
      <h1 v-show="!collapsed" class="text-xl font-bold tracking-wider whitespace-nowrap cursor-default" :style="{ color: 'var(--color-primary)' }">
        ACADEMIA
      </h1>
      <span v-show="!collapsed" class="ml-2 px-2 py-0.5 text-xs bg-gray-800 text-gray-400 rounded-full font-mono whitespace-nowrap cursor-default">
        v1.0
      </span>
      <span v-show="collapsed" class="text-lg font-bold tracking-wider cursor-default" :style="{ color: 'var(--color-primary)' }">
        AM
      </span>
    </div>

    <!-- User Information — clicable → /perfil -->
    <Link
      v-if="auth.user"
      :href="buildUrl('/perfil')"
      class="p-4 border-b border-gray-800 flex items-center shrink-0 hover:bg-gray-900 transition-colors duration-200 cursor-pointer"
      :class="collapsed ? 'justify-center' : 'space-x-3'"
      :title="collapsed ? `${auth.user.nombres} ${auth.user.apellidos} — Ir a perfil` : undefined"
    >
      <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-800 flex items-center justify-center font-bold text-white uppercase shrink-0 cursor-pointer" :style="!auth.user.foto_url ? { backgroundColor: 'var(--color-primary)' } : {}">
        <img
          v-if="auth.user.foto_url"
          :src="auth.user.foto_url"
          :alt="`${auth.user.nombres} ${auth.user.apellidos}`"
          class="w-full h-full object-cover"
          data-testid="sidebar-avatar"
        />
        <span v-else data-testid="sidebar-initials">
          {{ auth.user.nombres[0] }}{{ auth.user.apellidos[0] }}
        </span>
      </div>
      <div v-show="!collapsed" class="min-w-0 flex-1 cursor-pointer">
        <p class="text-sm font-semibold text-white truncate">{{ auth.user.nombres }} {{ auth.user.apellidos }}</p>
        <p class="text-xs text-gray-400 truncate uppercase tracking-wider font-semibold">{{ auth.rol }}</p>
      </div>
    </Link>

    <!-- Menu Items -->
    <nav class="flex-grow overflow-y-auto p-4 space-y-1 sidebar-scroll">
      <div v-for="item in menuItems" :key="item.id" class="space-y-1">
        <!-- If item has submenus (hijos) — with flyout when collapsed -->
        <div v-if="item.hijos && item.hijos.length > 0" class="relative">
          <button
            @click="toggleGroup(item.id)"
            @mouseenter="onGroupEnter(item, $event)"
            :title="collapsed ? item.nombre : undefined"
            class="w-full flex items-center rounded-lg text-sm font-medium hover:bg-gray-900 transition-colors duration-200 cursor-pointer"
            :class="[collapsed ? 'justify-center px-3 py-2.5' : 'justify-between px-4 py-2.5', isGroupActive(item) ? 'text-white' : 'text-gray-400 hover:text-white']"
          >
            <div class="flex items-center" :class="collapsed ? 'justify-center' : 'space-x-3'">
              <component :is="getIcon(item.icono)" class="w-5 h-5 shrink-0" />
              <span v-show="!collapsed" class="sidebar-text whitespace-nowrap">{{ item.nombre }}</span>
            </div>
            <!-- Chevron indicator -->
            <component
              v-show="!collapsed"
              :is="openGroups[item.id] ? Lucide.ChevronDown : Lucide.ChevronRight"
              class="w-4 h-4 transition-transform duration-200 shrink-0"
            />
          </button>

          <!-- Submenu Child Items (expanded mode) -->
          <div
            v-show="!collapsed && openGroups[item.id]"
            class="pl-9 mt-1 space-y-1 border-l border-gray-800 ml-6"
          >
            <Link
              v-for="subItem in item.hijos"
              :key="subItem.id"
              :href="buildUrl(subItem.ruta)"
              class="block px-4 py-2 rounded-lg text-xs font-medium transition-colors duration-200 cursor-pointer"
              :class="isUrlActive(subItem.ruta) ? 'text-white font-bold' : 'text-gray-400 hover:text-white hover:bg-gray-900'"
              :style="isUrlActive(subItem.ruta) ? { color: 'var(--color-primary)' } : {}"
            >
              <div class="flex items-center space-x-2">
                <component :is="getIcon(subItem.icono)" class="w-4 h-4" />
                <span class="sidebar-text">{{ subItem.nombre }}</span>
              </div>
            </Link>
          </div>
        </div>

        <!-- If item is a single route link -->
        <Link
          v-else
          :href="buildUrl(item.ruta)"
          :title="collapsed ? item.nombre : undefined"
          class="flex items-center rounded-lg text-sm font-medium transition-colors duration-200 cursor-pointer"
          :class="[collapsed ? 'justify-center px-3 py-2.5' : 'space-x-3 px-4 py-2.5', isUrlActive(item.ruta) ? 'text-white font-bold' : 'text-gray-400 hover:text-white hover:bg-gray-900']"
          :style="isUrlActive(item.ruta) ? { backgroundColor: 'rgba(255, 255, 255, 0.05)', color: 'var(--color-primary)' } : {}"
        >
          <component :is="getIcon(item.icono)" class="w-5 h-5 shrink-0" />
          <span v-show="!collapsed" class="sidebar-text whitespace-nowrap">{{ item.nombre }}</span>
        </Link>
      </div>
    </nav>

    <!-- Collapse Toggle Button -->
    <div class="border-t border-gray-800 p-3 shrink-0" :class="collapsed ? 'flex justify-center' : ''">
      <button
        @click="toggleCollapse"
        class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-white hover:bg-gray-900 transition-colors duration-200 cursor-pointer mx-auto"
        :title="collapsed ? 'Expandir menú' : 'Colapsar menú'"
      >
        <component :is="Lucide.PanelLeftClose" class="w-5 h-5 transition-transform duration-250" :class="collapsed ? 'rotate-180' : ''" />
      </button>
    </div>

    <!-- ── Flyout for collapsed hover ────────────────────────────────────── -->
    <div
      v-if="collapsed && flyoutOpen && flyoutItem"
      class="fixed z-50 bg-gray-900 border border-gray-700 rounded-lg shadow-xl py-1 min-w-[200px]"
      :style="{ left: '68px', top: flyoutTop + 'px' }"
      @mouseenter="cancelCloseFlyout()"
      @mouseleave="closeFlyout()"
    >
      <div class="px-3 py-1.5 text-xs font-bold text-gray-400 uppercase tracking-wider cursor-default">
        {{ flyoutItem.nombre }}
      </div>
      <Link
        v-for="sub in flyoutItem.hijos"
        :key="sub.id"
        :href="buildUrl(sub.ruta)"
        class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 transition-colors duration-150 cursor-pointer"
      >
        <component :is="getIcon(sub.icono)" class="w-4 h-4 shrink-0" />
        <span>{{ sub.nombre }}</span>
      </Link>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const page = usePage();
const auth = computed(() => page.props.auth);
//const menuItems = computed(() => page.props.menuItems || []);
////desde aqui

const hiddenItems = [
  'Mensualidades',
  'Seguimiento',
  'Reportes',
  'Aulas',
  'Bitácora'
];

const menuItems = computed(() => {
  const items = page.props.menuItems || [];

  return items.filter(item => !hiddenItems.includes(item.nombre));
});

// ─── Collapse state ─────────────────────────────────────────────────────────
const STORAGE_KEY = 'sidebar-collapsed';
const collapsed = ref(localStorage.getItem(STORAGE_KEY) === 'true');

watch(collapsed, (val) => {
  localStorage.setItem(STORAGE_KEY, val);
  if (val) {
    openGroups.value = {};
    closeFlyout();
  }
});

function toggleCollapse() {
  collapsed.value = !collapsed.value;
}

// ─── Flyout (collapsed hover) ──────────────────────────────────────────────
const flyoutItem = ref(null);
const flyoutTop = ref(0);
const flyoutOpen = ref(false);
let closeTimer = null;

function onGroupEnter(item, event) {
  if (!collapsed.value) return;
  cancelCloseFlyout();
  const rect = event.currentTarget.getBoundingClientRect();
  flyoutTop.value = rect.top;
  flyoutItem.value = item;
  flyoutOpen.value = true;
}

function scheduleCloseFlyout() {
  if (closeTimer) clearTimeout(closeTimer);
  closeTimer = setTimeout(() => {
    closeFlyout();
  }, 200);
}

function cancelCloseFlyout() {
  if (closeTimer) {
    clearTimeout(closeTimer);
    closeTimer = null;
  }
}

function closeFlyout() {
  flyoutOpen.value = false;
  flyoutItem.value = null;
}

// ─── Open group tracking ────────────────────────────────────────────────────
const openGroups = ref({});

const toggleGroup = (id) => {
  openGroups.value[id] = !openGroups.value[id];
};

const getIcon = (name) => {
  return Lucide[name] || Lucide.HelpCircle;
};

const isUrlActive = (url) => {
  const fullUrl = buildUrl(url);
  return page.url === fullUrl || page.url.startsWith(fullUrl + '/');
};

const isGroupActive = (item) => {
  if (isUrlActive(item.ruta)) return true;
  if (item.hijos) {
    return item.hijos.some(subItem => isUrlActive(subItem.ruta));
  }
  return false;
};

// Auto-expand active group on mount
onMounted(() => {
  if (!collapsed.value) {
    menuItems.value.forEach(item => {
      if (item.hijos && item.hijos.length > 0 && isGroupActive(item)) {
        openGroups.value[item.id] = true;
      }
    });
  }
});
</script>
