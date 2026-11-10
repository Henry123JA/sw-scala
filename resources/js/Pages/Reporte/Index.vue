<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-2xl font-bold mb-2" :style="{ color: 'var(--color-primary)' }">Reportes y Estadísticas</h1>
        <p class="text-gray-500">Seleccione el tipo de informe o estadística que desea visualizar.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Reporte de Acceso (Bitácora) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition flex flex-col justify-between">
          <div>
            <div class="h-12 w-12 rounded-lg bg-green-50 flex items-center justify-center text-green-600 mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">Bitácora de Accesos y Seguridad</h2>
            <p class="text-gray-500 text-sm mb-4">
              Consulte el registro histórico de acciones de los usuarios en el sistema, con filtros avanzados por usuario, acción, estado y rango de fechas.
            </p>
          </div>
          <Link
            :href="route('reportes.acceso')"
            class="inline-flex items-center justify-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-green-700 transition w-full"
            title="Ver Bitácora de Acceso"
          >
            <component :is="Lucide.Eye" class="w-4 h-4 shrink-0" />
            Bitácora de Acceso
          </Link>
        </div>

        <!-- HU14: Reportes Académicos -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-[#DDEFEA] hover:shadow-md transition flex flex-col justify-between">
          <div>
            <div class="h-12 w-12 rounded-lg bg-[#F0FDF9] flex items-center justify-center text-[#0F766E] mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5" />
              </svg>
            </div>
            <h2 class="text-lg font-bold text-slate-800 mb-2">Reportes Académicos</h2>
            <p class="text-slate-500 text-sm mb-4">
              Consulte el rendimiento académico institucional: KPIs de notas, sábanas de calificaciones por grupo, descargas CSV y boletines oficiales.
            </p>
          </div>
          <Link
            href="/reportes-academicos"
            class="inline-flex items-center justify-center gap-2 rounded-md bg-[#14B8A6] px-4 py-2 text-sm font-medium text-white shadow hover:bg-[#0D9488] transition w-full"
            title="Ver Reportes Académicos"
          >
            <component :is="Lucide.FileSpreadsheet" class="w-4 h-4 shrink-0" />
            Centro de Reportes Académicos
          </Link>
        </div>

      </div>
    </div>
  </AppLayout>
</template>