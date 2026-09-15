<template>
  <AppLayout>
    <div class="max-w-lg space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Editar Secretaria</h1>
        <Link :href="buildUrl('/secretarias')" class="text-sm text-gray-500 hover:text-gray-700">← Volver al listado</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
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
          <label class="block text-sm font-medium text-gray-700">Nueva contraseña</label>
          <input v-model="form.password" type="password" placeholder="Dejar en blanco para no cambiar" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <Link :href="buildUrl('/secretarias')" class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50">Cancelar</Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            Guardar cambios
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
  usuario: Object,
});

const form = useForm({
  nombres:   props.usuario?.nombres ?? '',
  apellidos: props.usuario?.apellidos ?? '',
  ci:        props.usuario?.ci ?? '',
  email:     props.usuario?.email ?? '',
  password:  '',
});

function submit() {
  form.put(buildUrl(`/secretarias/${props.usuario.id}`));
}
</script>
