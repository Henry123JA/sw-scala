<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-2xl font-bold mb-2" :style="{ color: 'var(--color-primary)' }">Reportes y Estadísticas</h1>
        <p class="text-gray-500">Seleccione el tipo de informe o estadística que desea visualizar.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Reporte de Negocio -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition flex flex-col justify-between">
          <div>
            <div class="h-12 w-12 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 mb-4">
              <!-- Dollar/Trending Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">Reportes Financieros y de Negocio</h2>
            <p class="text-gray-500 text-sm mb-4">
              Visualice los ingresos mensuales, estado de mensualidades (pagadas, pendientes, atrasadas) e inscripciones activas por curso.
            </p>
          </div>
          <Link
            :href="buildUrl('/reportes/negocio')"
            class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition w-full"
            title="Ver Reportes de Negocio"
          >
            <component :is="Lucide.Eye" class="w-4 h-4 shrink-0" />
            Reportes de Negocio
          </Link>
        </div>

        <!-- Reporte de Acceso (Bitácora) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition flex flex-col justify-between">
          <div>
            <div class="h-12 w-12 rounded-lg bg-green-50 flex items-center justify-center text-green-600 mb-4">
              <!-- Lock/Shield Icon -->
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
            :href="buildUrl('/reportes/acceso')"
            class="inline-flex items-center justify-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-green-700 transition w-full"
            title="Ver Bitácora de Acceso"
          >
            <component :is="Lucide.Eye" class="w-4 h-4 shrink-0" />
            Bitácora de Acceso
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};
</script>
