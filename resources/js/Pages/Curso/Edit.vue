<template>
  <AppLayout>
    <div class="max-w-2xl space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Editar Curso</h1>
        <Link :href="buildUrl('/cursos')" class="text-sm text-gray-500 hover:text-gray-700">
          ← Volver al listado
        </Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Nombre *</label>
            <input v-model="form.nombre" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required />
            <p v-if="form.errors.nombre" class="mt-1 text-xs text-red-600">{{ form.errors.nombre }}</p>
          </div>

          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Descripción</label>
            <textarea v-model="form.descripcion" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></textarea>
            <p v-if="form.errors.descripcion" class="mt-1 text-xs text-red-600">{{ form.errors.descripcion }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Tipo de Enseñanza</label>
            <input v-model="form.tipo_ensenanza" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.tipo_ensenanza" class="mt-1 text-xs text-red-600">{{ form.errors.tipo_ensenanza }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Duración Estándar</label>
            <input v-model="form.duracion_estandar" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.duracion_estandar" class="mt-1 text-xs text-red-600">{{ form.errors.duracion_estandar }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Precio (Bs) *</label>
            <input v-model.number="form.precio" type="number" step="0.01" min="0" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required />
            <p v-if="form.errors.precio" class="mt-1 text-xs text-red-600">{{ form.errors.precio }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Estado</label>
            <select v-model="form.estado" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
              <option value="ACTIVO">ACTIVO</option>
              <option value="INACTIVO">INACTIVO</option>
            </select>
            <p v-if="form.errors.estado" class="mt-1 text-xs text-red-600">{{ form.errors.estado }}</p>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <Link :href="buildUrl('/cursos')" class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50">
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            Actualizar Curso
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
  curso: Object,
});

const form = useForm({
  nombre: props.curso.nombre,
  descripcion: props.curso.descripcion || '',
  tipo_ensenanza: props.curso.tipo_ensenanza || '',
  duracion_estandar: props.curso.duracion_estandar || '',
  precio: Number(props.curso.precio),
  estado: props.curso.estado,
});

function submit() {
  form.put(buildUrl(`/cursos/${props.curso.id}`));
}
</script>
