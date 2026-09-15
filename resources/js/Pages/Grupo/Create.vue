<template>
  <AppLayout>
    <div class="max-w-2xl space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Nuevo Grupo</h1>
        <Link :href="buildUrl('/grupos')" class="text-sm text-gray-500 hover:text-gray-700">
          ← Volver al listado
        </Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Código de Grupo *</label>
            <input v-model="form.codigo_grupo" type="text" placeholder="Ej. G-PIANO-01" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required />
            <p v-if="form.errors.codigo_grupo" class="mt-1 text-xs text-red-600">{{ form.errors.codigo_grupo }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Curso *</label>
            <select v-model="form.curso_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
              <option value="" disabled>Seleccione un curso</option>
              <option v-for="c in cursos" :key="c.id" :value="c.id">
                {{ c.nombre }}
              </option>
            </select>
            <p v-if="form.errors.curso_id" class="mt-1 text-xs text-red-600">{{ form.errors.curso_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Docente *</label>
            <select v-model="form.docente_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
              <option value="" disabled>Seleccione un docente</option>
              <option v-for="d in docentes" :key="d.id" :value="d.id">
                {{ d.usuario ? `${d.usuario.nombres} ${d.usuario.apellidos}` : d.codigo }}
              </option>
            </select>
            <p v-if="form.errors.docente_id" class="mt-1 text-xs text-red-600">{{ form.errors.docente_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Capacidad Máxima *</label>
            <input v-model.number="form.capacidad_maxima" type="number" min="1" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required />
            <p v-if="form.errors.capacidad_maxima" class="mt-1 text-xs text-red-600">{{ form.errors.capacidad_maxima }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Nivel (ej. Básico, Intermedio, Avanzado)</label>
            <input v-model="form.nivel" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.nivel" class="mt-1 text-xs text-red-600">{{ form.errors.nivel }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Estado</label>
            <select v-model="form.estado" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
              <option value="ACTIVO">ACTIVO</option>
              <option value="INACTIVO">INACTIVO</option>
              <option value="CERRADO">CERRADO</option>
              <option value="CANCELADO">CANCELADO</option>
            </select>
            <p v-if="form.errors.estado" class="mt-1 text-xs text-red-600">{{ form.errors.estado }}</p>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <Link :href="buildUrl('/grupos')" class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50">
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            Guardar Grupo
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
  cursos: Array,
  docentes: Array,
});

const form = useForm({
  codigo_grupo: '',
  curso_id: '',
  docente_id: '',
  capacidad_maxima: 15,
  nivel: '',
  estado: 'ACTIVO',
});

function submit() {
  form.post(buildUrl('/grupos'));
}
</script>
