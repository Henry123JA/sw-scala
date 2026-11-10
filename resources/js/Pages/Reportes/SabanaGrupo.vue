<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
  grupo: {
    type: Object,
    default: () => ({}),
  },
  docente: {
    type: Object,
    default: () => ({}),
  },
  evaluaciones: {
    type: Array,
    default: () => [],
  },
  estudiantes: {
    type: Array,
    default: () => [],
  },
});

const obtenerColorBadge = (semaforo) => {
  switch (semaforo) {
    case 'EXCELENTE':
      return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    case 'FAVORABLE':
      return 'bg-teal-100 text-teal-800 border-teal-200';
    case 'REGULAR':
      return 'bg-amber-100 text-amber-800 border-amber-200';
    case 'EN RIESGO':
      return 'bg-rose-100 text-rose-800 border-rose-200';
    default:
      return 'bg-slate-100 text-slate-700 border-slate-200';
  }
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Encabezado con navegación directa -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-[#DDEFEA] shadow-sm">
        <div>
          <Link href="/reportes-academicos" class="text-xs text-[#0F766E] hover:underline font-semibold flex items-center gap-1 mb-2">
            ← Volver a Reportes Académicos
          </Link>
          <h1 class="text-2xl font-bold text-slate-800">
  Consolidado de Calificaciones: {{ grupo?.codigo_grupo ?? 'Grupo' }}
</h1>
          <p class="text-sm text-slate-500">
            Curso: <strong class="text-slate-700">{{ grupo?.curso?.nombre ?? 'Sin curso' }}</strong> | 
            Docente: <strong class="text-slate-700">{{ docente?.usuario?.nombres ?? 'Docente titular' }}</strong>
          </p>
        </div>

        <div class="flex items-center gap-3">
            <a
  :href="`/reportes-academicos/grupos/${grupo?.id}/sabana?export=csv`"
  class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#14B8A6] hover:bg-[#0D9488] text-white text-sm font-semibold shadow-sm transition"
>
  Exportar Reporte (CSV)
</a>
        </div>
      </div>

      <!-- Tabla Sábana de Notas -->
      <div class="bg-white rounded-2xl border border-[#DDEFEA] shadow-sm p-6 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-[#F0FDF9] text-[#0F766E] uppercase text-xs font-bold border-b border-[#DDEFEA]">
              <tr>
                <th class="py-3 px-4">Estudiante</th>
                <th class="py-3 px-4">CI</th>
                <th v-for="ev in evaluaciones" :key="ev.id" class="py-3 px-3 text-center">
                  {{ ev.nombre }} ({{ ev.peso_porcentaje }}%)
                </th>
                <th class="py-3 px-3 text-center">Promedio</th>
                <th class="py-3 px-3 text-center">% Asistencia</th>
                <th class="py-3 px-3 text-center">Semáforo</th>
                <th class="py-3 px-4 text-center">Boletín</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="est in estudiantes" :key="est.inscripcion_id" class="hover:bg-slate-50 transition">
                <td class="py-3 px-4 font-semibold text-slate-800">
                  {{ est.alumno?.nombre_completo || 'Estudiante' }}
                </td>
                <td class="py-3 px-4 text-xs font-mono text-slate-500">
                  {{ est.alumno?.ci || 'S/N' }}
                </td>
                <td v-for="ev in evaluaciones" :key="ev.id" class="py-3 px-3 text-center font-mono">
                  {{ est.calificaciones?.[ev.id] !== null && est.calificaciones?.[ev.id] !== undefined ? est.calificaciones[ev.id] : '-' }}
                </td>
                <td class="py-3 px-3 text-center font-bold text-slate-800">
                  {{ Number(est.resumen_progreso?.promedio ?? 0).toFixed(1) }}
                </td>
                <td class="py-3 px-3 text-center font-semibold text-teal-700">
                  {{ est.resumen_progreso?.porcentaje_asistencia ?? 0 }}%
                </td>
                <td class="py-3 px-3 text-center">
                  <span
                    class="px-2.5 py-1 text-xs font-semibold rounded-full border inline-block"
                    :class="obtenerColorBadge(est.resumen_progreso?.semaforo)"
                  >
                    {{ est.resumen_progreso?.semaforo ?? 'SIN DATOS' }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
                  <Link
                    :href="`/reportes-academicos/inscripciones/${est.inscripcion_id}/boletin`"
                    class="px-3 py-1 bg-slate-100 hover:bg-[#F0FDF9] text-slate-700 hover:text-[#0F766E] rounded-lg text-xs font-medium border border-slate-200 transition"
                  >
                    Boletín
                  </Link>
                </td>
              </tr>
              <tr v-if="!estudiantes || estudiantes.length === 0">
                <td :colspan="6 + (evaluaciones?.length || 0)" class="py-8 text-center text-slate-400">
                  No hay estudiantes registrados en este grupo.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>