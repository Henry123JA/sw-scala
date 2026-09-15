<template>
  <AppLayout>
    <div class="max-w-2xl space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Nuevo Docente</h1>
        <Link :href="buildUrl('/docentes')" class="text-sm text-gray-500 hover:text-gray-700">← Volver al listado</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">Datos de acceso</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombres *</label>
            <input v-model="form.nombres" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.nombres" class="mt-1 text-xs text-red-600">{{ form.errors.nombres }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Apellidos *</label>
            <input v-model="form.apellidos" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.apellidos" class="mt-1 text-xs text-red-600">{{ form.errors.apellidos }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">CI *</label>
            <input v-model="form.ci" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.ci" class="mt-1 text-xs text-red-600">{{ form.errors.ci }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Correo electrónico *</label>
            <input v-model="form.email" type="email" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Contraseña *</label>
            <input v-model="form.password" type="password" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
          </div>
        </div>

        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2 pt-4">Datos del docente</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Código</label>
            <input v-model="form.codigo" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.codigo" class="mt-1 text-xs text-red-600">{{ form.errors.codigo }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Estado</label>
            <select v-model="form.estado" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
              <option value="ACTIVO">ACTIVO</option>
              <option value="INACTIVO">INACTIVO</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Fecha de nacimiento</label>
            <input v-model="form.fecha_nacimiento" type="date" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.fecha_nacimiento" class="mt-1 text-xs text-red-600">{{ form.errors.fecha_nacimiento }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Fecha de incorporación</label>
            <input v-model="form.fecha_incorporacion" type="date" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input v-model="form.telefono" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Tarifa horaria (Bs)</label>
            <input v-model="form.tarifa_horaria" type="number" step="0.01" min="0" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.tarifa_horaria" class="mt-1 text-xs text-red-600">{{ form.errors.tarifa_horaria }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Especialidades</label>
          <div class="flex flex-wrap gap-2">
            <label
              v-for="esp in especialidades"
              :key="esp.id"
              class="flex items-center gap-1.5 rounded-md border px-2 py-1 text-sm cursor-pointer"
              :class="form.especialidades.includes(esp.id) ? 'bg-purple-50 border-purple-400 text-purple-700' : 'bg-white border-gray-200'"
            >
              <input type="checkbox" :value="esp.id" v-model="form.especialidades" class="h-3.5 w-3.5" />
              {{ esp.nombre }}
            </label>
          </div>
          <p v-if="form.errors.especialidades" class="mt-1 text-xs text-red-600">{{ form.errors.especialidades }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Observaciones</label>
          <textarea v-model="form.observaciones" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <Link :href="buildUrl('/docentes')" class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50">Cancelar</Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            Guardar docente
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

defineProps({
  especialidades: { type: Array, default: () => [] },
});

const form = useForm({
  nombres: '',
  apellidos: '',
  ci: '',
  email: '',
  password: '',
  codigo: '',
  estado: 'ACTIVO',
  fecha_nacimiento: '',
  fecha_incorporacion: '',
  telefono: '',
  tarifa_horaria: '',
  observaciones: '',
  especialidades: [],
});

function submit() {
  form.post(buildUrl('/docentes'));
}
</script>
