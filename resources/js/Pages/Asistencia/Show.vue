<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  sesion: Object,
  alumnos: Array,
});

const page = usePage();
const base = (import.meta.env.VITE_BASE_URL || '').replace(/\/$/, '');

const estados = {
  PRESENTE: { label: 'Presente', icon: 'CheckCircle2', color: 'text-emerald-700 bg-emerald-50 border-emerald-300' },
  FALTA: { label: 'Falta', icon: 'XCircle', color: 'text-rose-700 bg-rose-50 border-rose-300' },
  LICENCIA: { label: 'Licencia', icon: 'FileText', color: 'text-sky-700 bg-sky-50 border-sky-300' },
  ATRASO: { label: 'Atraso', icon: 'Clock', color: 'text-amber-700 bg-amber-50 border-amber-300' },
};

function getInscripcionBadge(estado) {
  switch (estado) {
    case 'ACTIVA':
      return {
        label: 'Activa',
        badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        icon: null,
      };
    case 'PAUSADA':
      return {
        label: 'Inscripción Pausada',
        badgeClass: 'bg-amber-100 text-amber-800 border-amber-300 font-bold',
        icon: 'AlertTriangle',
        alertMsg: 'Inscripción en pausa académica. Verifique la situación del alumno antes de registrar su asistencia.',
        alertClass: 'bg-amber-50 text-amber-800 border-amber-200',
      };
    case 'CANCELADA':
      return {
        label: 'Inscripción Cancelada',
        badgeClass: 'bg-rose-100 text-rose-800 border-rose-300 font-bold',
        icon: 'AlertCircle',
        alertMsg: 'Inscripción cancelada / alumno retirado del grupo.',
        alertClass: 'bg-rose-50 text-rose-700 border-rose-200',
      };
    default:
      return {
        label: estado || 'Inactiva',
        badgeClass: 'bg-gray-100 text-gray-700 border-gray-300 font-medium',
        icon: 'AlertCircle',
        alertMsg: `Inscripción en estado ${estado || 'inactiva'}.`,
        alertClass: 'bg-gray-50 text-gray-700 border-gray-200',
      };
  }
}

const form = useForm({
  asistencias: props.alumnos.map(alumno => ({
    inscripcion_id: alumno.inscripcion_id,
    estado: alumno.estado || 'PRESENTE',
    observacion: alumno.observacion || '',
  })),
});

function marcarTodos(estado) {
  form.asistencias.forEach(fila => {
    fila.estado = estado;
  });
}
</script>

