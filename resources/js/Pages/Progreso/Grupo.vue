<template>
  <DetalleGrupo v-bind="$props" />
</template>

<script setup>
import DetalleGrupo from './DetalleGrupo.vue';

defineProps({
  grupo: {
    type: Object,
    required: true,
  },
  metricas: {
    type: Object,
    default: () => ({}),
  },
  alumnos: {
    type: Array,
    default: () => [],
  },
});
</script>

