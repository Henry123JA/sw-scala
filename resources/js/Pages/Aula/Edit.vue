<template>
  <AppLayout>
    <div class="max-w-2xl space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Editar Aula</h1>
        <Link :href="buildUrl('/aulas')" class="text-sm text-gray-500 hover:text-gray-700">
          ← Volver al listado
        </Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre *</label>
            <input v-model="form.nombre" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required />
            <p v-if="form.errors.nombre" class="mt-1 text-xs text-red-600">{{ form.errors.nombre }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Tipo (Teoría, Práctica, Auditorio, etc.)</label>
            <input v-model="form.tipo" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.tipo" class="mt-1 text-xs text-red-600">{{ form.errors.tipo }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Capacidad *</label>
            <input v-model.number="form.capacidad" type="number" min="1" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required />
            <p v-if="form.errors.capacidad" class="mt-1 text-xs text-red-600">{{ form.errors.capacidad }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Ubicación / Piso</label>
            <input v-model="form.ubicacion_piso" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.ubicacion_piso" class="mt-1 text-xs text-red-600">{{ form.errors.ubicacion_piso }}</p>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Equipamiento</label>
            <input v-model="form.equipamiento" type="text" placeholder="Ej. Piano vertical, 15 sillas, pizarra acrílica" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.equipamiento" class="mt-1 text-xs text-red-600">{{ form.errors.equipamiento }}</p>
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
          <Link :href="buildUrl('/aulas')" class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50">
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            Actualizar Aula
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
  aula: Object,
});

const form = useForm({
  nombre: props.aula.nombre,
  tipo: props.aula.tipo ?? '',
  capacidad: props.aula.capacidad,
  ubicacion_piso: props.aula.ubicacion_piso ?? '',
  equipamiento: props.aula.equipamiento ?? '',
  estado: props.aula.estado,
});

function submit() {
  form.put(buildUrl(`/aulas/${props.aula.id}`));
}
</script>
