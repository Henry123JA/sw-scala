<template>
  <aside
    class="bg-white text-gray-700 flex flex-col h-full border-r border-[#DDEFEA] transition-all duration-250 ease-in-out shadow-sm select-none"
    :class="collapsed ? 'w-16' : 'w-64 overflow-hidden'"
    @mouseleave="collapsed && scheduleCloseFlyout()"
  >
    <!-- Brand / Title -->
    <div
      class="h-16 flex items-center border-b border-[#DDEFEA] shrink-0"
      :class="collapsed ? 'justify-center px-0' : 'px-5 justify-between'"
    >
      <div v-show="!collapsed" class="flex items-center space-x-2.5 overflow-hidden">
        <div class="w-8 h-8 rounded-lg bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0 shadow-xs">
          <component :is="Lucide.Music" class="w-4 h-4" />
        </div>
        <div class="flex flex-col min-w-0">
          <span class="text-sm font-extrabold tracking-wide text-gray-900 truncate">
            SCALA MUSICAL
          </span>
          <span class="text-[10px] uppercase font-semibold tracking-wider text-[#0F766E]">
            Academia Web
          </span>
        </div>
      </div>

      <span
        v-show="!collapsed"
        class="px-2 py-0.5 text-[10px] font-bold bg-[#D1FAE5] text-[#0F766E] rounded-full font-mono shrink-0"
      >
        v1.0
      </span>

      <div
        v-show="collapsed"
        class="w-8 h-8 rounded-lg bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0 shadow-xs cursor-default"
        title="Scala Musical"
      >
        <component :is="Lucide.Music" class="w-4 h-4" />
      </div>
    </div>

    <!-- User Information — Clicable hacia /perfil -->
    <Link
      v-if="auth.user"
      :href="buildUrl('/perfil')"
      class="p-3.5 border-b border-[#DDEFEA] flex items-center shrink-0 hover:bg-[#F0FDF9] transition-colors duration-150 cursor-pointer"
      :class="collapsed ? 'justify-center' : 'space-x-3'"
      :title="collapsed ? `${auth.user.nombres} ${auth.user.apellidos} — Ver Perfil` : undefined"
    >
      <div
        class="w-9 h-9 rounded-full overflow-hidden flex items-center justify-center font-bold text-white text-xs uppercase shrink-0 shadow-xs cursor-pointer ring-2 ring-[#D1FAE5]"
        :style="!auth.user.foto_url ? { backgroundColor: '#14B8A6' } : {}"
      >
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
      <div v-show="!collapsed" class="min-w-0 flex-1">
        <p class="text-xs font-bold text-gray-900 truncate">{{ auth.user.nombres }} {{ auth.user.apellidos }}</p>
        <p class="text-[10px] font-semibold text-[#0F766E] uppercase tracking-wider truncate">{{ auth.rol }}</p>
      </div>
    </Link>

    <!-- Navigation Items -->
    <nav class="flex-grow overflow-y-auto p-3 space-y-1 sidebar-scroll">
      <div v-for="item in menuItems" :key="item.label" class="space-y-1">
        <!-- If item has children submenus -->
        <div v-if="item.children && item.children.length > 0" class="relative">
          <button
            type="button"
            @click="toggleGroup(item.label)"
            @mouseenter="onGroupEnter(item, $event)"
            :title="collapsed ? item.label : undefined"
            class="w-full flex items-center rounded-lg text-xs font-semibold transition-all duration-150 cursor-pointer"
            :class="[
              collapsed ? 'justify-center p-2.5' : 'justify-between px-3 py-2.5',
              isGroupActive(item)
                ? 'bg-[#D1FAE5] text-[#0F766E]'
                : 'text-gray-600 hover:text-[#0F766E] hover:bg-[#F0FDF9]'
            ]"
          >
            <div class="flex items-center" :class="collapsed ? 'justify-center' : 'space-x-2.5'">
              <component
                :is="getIcon(item.icon)"
                class="w-4 h-4 shrink-0 transition-colors duration-150"
                :class="isGroupActive(item) ? 'text-[#0F766E]' : 'text-gray-500'"
              />
              <span v-show="!collapsed" class="whitespace-nowrap">{{ item.label }}</span>
            </div>
            <!-- Chevron indicator -->
            <component
              v-show="!collapsed"
              :is="openGroups[item.label] ? Lucide.ChevronDown : Lucide.ChevronRight"
              class="w-3.5 h-3.5 shrink-0 transition-transform duration-200"
              :class="isGroupActive(item) ? 'text-[#0F766E]' : 'text-gray-400'"
            />
          </button>

          <!-- Submenu Child Items (Expanded mode) -->
          <div
            v-show="!collapsed && openGroups[item.label]"
            class="pl-6 mt-1 space-y-1 border-l-2 border-[#DDEFEA] ml-4"
          >
            <Link
              v-for="subItem in item.children"
              :key="subItem.route"
              :href="buildUrl(subItem.path)"
              class="flex items-center space-x-2 px-3 py-2 rounded-md text-xs font-medium transition-colors duration-150 cursor-pointer"
              :class="isUrlActive(subItem.path)
                ? 'bg-[#E6FBF2] text-[#0F766E] font-bold shadow-2xs'
                : 'text-gray-600 hover:text-[#0F766E] hover:bg-[#F0FDF9]'"
            >
              <component
                :is="getIcon(subItem.icon)"
                class="w-3.5 h-3.5 shrink-0"
                :class="isUrlActive(subItem.path) ? 'text-[#0F766E]' : 'text-gray-400'"
              />
              <span class="truncate">{{ subItem.label }}</span>
            </Link>
          </div>
        </div>

        <!-- If item is a single route link -->
        <Link
          v-else
          :href="buildUrl(item.path)"
          :title="collapsed ? item.label : undefined"
          class="flex items-center rounded-lg text-xs font-semibold transition-all duration-150 cursor-pointer"
          :class="[
            collapsed ? 'justify-center p-2.5' : 'space-x-2.5 px-3 py-2.5',
            isUrlActive(item.path)
              ? 'bg-[#D1FAE5] text-[#0F766E] font-bold shadow-2xs'
              : 'text-gray-600 hover:text-[#0F766E] hover:bg-[#F0FDF9]'
          ]"
        >
          <component
            :is="getIcon(item.icon)"
            class="w-4 h-4 shrink-0"
            :class="isUrlActive(item.path) ? 'text-[#0F766E]' : 'text-gray-500'"
          />
          <span v-show="!collapsed" class="whitespace-nowrap">{{ item.label }}</span>
        </Link>
      </div>
    </nav>

    <!-- Collapse Toggle Button -->
    <div class="border-t border-[#DDEFEA] p-2.5 shrink-0" :class="collapsed ? 'flex justify-center' : ''">
      <button
        type="button"
        @click="toggleCollapse"
        class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-[#0F766E] hover:bg-[#F0FDF9] transition-colors duration-150 cursor-pointer mx-auto"
        :title="collapsed ? 'Expandir menú' : 'Colapsar menú'"
      >
        <component
          :is="Lucide.PanelLeftClose"
          class="w-4 h-4 transition-transform duration-250"
          :class="collapsed ? 'rotate-180' : ''"
        />
      </button>
    </div>

    <!-- ── Flyout for collapsed hover ────────────────────────────────────── -->
    <div
      v-if="collapsed && flyoutOpen && flyoutItem"
      class="fixed z-50 bg-white border border-[#DDEFEA] rounded-xl shadow-xl py-1.5 min-w-[200px]"
      :style="{ left: '68px', top: flyoutTop + 'px' }"
      @mouseenter="cancelCloseFlyout()"
      @mouseleave="closeFlyout()"
    >
      <div class="px-3.5 py-1.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-[#DDEFEA]">
        {{ flyoutItem.label }}
      </div>
      <div class="p-1 space-y-0.5">
        <Link
          v-for="sub in flyoutItem.children"
          :key="sub.route"
          :href="buildUrl(sub.path)"
          class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors duration-150 cursor-pointer"
          :class="isUrlActive(sub.path)
            ? 'bg-[#D1FAE5] text-[#0F766E] font-bold'
            : 'text-gray-700 hover:text-[#0F766E] hover:bg-[#F0FDF9]'"
        >
          <component :is="getIcon(sub.icon)" class="w-3.5 h-3.5 shrink-0" />
          <span>{{ sub.label }}</span>
        </Link>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';
import { getNavForRole } from '@/config/navigation';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const page = usePage();
const auth = computed(() => page.props.auth || {});

// ─── Opciones de menú obtenidas desde la configuración estática navigation.js ───
const menuItems = computed(() => {
  const rol = auth.value?.rol || '';
  return getNavForRole(rol);
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

const toggleGroup = (key) => {
  openGroups.value[key] = !openGroups.value[key];
};

const getIcon = (name) => {
  return Lucide[name] || Lucide.HelpCircle;
};

const isUrlActive = (url) => {
  if (!url) return false;
  const fullUrl = buildUrl(url);
  return page.url === fullUrl || page.url.startsWith(fullUrl + '/') || page.url.startsWith(fullUrl + '?');
};

const isGroupActive = (item) => {
  if (item.path && isUrlActive(item.path)) return true;
  if (item.children) {
    return item.children.some(subItem => isUrlActive(subItem.path));
  }
  return false;
};

// Auto-expand active group on mount
onMounted(() => {
  if (!collapsed.value) {
    menuItems.value.forEach(item => {
      if (item.children && item.children.length > 0 && isGroupActive(item)) {
        openGroups.value[item.label] = true;
      }
    });
  }
});
</script>
