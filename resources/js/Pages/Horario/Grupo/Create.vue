<template>
  <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm max-w-lg mx-auto">
    <h2 class="text-xl font-bold mb-4" :style="{ color: 'var(--color-primary)' }">
      {{ isEdit ? 'Editar Horario de Grupo' : 'Registrar Horario de Grupo' }}
    </h2>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Grupo</label>
        <select
          v-model="form.grupo_id"
          class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          :disabled="isEdit"
        >
          <option value="">Seleccione un grupo</option>
          <option v-for="g in grupos" :key="g.id" :value="g.id">
            {{ g.codigo_grupo }} ({{ g.curso }}) - Docente: {{ g.docente }}
          </option>
        </select>
        <span v-if="form.errors.grupo_id" class="text-xs text-red-600 mt-1 block">
          {{ form.errors.grupo_id }}
        </span>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Sala (Aula)</label>
        <select
          v-model="form.sala_id"
          class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
        >
          <option value="">Seleccione una sala</option>
          <option v-for="s in salas" :key="s.id" :value="s.id">
            {{ s.nombre }}
          </option>
        </select>
        <span v-if="form.errors.sala_id" class="text-xs text-red-600 mt-1 block">
          {{ form.errors.sala_id }}
        </span>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Día de la Semana</label>
        <select
          v-model="form.dia_semana"
          class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
        >
          <option value="">Seleccione un día</option>
          <option value="Lunes">Lunes</option>
          <option value="Martes">Martes</option>
          <option value="Miércoles">Miércoles</option>
          <option value="Jueves">Jueves</option>
          <option value="Viernes">Viernes</option>
          <option value="Sábado">Sábado</option>
          <option value="Domingo">Domingo</option>
        </select>
        <span v-if="form.errors.dia_semana" class="text-xs text-red-600 mt-1 block">
          {{ form.errors.dia_semana }}
        </span>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Hora Inicio</label>
          <input
            v-model="form.hora_inicio"
            type="time"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          />
          <span v-if="form.errors.hora_inicio" class="text-xs text-red-600 mt-1 block">
            {{ form.errors.hora_inicio }}
          </span>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Hora Fin</label>
          <input
            v-model="form.hora_fin"
            type="time"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
          />
          <span v-if="form.errors.hora_fin" class="text-xs text-red-600 mt-1 block">
            {{ form.errors.hora_fin }}
          </span>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Tipo de Sesión (Opcional)</label>
        <input
          v-model="form.tipo_sesion"
          type="text"
          placeholder="Ej: Teórica, Práctica, Ensayo"
          class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
        />
        <span v-if="form.errors.tipo_sesion" class="text-xs text-red-600 mt-1 block">
          {{ form.errors.tipo_sesion }}
        </span>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button
          type="button"
          @click="$emit('cancel')"
          class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition disabled:opacity-50"
        >
          {{ isEdit ? 'Guardar Cambios' : 'Registrar' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const props = defineProps({
  grupos: Array,
  salas: Array,
  horario: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['success', 'cancel']);

const isEdit = !!props.horario;

const form = useForm({
  grupo_id: props.horario ? props.horario.grupo_id : '',
  sala_id: props.horario ? props.horario.sala_id : '',
  dia_semana: props.horario ? props.horario.dia_semana : '',
  hora_inicio: props.horario ? formatTime(props.horario.hora_inicio) : '08:00',
  hora_fin: props.horario ? formatTime(props.horario.hora_fin) : '09:00',
  tipo_sesion: props.horario ? props.horario.tipo_sesion : '',
});

function formatTime(timeStr) {
  if (!timeStr) return '';
  return timeStr.substring(0, 5); // converts HH:MM:SS to HH:MM
}

function submit() {
  if (isEdit) {
    form.put(buildUrl(`/horarios/grupos/${props.horario.id}`), {
      onSuccess: () => emit('success'),
    });
  } else {
    form.post(buildUrl('/horarios/grupos'), {
      onSuccess: () => emit('success'),
    });
  }
}
</script>
