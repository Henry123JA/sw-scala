<template>
  <Head title="Términos y Condiciones - Academia de Música y Arte SCALA" />

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
              SCALA · Términos y Condiciones de Uso
            </h1>
          </div>
        </Link>

        <div class="flex items-center gap-2.5 text-xs">
          <Link
            :href="buildUrl('/')"
            class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold inline-flex items-center gap-1.5 transition-colors"
          >
            <component :is="Lucide.Home" class="w-3.5 h-3.5" />
            <span>Inicio Corporativo</span>
          </Link>
        </div>
      </div>
    </header>

    <!-- Indicador de Flujo de Primer Inicio de Sesión -->
    <div class="bg-white border-b border-[#DDEFEA]">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 sm:gap-4 flex-wrap">
          <Link :href="buildUrl('/politica-privacidad')" class="inline-flex items-center gap-1.5 font-bold text-emerald-700">
            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[11px]">✓</span>
            Política de Privacidad
          </Link>
          <component :is="Lucide.ChevronRight" class="w-4 h-4 text-gray-300" />
          <span class="inline-flex items-center gap-1.5 font-extrabold text-[#0F766E]">
            <span class="w-5 h-5 rounded-full bg-[#0F766E] text-white flex items-center justify-center text-[11px]">2</span>
            Términos y Condiciones
          </span>
          <component :is="Lucide.ChevronRight" class="w-4 h-4 text-gray-300" />
          <span class="inline-flex items-center gap-1.5 font-semibold text-gray-400">
            <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center text-[11px]">3</span>
            Mensaje de Bienvenida
          </span>
        </div>

        <span v-if="usuario" class="px-2.5 py-1 rounded-full bg-[#F0FDF9] border border-[#DDEFEA] text-[#0F766E] font-bold">
          Usuario: {{ usuario.nombre_completo }} ({{ usuario.codigo || usuario.rol }})
        </span>
      </div>
    </div>

    <!-- Contenido Principal -->
    <main class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-8 flex-1 space-y-6">
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- ESTADO B: CONFIRMACIÓN DE TÉRMINOS Y CONDICIONES (Figura 7.13)      -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div
        v-if="mostrarConfirmacion"
        class="card-menta p-8 border-2 border-emerald-300 bg-gradient-to-b from-white to-[#F0FDF9] text-center space-y-5 shadow-md"
      >
        <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-700 border border-emerald-300 flex items-center justify-center mx-auto shadow-xs">
          <component :is="Lucide.FileCheck2" class="w-9 h-9" />
        </div>

        <div class="space-y-2 max-w-xl mx-auto">
          <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
            Confirmación Registrada · Paso 2 Completado
          </span>
          <h2 class="text-2xl font-black text-gray-900">
            Términos y Condiciones Aceptados Correctamente
          </h2>
          <p class="text-sm text-gray-600 leading-relaxed">
            Se ha registrado su conformidad con los <strong>Términos y Condiciones de Uso de la Plataforma Académica SCALA</strong>. Ya puede finalizar su proceso de bienvenida.
          </p>
          <p v-if="usuario?.email" class="text-xs font-semibold text-[#0F766E] bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 inline-block">
            📧 Se han enviado 2 correos independientes a <strong>{{ usuario.email }}</strong>: la copia de los Términos y Condiciones y el Correo Oficial de Bienvenida al Sistema.
          </p>
        </div>

        <!-- Resumen de Auditoría -->
        <div class="max-w-lg mx-auto p-4 rounded-xl bg-white border border-[#DDEFEA] text-left text-xs space-y-2">
          <div class="flex items-center justify-between border-b border-[#DDEFEA] pb-1.5">
            <span class="text-gray-500 font-semibold">Documento confirmado:</span>
            <span class="font-bold text-gray-900">Términos y Condiciones SCALA v2.1</span>
          </div>
          <div class="flex items-center justify-between border-b border-[#DDEFEA] pb-1.5">
            <span class="text-gray-500 font-semibold">Usuario / Visitante:</span>
            <span class="font-bold text-[#0F766E]">{{ usuario?.nombre_completo || 'Usuario Autenticado SCALA' }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-500 font-semibold">Fecha y Estado:</span>
            <span class="font-mono font-bold text-emerald-700">{{ fecha_aceptacion }} · ACEPTADO</span>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
          <button
            type="button"
            @click="mostrarConfirmacion = false"
            class="btn-menta-outline text-xs !py-2.5"
          >
            <component :is="Lucide.FileText" class="w-4 h-4" />
            Ver Documento de Términos
          </button>

          <Link
            :href="buildUrl('/bienvenida')"
            class="btn-menta text-xs !py-2.5 !px-6 shadow-sm"
          >
            <span>Finalizar y Ver Mensaje de Bienvenida</span>
            <component :is="Lucide.ArrowRight" class="w-4 h-4" />
          </Link>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- ESTADO A: VISTA INDEPENDIENTE DE TÉRMINOS Y CONDICIONES (Fig. 7.12) -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div v-else class="card-menta p-6 sm:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#DDEFEA]">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
              <component :is="Lucide.ScrollText" class="w-6 h-6" />
            </div>
            <div>
              <span class="badge-menta">Reglamento de Uso del Sistema</span>
              <h2 class="text-xl sm:text-2xl font-black text-gray-900 mt-0.5">
                Términos y Condiciones de Uso
              </h2>
            </div>
          </div>

          <span class="text-xs font-mono text-gray-500 bg-[#F0FDF9] px-3 py-1.5 rounded-xl border border-[#DDEFEA]">
            Plataforma SCALA · 2026
          </span>
        </div>

        <!-- Cuerpo de Términos y Condiciones -->
        <div class="space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed max-h-[420px] overflow-y-auto pr-2">
          <p>
            El acceso y uso del Sistema Web para la Gestión y Seguimiento Académico Predictivo de la <strong>Academia de Música y Arte SCALA</strong> (<code>techmusic.work</code>) se rige por los siguientes términos y condiciones institucionales:
          </p>

          <div class="p-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] space-y-1.5">
            <h3 class="font-extrabold text-[#0F766E] text-sm">
              1. Asignación yCustodia de Credenciales de Acceso
            </h3>
            <p class="text-xs text-gray-600">
              El registro inicial de usuarios (Administración, Secretaría, Docentes y Alumnos) es efectuado por personal autorizado de la Academia. Las credenciales entregadas para el primer inicio de sesión son personales e intransferibles, pudiendo el usuario actualizar su contraseña desde su perfil personal.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] space-y-1.5">
            <h3 class="font-extrabold text-[#0F766E] text-sm">
              2. Régimen de Asistencia, Puntualidad y Licencias
            </h3>
            <p class="text-xs text-gray-600">
              La regularidad de asistencia se registra en cada sesión de clase bajo los estados <em>Presente, Atraso, Licencia justificada o Falta injustificada</em>. Las solicitudes de licencia o pausa temporal de inscripción deben comunicarse oportunamente a Secretaría Académica.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] space-y-1.5">
            <h3 class="font-extrabold text-[#0F766E] text-sm">
              3. Evaluaciones Formativas, Audiciones y Seguimiento Pedagógico
            </h3>
            <p class="text-xs text-gray-600">
              Las calificaciones de prácticas instrumentales, controles teóricos, audiciones y recitales, así como las recomendaciones registradas en la bitácora pedagógica y las intervenciones de seguimiento, tienen como propósito apoyar el progreso continuo del estudiante.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] space-y-1.5">
            <h3 class="font-extrabold text-[#0F766E] text-sm">
              4. Uso Responsable de los Recursos y Salas de la Academia
            </h3>
            <p class="text-xs text-gray-600">
              Los estudiantes y docentes se comprometen a respetar los horarios asignados, el aforo máximo de cada aula acustizada y el cuidado de los instrumentos musicales e infraestructura institucional.
            </p>
          </div>
        </div>

        <!-- Casilla de Aceptación y Botón -->
        <form @submit.prevent="confirmarTerminos" class="pt-4 border-t border-[#DDEFEA] space-y-4">
          <label class="flex items-start gap-3 p-3.5 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] cursor-pointer">
            <input
              v-model="casillaAceptada"
              type="checkbox"
              required
              class="mt-0.5 w-4 h-4 accent-[#0F766E] rounded cursor-pointer"
            />
            <span class="text-xs sm:text-sm font-semibold text-gray-800">
              He leído y acepto los <strong>Términos y Condiciones de Uso del Sistema Académico</strong> de la Academia de Música y Arte SCALA.
            </span>
          </label>

          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <button
                v-if="usuario"
                type="button"
                @click="rechazarYSalir"
                class="px-4 py-2.5 rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer transition"
              >
                <component :is="Lucide.XCircle" class="w-4 h-4" />
                <span>No acepto (Salir)</span>
              </button>
              <Link :href="buildUrl('/politica-privacidad?revisar=1')" class="btn-menta-outline text-xs !py-2.5">
                <component :is="Lucide.ArrowLeft" class="w-4 h-4" />
                Volver a Política
              </Link>
            </div>

            <button
              type="submit"
              :disabled="!casillaAceptada || form.processing"
              class="btn-menta text-xs !py-2.5 !px-6 disabled:opacity-50"
            >
              <component :is="Lucide.CheckCircle2" class="w-4 h-4" />
              <span>{{ form.processing ? 'Aceptando y enviando correos...' : 'Aceptar Términos y Condiciones' }}</span>
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  confirmado: {
    type: Boolean,
    default: false,
  },
  fecha_aceptacion: {
    type: String,
    default: '',
  },
  onboarding: {
    type: Boolean,
    default: true,
  },
  usuario: {
    type: Object,
    default: null,
  },
});

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const casillaAceptada = ref(true);
const mostrarConfirmacion = ref(props.confirmado);

watch(
  () => props.confirmado,
  (val) => {
    mostrarConfirmacion.value = val;
  }
);

const form = useForm({});

function confirmarTerminos() {
  form.post(buildUrl('/terminos-condiciones/aceptar'), {
    preserveScroll: true,
    onSuccess: () => {
      mostrarConfirmacion.value = true;
    },
  });
}

function rechazarYSalir() {
  form.post(buildUrl('/onboarding/rechazar'));
}
</script>
