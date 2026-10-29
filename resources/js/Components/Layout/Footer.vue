<template>
  <footer class="app-footer">
    <div class="footer-copy">
      &copy; {{ currentYear }} <strong>Scala Musical</strong> — Academia de Música. Todos los derechos reservados.
    </div>
    <div class="visit-counter">
      <span class="visit-dot"></span>
      <span>Visitas a esta página: <strong class="text-[#0F766E]">{{ page.props.paginaContador ?? 0 }}</strong></span>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const currentYear = computed(() => new Date().getFullYear());
</script>

<style scoped>
.app-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1.5rem;
  background-color: #FFFFFF;
  border-top: 1px solid #DDEFEA;
  font-size: 0.75rem;
  color: #6B7280;
  flex-shrink: 0;
  user-select: none;
}

.visit-counter {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background-color: #F0FDF9;
  border: 1px solid #DDEFEA;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  color: #1F2937;
  font-weight: 500;
}

.visit-dot {
  width: 0.4rem;
  height: 0.4rem;
  border-radius: 9999px;
  background-color: #14B8A6;
  box-shadow: 0 0 4px rgba(20, 184, 166, 0.6);
}
</style>
