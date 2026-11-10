@'
<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

defineProps({
  dashboard: {
    type: Object,
    default: () => ({}),
  },
  grupos: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Encabezado con navegación -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-[#DDEFEA] shadow-sm">
        <div>
          <Link href="/reportes" class="text-xs text-[#0F766E] hover:underline font-semibold flex items-center gap-1 mb-2">
            ← Volver al Menú Principal de Reportes
          </Link>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-800">Centro de Reportes Académicos</h1>
            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-[#CCFBF1] text-[#0F766E] border border-[#99F6E4]">
              HU14
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Métricas institucionales, sábanas de notas grupales y boletines individuales.
          </p>
        </div>
      </div>

      <!-- Tarjetas KPIs -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-[#DDEFEA] shadow-sm">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Inscripciones</span>
          <p class="text-3xl font-extrabold text-slate-800 mt-2">{{ dashboard?.total_inscripciones ?? 0 }}</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-[#DDEFEA] shadow-sm">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Promedio General</span>
          <p class="text-3xl font-extrabold text-[#0F766E] mt-2">{{ Number(dashboard?.promedio_general ?? 0).toFixed(1) }} / 100</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-[#DDEFEA] shadow-sm">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Asistencia Global</span>
          <p class="text-3xl font-extrabold text-[#14B8A6] mt-2">{{ dashboard?.porcentaje_asistencia_global ?? 0 }}%</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-[#DDEFEA] shadow-sm">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Alumnos en Riesgo</span>
          <p class="text-3xl font-extrabold text-rose-600 mt-2">{{ dashboard?.semaforos?.['EN RIESGO'] ?? 0 }}</p>
        </div>
      </div>

      <!-- Tabla de Grupos -->
      <div class="bg-white rounded-2xl border border-[#DDEFEA] shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-800">Consolidado General de Calificaciones por Grupo</h2>
          <span class="text-xs text-slate-400 font-medium">{{ grupos?.length ?? 0 }} grupos registrados</span>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-[#F0FDF9] text-[#0F766E] uppercase text-xs font-bold border-b border-[#DDEFEA]">
              <tr>
                <th class="py-3 px-4">Código</th>
                <th class="py-3 px-4">Curso</th>
                <th class="py-3 px-4">Docente</th>
                <th class="py-3 px-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="grp in grupos" :key="grp.id" class="hover:bg-slate-50 transition">
                <td class="py-3 px-4 font-semibold text-slate-800">{{ grp.codigo_grupo }}</td>
                <td class="py-3 px-4">{{ grp.curso?.nombre ?? 'Sin curso' }}</td>
                <td class="py-3 px-4">{{ grp.docente?.usuario?.nombres ?? 'Docente asignado' }}</td>
                <td class="py-3 px-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <Link
                      :href="`/reportes-academicos/grupos/${grp.id}/sabana`"
                      class="px-3 py-1.5 rounded-lg bg-[#14B8A6] hover:bg-[#0D9488] text-white text-xs font-medium transition shadow-sm"
                    >
                      Ver calificaciones 
                    </Link>
                    <a
                      :href="`/reportes-academicos/grupos/${grp.id}/sabana?export=csv`"
                      class="px-3 py-1.5 rounded-lg border border-[#14B8A6] text-[#0F766E] hover:bg-[#F0FDF9] text-xs font-medium transition"
                    >
                      CSV
                    </a>
                  </div>
                </td>
              </tr>
              <tr v-if="!grupos || grupos.length === 0">
                <td colspan="4" class="py-8 text-center text-slate-400">
                  No hay grupos registrados en este momento.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
'@ | Set-Content -Path "resources/js/Pages/Reportes/AcademicosIndex.vue" -Encoding UTF8