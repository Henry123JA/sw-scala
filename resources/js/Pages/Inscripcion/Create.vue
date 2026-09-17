<template>
  <AppLayout>
    <div class="max-w-2xl space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Nueva Inscripción</h1>
        <Link :href="buildUrl('/inscripciones')" class="text-sm text-gray-500 hover:text-gray-700">
          ← Volver al listado
        </Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Alumno *</label>
            <select v-model="form.alumno_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
              <option value="" disabled>Seleccione un alumno</option>
              <option v-for="a in alumnos" :key="a.id" :value="a.id">
                {{ a.usuario ? `${a.usuario.nombres} ${a.usuario.apellidos}` : a.codigo }}
              </option>
            </select>
            <p v-if="form.errors.alumno_id" class="mt-1 text-xs text-red-600">{{ form.errors.alumno_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Grupo / Curso *</label>
            <select v-model="form.grupo_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
              <option value="" disabled>Seleccione un grupo</option>
              <option v-for="g in grupos" :key="g.id" :value="g.id">
                {{ g.codigo_grupo }} - {{ g.curso?.nombre }}
              </option>
            </select>
            <p v-if="form.errors.grupo_id" class="mt-1 text-xs text-red-600">{{ form.errors.grupo_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Fecha de Inscripción</label>
            <input v-model="form.fecha" type="date" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.fecha" class="mt-1 text-xs text-red-600">{{ form.errors.fecha }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Fecha de Inicio de Clases *</label>
            <input v-model="form.fecha_inicio_clases" type="date" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required />
            <p v-if="form.errors.fecha_inicio_clases" class="mt-1 text-xs text-red-600">{{ form.errors.fecha_inicio_clases }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Observaciones</label>
          <textarea v-model="form.observaciones" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></textarea>
          <p v-if="form.errors.observaciones" class="mt-1 text-xs text-red-600">{{ form.errors.observaciones }}</p>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <Link :href="buildUrl('/inscripciones')" class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50">
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            Guardar Inscripción
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const props = defineProps({
  alumnos: Array,
  grupos: Array,
});

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  alumno_id: '',
  grupo_id: '',
  fecha: today,
  fecha_inicio_clases: '',
  observaciones: '',
});

function submit() {
  form.post(buildUrl('/inscripciones'));
}
</script>
