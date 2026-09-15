<template>
  <footer class="app-footer">
    <div>&copy; {{ currentYear }} Academia de Música Web. Todos los derechos reservados.</div>
    <div class="visit-counter">
      <span class="visit-dot"></span>
      <span>Visitas a esta página: <strong>{{ page.props.paginaContador ?? 0 }}</strong></span>
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
  padding: 1rem 1.5rem;
  background-color: var(--bg-header);
  border-top: 1px solid var(--border-light);
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--text-muted);
  flex-shrink: 0;
}

.visit-counter {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  background-color: var(--bg-surface);
  border: 1px solid var(--border-light);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  color: var(--text-muted);
}

.visit-dot {
  width: 0.375rem;
  height: 0.375rem;
  border-radius: 9999px;
  background-color: #22C55E;
}
</style>
