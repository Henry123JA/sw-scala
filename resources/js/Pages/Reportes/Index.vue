<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    dashboard: Object,
    grupos: Array,
});
</script>

<template>
    <Head title="Reportes Académicos - Scala Musical" />
    <div class="min-h-screen bg-slate-50 p-6 md:p-10">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Centro de Reportes Académicos</h1>
                    <p class="text-sm text-slate-500">Métricas institucionales, sábanas de notas y boletines individuales</p>
                </div>
            </div>

            <!-- KPIs Globales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-[#DDEFEA] shadow-sm">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Inscripciones</span>
                    <p class="text-3xl font-extrabold text-slate-800 mt-2">{{ dashboard?.total_inscripciones ?? 0 }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-[#DDEFEA] shadow-sm">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Promedio Global</span>
                    <p class="text-3xl font-extrabold text-[#0F766E] mt-2">{{ Number(dashboard?.promedio_general ?? 0).toFixed(1) }} / 100</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-[#DDEFEA] shadow-sm">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Asistencia General</span>
                    <p class="text-3xl font-extrabold text-teal-600 mt-2">{{ dashboard?.porcentaje_asistencia_global ?? 0 }}%</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-[#DDEFEA] shadow-sm">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Alumnos en Riesgo</span>
                    <p class="text-3xl font-extrabold text-rose-600 mt-2">{{ dashboard?.semaforos?.['EN RIESGO'] ?? 0 }}</p>
                </div>
            </div>

            <!-- Listado de Grupos -->
            <div class="bg-white rounded-2xl border border-[#DDEFEA] shadow-sm p-6">
                <h2 class="text-lg font-bold text-slate-800 mb-4">Sábanas de Notas por Grupo</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-[#F0FDF9] text-[#0F766E] uppercase text-xs font-bold">
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
                                <td class="py-3 px-4 text-center flex items-center justify-center gap-2">
                                    <Link :href="route('reportes.sabana.grupo', grp.id)" class="px-3 py-1.5 rounded-lg bg-[#14B8A6] hover:bg-[#0D9488] text-white text-xs font-medium transition">
                                        Ver Sábana
                                    </Link>
                                    <a :href="route('reportes.sabana.grupo', { grupo: grp.id, export: 'csv' })" class="px-3 py-1.5 rounded-lg border border-[#14B8A6] text-[#0F766E] hover:bg-teal-50 text-xs font-medium transition">
                                        CSV
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
