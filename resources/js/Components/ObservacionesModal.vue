<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity animate-in fade-in">
    <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-xl border border-[#DDEFEA]">
      <!-- Header -->
      <div class="flex items-center justify-between pb-4 border-b border-[#DDEFEA]">
        <div class="flex items-center gap-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#D1FAE5] text-[#0F766E]">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900">Nueva Observación Académica</h3>
            <p class="text-xs text-gray-500">Bitácora pedagógica y seguimiento del estudiante</p>
          </div>
        </div>
        <button
          type="button"
          @click="$emit('close')"
          class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="mt-4 space-y-4">
        <!-- Tipo & Fecha Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
              Categoría <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.tipo"
              required
              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#14B8A6] focus:ring-1 focus:ring-[#14B8A6]"
            >
              <option value="PROGRESO_TECNICO">Progreso Técnico</option>
              <option value="RECOMENDACION">Recomendación</option>
              <option value="DISCIPLINA">Disciplina</option>
              <option value="FELICITACION">Felicitación</option>
            </select>
            <p v-if="form.errors.tipo" class="mt-1 text-xs text-red-600">{{ form.errors.tipo }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
              Fecha <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.fecha"
              type="date"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-[#14B8A6] focus:ring-1 focus:ring-[#14B8A6]"
            />
            <p v-if="form.errors.fecha" class="mt-1 text-xs text-red-600">{{ form.errors.fecha }}</p>
          </div>
        </div>

        <!-- Título -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
            Título <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.titulo"
            type="text"
            maxlength="150"
            required
            placeholder="Ej. Avance notable en digitación de escalas"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-[#14B8A6] focus:ring-1 focus:ring-[#14B8A6]"
          />
          <p v-if="form.errors.titulo" class="mt-1 text-xs text-red-600">{{ form.errors.titulo }}</p>
        </div>

        <!-- Descripción -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
            Descripción Detallada <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.descripcion"
            rows="4"
            required
            placeholder="Describe la observación, recomendaciones prácticas o aspectos a reforzar..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-[#14B8A6] focus:ring-1 focus:ring-[#14B8A6]"
          ></textarea>
          <p v-if="form.errors.descripcion" class="mt-1 text-xs text-red-600">{{ form.errors.descripcion }}</p>
        </div>

        <!-- Visible para el alumno -->
        <div class="rounded-lg bg-[#F8FFFD] border border-[#DDEFEA] p-3 flex items-start gap-3">
          <input
            id="visible_check"
            v-model="form.visible_para_alumno"
            type="checkbox"
            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#14B8A6] focus:ring-[#14B8A6]"
          />
          <label for="visible_check" class="text-xs text-gray-700 cursor-pointer select-none">
            <span class="font-semibold text-gray-900 block">Visible para el Alumno</span>
            Permitir que el estudiante visualice esta observación en su portal personal de seguimiento.
          </label>
        </div>

        <!-- General Error -->
        <p v-if="form.errors.general" class="text-xs text-red-600 bg-red-50 p-2 rounded border border-red-200">
          {{ form.errors.general }}
        </p>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#DDEFEA]">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-lg bg-[#14B8A6] hover:bg-[#0D9488] shadow-xs disabled:opacity-50 transition cursor-pointer"
          >
            <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ form.processing ? 'Guardando...' : 'Guardar Observación' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  inscripcionId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['close', 'created']);

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  inscripcion_id: props.inscripcionId,
  fecha: today,
  tipo: 'PROGRESO_TECNICO',
  titulo: '',
  descripcion: '',
  visible_para_alumno: true,
});

watch(
  () => props.inscripcionId,
  (newId) => {
    form.inscripcion_id = newId;
  }
);

function submit() {
  form.post(buildUrl('/observaciones'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('titulo', 'descripcion');
      form.fecha = new Date().toISOString().split('T')[0];
      form.tipo = 'PROGRESO_TECNICO';
      form.visible_para_alumno = true;
      emit('created');
      emit('close');
    },
  });
}
</script>
