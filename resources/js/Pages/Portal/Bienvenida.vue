<template>
  <Head title="Bienvenido(a) a SCALA - Proceso Completado" />

  <div class="min-h-screen bg-[#F8FFFD] text-[#1F2937] font-sans flex flex-col">
    <!-- Cabecera Institucional -->
    <header class="bg-[#0F766E] text-white shadow-sm">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
        <Link :href="buildUrl('/')" class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-white text-[#0F766E] flex items-center justify-center shadow-xs">
            <component :is="Lucide.Music" class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[10px] uppercase tracking-widest text-teal-200 font-semibold">
              Academia de Música y Arte
            </p>
            <h1 class="text-lg font-serif font-black tracking-wide leading-none">
              SCALA · Portal Académico
            </h1>
          </div>
        </Link>

        <span class="px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-300/40 text-xs font-bold text-teal-100">
          Habilitación Completada
        </span>
      </div>
    </header>

    <!-- Indicador de 3 Pasos Completados -->
    <div class="bg-white border-b border-[#DDEFEA]">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 sm:gap-4 flex-wrap">
          <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700">
            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[11px]">✓</span>
            Política de Privacidad
          </span>
          <component :is="Lucide.ChevronRight" class="w-4 h-4 text-gray-300" />
          <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700">
            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[11px]">✓</span>
            Términos y Condiciones
          </span>
          <component :is="Lucide.ChevronRight" class="w-4 h-4 text-gray-300" />
          <span class="inline-flex items-center gap-1.5 font-extrabold text-[#0F766E]">
            <span class="w-5 h-5 rounded-full bg-[#0F766E] text-white flex items-center justify-center text-[11px]">3</span>
            Mensaje de Bienvenida
          </span>
        </div>
      </div>
    </div>

    <!-- Tarjeta Central de Mensaje de Bienvenida (Figura 7.14) -->
    <main class="max-w-3xl w-full mx-auto px-4 sm:px-6 py-10 flex-1 flex items-center">
      <div class="card-menta w-full p-8 sm:p-10 bg-gradient-to-b from-white via-white to-[#F0FDF9] border-2 border-[#A7F3D0] text-center space-y-6 shadow-lg">
        <!-- Icono de Bienvenida -->
        <div class="w-20 h-20 rounded-3xl bg-[#D1FAE5] text-[#0F766E] ring-8 ring-[#F0FDF9] flex items-center justify-center mx-auto shadow-sm">
          <component :is="Lucide.Sparkles" class="w-10 h-10" />
        </div>

        <div class="space-y-2">
          <span class="badge-menta uppercase tracking-wider">
            Activación de Cuenta Exitosa
          </span>
          <h2 class="text-2xl sm:text-3xl font-black text-gray-900">
            ¡Bienvenido(a) a la Academia de Música y Arte SCALA!
          </h2>
          <p class="text-sm text-gray-600 max-w-xl mx-auto leading-relaxed">
            Estimado(a) <strong>{{ usuario?.nombre_completo }}</strong>, has completado satisfactoriamente la lectura y aceptación de la <strong>Política de Privacidad</strong> y los <strong>Términos y Condiciones</strong> de nuestra plataforma académica.
          </p>
        </div>

        <!-- Resumen de Verificaciones Completadas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left max-w-xl mx-auto">
          <div class="p-3.5 rounded-xl bg-emerald-50/80 border border-emerald-200 flex items-start gap-3">
            <component :is="Lucide.CheckCircle2" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
            <div class="text-xs">
              <span class="font-extrabold text-emerald-900 block">Política de Privacidad</span>
              <span class="text-emerald-700">Aceptada y registrada ({{ fecha_proceso }})</span>
            </div>
          </div>

          <div class="p-3.5 rounded-xl bg-emerald-50/80 border border-emerald-200 flex items-start gap-3">
            <component :is="Lucide.CheckCircle2" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
            <div class="text-xs">
              <span class="font-extrabold text-emerald-900 block">Términos y Condiciones</span>
              <span class="text-emerald-700">Aceptados y registrados ({{ fecha_proceso }})</span>
            </div>
          </div>
        </div>

        <!-- Datos de la cuenta habilitada -->
        <div class="max-w-xl mx-auto p-4 rounded-xl bg-white border border-[#DDEFEA] flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-full bg-[#14B8A6] text-white font-black flex items-center justify-center">
              {{ (usuario?.nombre_completo || 'U').charAt(0) }}
            </div>
            <div class="text-left">
              <span class="font-extrabold text-gray-900 block">{{ usuario?.nombre_completo }}</span>
              <span class="text-gray-500 font-mono">{{ usuario?.email }}</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 font-mono font-bold">
              {{ usuario?.codigo || 'SCALA' }}
            </span>
            <span class="px-2.5 py-1 rounded-md bg-[#D1FAE5] text-[#0F766E] font-extrabold">
              Rol: {{ usuario?.rol || 'Usuario' }}
            </span>
          </div>
        </div>

        <!-- Botón Principal hacia la Página de Actividades (Figura 7.15) -->
        <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
          <Link
            :href="buildUrl('/dashboard')"
            class="btn-menta text-sm !py-3 !px-8 shadow-md"
          >
            <component :is="Lucide.LayoutDashboard" class="w-4 h-4" />
            <span>Continuar con mis Actividades Académicas</span>
          </Link>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';

defineProps({
  fecha_proceso: {
    type: String,
    default: '',
  },
  usuario: {
    type: Object,
    default: () => ({}),
  },
});

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};
</script>