<template>
  <Head title="Tomar Asistencia" />
  <AppLayout>
    <div class="space-y-6 max-w-5xl">
      <!-- Botón volver y encabezado -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <Link
            :href="`${base}/asistencias`"
            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#0F766E] hover:underline mb-2"
          >
            <component :is="Lucide.ArrowLeft" class="w-3.5 h-3.5" />
            Volver a Grupos
          </Link>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
              Planilla de Asistencia
            </h1>
            <span class="badge-menta font-mono text-xs">
              {{ sesion.grupo?.codigo_grupo }}
            </span>
          </div>
          <p class="text-sm text-gray-500 mt-1 flex items-center gap-2">
            <span class="font-semibold text-gray-700">{{ sesion.grupo?.curso?.nombre }}</span>
            <span>•</span>
            <span class="font-mono text-gray-600 flex items-center gap-1">
              <component :is="Lucide.Calendar" class="w-3.5 h-3.5 text-[#14B8A6]" />
              {{ sesion.fecha ? sesion.fecha.slice(0, 10) : '' }}
            </span>
          </p>
        </div>

        <!-- Botones de marcado rápido -->
        <div v-if="alumnos.length > 0" class="flex items-center gap-2">
          <button
            type="button"
            class="btn-menta-outline text-xs py-1.5 px-3"
            :disabled="form.processing"
            @click="marcarTodos('PRESENTE')"
          >
            <component :is="Lucide.CheckCheck" class="w-3.5 h-3.5 text-emerald-600" />
            Todos Presentes
          </button>
        </div>
      </div>

      <!-- Alertas Flash -->
      <div
        v-if="page.props.flash?.success || form.recentlySuccessful"
        class="card-menta p-4 bg-emerald-50/70 border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2 animate-in fade-in"
      >
        <component :is="Lucide.CheckCircle" class="w-5 h-5 text-emerald-600 shrink-0" />
        {{ page.props.flash?.success || 'Asistencia guardada correctamente.' }}
      </div>

      <!-- Estado sin alumnos -->
      <div v-if="!alumnos.length" class="card-menta p-10 text-center text-gray-500">
        <component :is="Lucide.Users" class="w-10 h-10 mx-auto text-gray-300 mb-2" />
        <p class="font-semibold text-gray-700">Este grupo no tiene estudiantes inscritos actualmente.</p>
        <p class="text-xs text-gray-400 mt-1">Inscribe alumnos en el grupo para poder registrar su asistencia.</p>
      </div>

      <!-- Formulario de toma de asistencia -->
      <form v-else class="space-y-4" @submit.prevent="form.put(`${base}/asistencias/${sesion.id}`, { preserveScroll: true })">
        <div class="card-menta overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="table-header-menta">
                <tr>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider w-1/3">Estudiante</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider w-1/3">Estado</th>
                  <th class="p-3.5 text-xs font-bold uppercase tracking-wider">Observación</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#DDEFEA]">
                <tr
                  v-for="(alumno, index) in alumnos"
                  :key="alumno.inscripcion_id"
                  class="transition-colors"
                  :class="[
                    alumno.estado_inscripcion === 'PAUSADA' ? 'bg-amber-50/40 hover:bg-amber-50/60' :
                    alumno.estado_inscripcion === 'CANCELADA' ? 'bg-rose-50/40 hover:bg-rose-50/60' :
                    alumno.estado_inscripcion !== 'ACTIVA' ? 'bg-gray-50/60 hover:bg-gray-100/60' :
                    'hover:bg-[#F0FDF9]'
                  ]"
                >
                  <!-- Columna Estudiante -->
                  <td class="p-3.5 align-top">
                    <div class="font-bold text-gray-900">{{ alumno.nombre }}</div>

                    <!-- Fila de metadatos y badge de inscripción -->
                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                      <span class="text-[11px] text-gray-400 font-mono font-medium">
                        #{{ alumno.inscripcion_id }}
                      </span>

                      <!-- Badge según estado de la inscripción -->
                      <span
                        v-if="alumno.estado_inscripcion === 'ACTIVA'"
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                      >
                        Activa
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold border shadow-xs"
                        :class="getInscripcionBadge(alumno.estado_inscripcion).badgeClass"
                      >
                        <component
                          :is="Lucide[getInscripcionBadge(alumno.estado_inscripcion).icon] || Lucide.AlertTriangle"
                          class="w-3 h-3 shrink-0"
                        />
                        {{ getInscripcionBadge(alumno.estado_inscripcion).label }}
                      </span>
                    </div>

                    <!-- Alerta visual destacada si la inscripción NO está activa (ej: PAUSADA o CANCELADA) -->
                    <div
                      v-if="alumno.estado_inscripcion && alumno.estado_inscripcion !== 'ACTIVA'"
                      class="mt-2 flex items-start gap-1.5 p-2 rounded-lg text-xs font-medium border"
                      :class="getInscripcionBadge(alumno.estado_inscripcion).alertClass"
                    >
                      <component
                        :is="Lucide[getInscripcionBadge(alumno.estado_inscripcion).icon] || Lucide.AlertTriangle"
                        class="w-3.5 h-3.5 shrink-0 mt-0.5"
                      />
                      <span>{{ getInscripcionBadge(alumno.estado_inscripcion).alertMsg }}</span>
                    </div>

                    <p v-if="form.errors[`asistencias.${index}.inscripcion_id`]" class="text-xs text-rose-600 mt-1">
                      {{ form.errors[`asistencias.${index}.inscripcion_id`] }}
                    </p>
                  </td>

                  <!-- Columna Estado -->
                  <td class="p-3.5">
                    <div class="flex flex-wrap gap-1.5 sm:gap-2">
                      <label
                        v-for="(conf, key) in estados"
                        :key="key"
                        class="cursor-pointer text-xs font-semibold px-2.5 py-1.5 rounded-lg border transition-all flex items-center gap-1 select-none"
                        :class="form.asistencias[index].estado === key ? conf.color + ' shadow-xs ring-1 ring-offset-1 ring-current' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                      >
                        <input
                          v-model="form.asistencias[index].estado"
                          type="radio"
                          :name="`estado_${alumno.inscripcion_id}`"
                          :value="key"
                          class="sr-only"
                          :disabled="form.processing"
                        />
                        <span class="capitalize">{{ conf.label }}</span>
                      </label>
                    </div>
                    <p v-if="form.errors[`asistencias.${index}.estado`]" class="text-xs text-rose-600 mt-1">
                      {{ form.errors[`asistencias.${index}.estado`] }}
                    </p>
                  </td>

                  <!-- Columna Observación -->
                  <td class="p-3.5">
                    <input
                      v-model="form.asistencias[index].observacion"
                      type="text"
                      placeholder="Observación opcional..."
                      maxlength="500"
                      class="input-menta text-xs py-1.5"
                      :disabled="form.processing"
                    />
                    <p v-if="form.errors[`asistencias.${index}.observacion`]" class="text-xs text-rose-600 mt-1">
                      {{ form.errors[`asistencias.${index}.observacion`] }}
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <p v-if="form.errors.asistencias" role="alert" class="text-xs text-rose-600">
          {{ form.errors.asistencias }}
        </p>

        <!-- Barra inferior de guardado -->
        <div class="flex items-center justify-between pt-2">
          <span class="text-xs text-gray-500">
            Total inscritos: <strong>{{ alumnos.length }}</strong>
          </span>
          <button
            type="submit"
            :disabled="form.processing"
            class="btn-menta px-6 py-2.5 text-sm shadow-md"
          >
            <component :is="Lucide.Save" class="w-4 h-4" />
            {{ form.processing ? 'Guardando asistencia…' : 'Guardar Asistencia' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
