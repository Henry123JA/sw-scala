<template>
  <AppLayout>
    <div class="max-w-2xl space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Editar Alumno</h1>
        <Link :href="buildUrl('/alumnos')" class="text-sm text-gray-500 hover:text-gray-700">
          ← Volver al listado
        </Link>
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
            <label class="block text-sm font-medium text-gray-700">Nueva contraseña</label>
            <input v-model="form.password" type="password" placeholder="Dejar en blanco para no cambiar" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
          </div>
        </div>

        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2 pt-4">Datos del alumno</h2>

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
            <label class="block text-sm font-medium text-gray-700">Sexo</label>
            <select v-model="form.sexo" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
              <option value="">-- Seleccionar --</option>
              <option value="M">Masculino</option>
              <option value="F">Femenino</option>
              <option value="OTRO">Otro</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input v-model="form.telefono" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono alternativo</label>
            <input v-model="form.telefono_alternativo" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Nivel</label>
            <input v-model="form.nivel" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Referido por</label>
            <select v-model="form.referido_por" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
              <option :value="null">-- Ninguno --</option>
              <option v-for="a in alumnos" :key="a.id" :value="a.id">{{ a.nombre }}</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Observaciones</label>
          <textarea v-model="form.observaciones" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <Link :href="buildUrl('/alumnos')" class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50">
            Cancelar
          </Link>
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
  alumnos: { type: Array, default: () => [] },
});

const a = props.usuario?.alumno ?? {};

const form = useForm({
  nombres:               props.usuario?.nombres ?? '',
  apellidos:             props.usuario?.apellidos ?? '',
  ci:                    props.usuario?.ci ?? '',
  email:                 props.usuario?.email ?? '',
  password:              '',
  codigo:                a.codigo ?? '',
  estado:                a.estado ?? 'ACTIVO',
  fecha_nacimiento:      a.fecha_nacimiento ?? '',
  sexo:                  a.sexo ?? '',
  telefono:              a.telefono ?? '',
  telefono_alternativo:  a.telefono_alternativo ?? '',
  nivel:                 a.nivel ?? '',
  referido_por:          a.referido_por ?? null,
  observaciones:         a.observaciones ?? '',
});

function submit() {
  form.put(buildUrl(`/alumnos/${props.usuario.id}`));
}
</script>
