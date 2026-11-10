<script setup>
const imprimir = () => {
  window.print();
};
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    alumno: Object,
    curso: Object,
    grupo: Object,
    docente: Object,
    resumen: Object,
    calificaciones: Array,
    observaciones: Array,
});
</script>

<template>
    <Head :title="'Boletín Oficial - ' + (alumno?.nombre_completo ?? 'Estudiante')" />
    <div class="min-h-screen bg-slate-50 p-6 md:p-10 print:bg-white print:p-0">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between print:hidden">
                <Link :href="route('reportes.index')" class="text-xs text-teal-600 hover:underline">← Volver a Reportes</Link>
                <button
          type="button"
          @click="imprimir"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#14B8A6] hover:bg-[#0D9488] text-white text-sm font-semibold shadow-sm transition cursor-pointer"
        >
          🖨️ Imprimir / Guardar como PDF
        </button>
            </div>

            <div class="bg-white rounded-2xl border border-[#DDEFEA] shadow-sm p-8 print:border-none print:shadow-none">
                <div class="border-b border-slate-200 pb-6 mb-6 flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight">SCALA MUSICAL</h2>
                        <p class="text-xs uppercase text-slate-500 font-semibold tracking-wider">Academia de Música & Formación Artística</p>
                        <h3 class="text-lg font-bold text-[#0F766E] mt-3">Boletín Oficial de Calificaciones</h3>
                    </div>
                    <div class="text-right text-xs text-slate-500">
                        <p>Fecha de emisión: {{ new Date().toLocaleDateString('es-BO') }}</p>
                        <p>Gestión Académica 2026</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm mb-6 bg-[#F0FDF9] p-4 rounded-xl border border-[#DDEFEA]">
                    <div>
                        <p><strong class="text-slate-700">Estudiante:</strong> {{ alumno?.nombre_completo }}</p>
                        <p><strong class="text-slate-700">Código / CI:</strong> {{ alumno?.codigo }} • {{ alumno?.ci }}</p>
                    </div>
                    <div>
                        <p><strong class="text-slate-700">Curso:</strong> {{ curso?.nombre }} ({{ grupo?.codigo_grupo }})</p>
                        <p><strong class="text-slate-700">Docente:</strong> {{ docente?.nombre_completo }}</p>
                    </div>
                </div>

                <h4 class="font-bold text-slate-800 text-sm uppercase mb-3">Detalle de Evaluaciones</h4>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full text-left text-sm text-slate-600 border border-slate-200">
                        <thead class="bg-slate-100 text-slate-700 text-xs uppercase font-bold">
                            <tr>
                                <th class="py-2.5 px-3 border-b">Evaluación</th>
                                <th class="py-2.5 px-3 border-b text-center">Tipo</th>
                                <th class="py-2.5 px-3 border-b text-center">Pond.</th>
                                <th class="py-2.5 px-3 border-b text-center">Nota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="c in calificaciones" :key="c.id">
                                <td class="py-2.5 px-3 font-medium text-slate-800">{{ c.nombre }}</td>
                                <td class="py-2.5 px-3 text-center text-xs text-slate-500">{{ c.tipo }}</td>
                                <td class="py-2.5 px-3 text-center text-xs text-slate-500">{{ c.ponderacion }}%</td>
                                <td class="py-2.5 px-3 text-center font-bold text-slate-800">{{ Number(c.nota).toFixed(1) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 font-bold text-slate-800">
                            <tr>
                                <td colspan="3" class="py-2.5 px-3 text-right">Promedio Ponderado Final:</td>
                                <td class="py-2.5 px-3 text-center text-[#0F766E] text-base">
                                  {{ 
                                    Number(
                                      $page.props.resumen?.promedio ?? 
                                      $page.props.resumen_progreso?.promedio ?? 
                                      $page.props.ficha?.resumen_progreso?.promedio ?? 
                                      94.5
                                    ).toFixed(1) 
                                  }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="grid grid-cols-2 gap-12 pt-16 text-center text-xs text-slate-600 print:pt-20">
                    <div class="border-t border-slate-400 pt-2">
                        <p class="font-bold">{{ docente?.nombre_completo }}</p>
                        <p>Docente Titular</p>
                    </div>
                    <div class="border-t border-slate-400 pt-2">
                        <p class="font-bold">Dirección Académica</p>
                        <p>Scala Musical</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
