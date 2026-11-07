<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

defineProps({ grupos: Array });
const base = (import.meta.env.VITE_BASE_URL || '').replace(/\/$/, '');
const form = useForm({ grupo_id: '', fecha: new Date().toLocaleDateString('en-CA') });
</script>

<template>
  <Head title="Gestionar asistencia" />
  <AppLayout>
    <div class="max-w-2xl space-y-6">
      <h1 class="text-2xl font-bold">Gestionar asistencia</h1>
      <p class="text-gray-600">Selecciona el grupo y la fecha para abrir o continuar el registro de la sesión.</p>
      <form class="space-y-4 rounded-lg border bg-white p-6" @submit.prevent="form.post(`${base}/asistencias/sesion`)">
        <label for="grupo" class="block font-medium">Grupo</label>
        <select id="grupo" v-model="form.grupo_id" required class="w-full rounded border p-2">
          <option disabled value="">Selecciona un grupo</option>
          <option v-for="grupo in grupos" :key="grupo.id" :value="grupo.id">{{ grupo.codigo_grupo }} — {{ grupo.curso?.nombre }}</option>
        </select>
        <p v-if="!grupos.length" class="text-gray-600">No tienes grupos disponibles.</p>
        <p v-if="form.errors.grupo_id" class="text-sm text-red-600">{{ form.errors.grupo_id }}</p>
        <label for="fecha" class="block font-medium">Fecha de la sesión</label>
        <input id="fecha" v-model="form.fecha" type="date" required class="w-full rounded border p-2" />
        <p v-if="form.errors.fecha" class="text-sm text-red-600">{{ form.errors.fecha }}</p>
        <button :disabled="form.processing || !grupos.length" class="rounded bg-teal-700 px-4 py-2 text-white disabled:opacity-50">Abrir sesión</button>
      </form>
    </div>
  </AppLayout>
</template>
