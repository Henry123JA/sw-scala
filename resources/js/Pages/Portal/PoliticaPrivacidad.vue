<template>
  <Head title="Política de Privacidad - Academia de Música y Arte SCALA" />

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
              SCALA · Portal Legal y Privacidad
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
          <span class="inline-flex items-center gap-1.5 font-extrabold text-[#0F766E]">
            <span class="w-5 h-5 rounded-full bg-[#0F766E] text-white flex items-center justify-center text-[11px]">1</span>
            Política de Privacidad
          </span>
          <component :is="Lucide.ChevronRight" class="w-4 h-4 text-gray-300" />
          <span class="inline-flex items-center gap-1.5 font-semibold text-gray-400">
            <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center text-[11px]">2</span>
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
      <!-- ESTADO B: CONFIRMACIÓN DE POLÍTICA DE PRIVACIDAD (Figura 7.11)      -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div
        v-if="mostrarConfirmacion"
        class="card-menta p-8 border-2 border-emerald-300 bg-gradient-to-b from-white to-[#F0FDF9] text-center space-y-5 shadow-md"
      >
        <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-700 border border-emerald-300 flex items-center justify-center mx-auto shadow-xs">
          <component :is="Lucide.ShieldCheck" class="w-9 h-9" />
        </div>

        <div class="space-y-2 max-w-xl mx-auto">
          <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
            Confirmación Registrada · Paso 1 Completado
          </span>
          <h2 class="text-2xl font-black text-gray-900">
            Política de Privacidad Aceptada Correctamente
          </h2>
          <p class="text-sm text-gray-600 leading-relaxed">
            Se ha registrado su consentimiento informado sobre la <strong>Política de Privacidad y Protección de Datos Académicos</strong> de la Academia de Música y Arte SCALA.
          </p>
          <p v-if="usuario?.email" class="text-xs font-semibold text-[#0F766E] bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 inline-block">
            📧 Se ha enviado una copia oficial de la Política de Privacidad a tu correo: <strong>{{ usuario.email }}</strong>
          </p>
        </div>

        <!-- Resumen de Auditoría -->
        <div class="max-w-lg mx-auto p-4 rounded-xl bg-white border border-[#DDEFEA] text-left text-xs space-y-2">
          <div class="flex items-center justify-between border-b border-[#DDEFEA] pb-1.5">
            <span class="text-gray-500 font-semibold">Documento confirmado:</span>
            <span class="font-bold text-gray-900">Política de Privacidad SCALA v2.1</span>
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
            Ver Documento de Política
          </button>

          <Link
            :href="buildUrl('/terminos-condiciones?revisar=1&onboarding=1')"
            class="btn-menta text-xs !py-2.5 !px-6 shadow-sm"
          >
            <span>Continuar a Términos y Condiciones</span>
            <component :is="Lucide.ArrowRight" class="w-4 h-4" />
          </Link>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- ESTADO A: VISTA INDEPENDIENTE DE POLÍTICA DE PRIVACIDAD (Fig. 7.10) -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div v-else class="card-menta p-6 sm:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#DDEFEA]">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
              <component :is="Lucide.Shield" class="w-6 h-6" />
            </div>
            <div>
              <span class="badge-menta">Documento Normativo Institucional</span>
              <h2 class="text-xl sm:text-2xl font-black text-gray-900 mt-0.5">
                Política de Privacidad y Protección de Datos
              </h2>
            </div>
          </div>

          <span class="text-xs font-mono text-gray-500 bg-[#F0FDF9] px-3 py-1.5 rounded-xl border border-[#DDEFEA]">
            Vigencia: Gestión 2026
          </span>
        </div>

        <!-- Cuerpo de la Política de Privacidad -->
        <div class="space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed max-h-[420px] overflow-y-auto pr-2">
          <p>
            La <strong>Academia de Música y Arte SCALA</strong>, con sede en Santa Cruz de la Sierra, Bolivia, establece la presente <strong>Política de Privacidad</strong> para garantizar la confidencialidad, integridad y resguardo de la información personal y académica registrada en la plataforma institucional <code>techmusic.work</code>.
          </p>

          <div class="p-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] space-y-1.5">
            <h3 class="font-extrabold text-[#0F766E] text-sm">
              1. Finalidad de la Recopilación de Datos Académicos
            </h3>
            <p class="text-xs text-gray-600">
              Los datos personales del estudiante (nombre completo, documento de identidad, teléfono de contacto, correo electrónico) y sus registros formativos (asistencias por sesión, calificaciones prácticas/teóricas y bitácora de observaciones docentes) se utilizan exclusivamente para la gestión pedagógica, emisión de boletines y seguimiento del progreso musical.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] space-y-1.5">
            <h3 class="font-extrabold text-[#0F766E] text-sm">
              2. Protección de Menores y Control de Visibilidad (Ley N.º 548)
            </h3>
            <p class="text-xs text-gray-600">
              En cumplimiento del Código Niña, Niño y Adolescente (Ley N.º 548), el acceso a los expedientes individuales se encuentra restringido mediante Control de Acceso Basado en Roles (RBAC). Cada estudiante o tutor únicamente puede visualizar su propia ficha académica, y las anotaciones internas de coordinación cuentan con aislamiento de privacidad.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] space-y-1.5">
            <h3 class="font-extrabold text-[#0F766E] text-sm">
              3. Uso Ético de Analítica Predictiva e Inteligencia Artificial Explicable (XAI)
            </h3>
            <p class="text-xs text-gray-600">
              Los indicadores procesados por el módulo de Seguimiento Académico Predictivo tienen carácter estrictamente preventivo y orientador para activar tutorías de apoyo o refuerzos técnicos, sin compartir información con terceros ajenos a la institución.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] space-y-1.5">
            <h3 class="font-extrabold text-[#0F766E] text-sm">
              4. Seguridad Transaccional y Bitácora de Auditoría
            </h3>
            <p class="text-xs text-gray-600">
              Las credenciales de acceso se almacenan bajo cifrado criptográfico unidireccional y toda operación relevante queda registrada en la bitácora institucional para resguardar la trazabilidad y transparencia de los registros.
            </p>
          </div>
        </div>

        <!-- Casilla de Aceptación y Botón -->
        <form @submit.prevent="confirmarPolitica" class="pt-4 border-t border-[#DDEFEA] space-y-4">
          <label class="flex items-start gap-3 p-3.5 rounded-xl bg-[#F0FDF9] border border-[#DDEFEA] cursor-pointer">
            <input
              v-model="casillaAceptada"
              type="checkbox"
              required
              class="mt-0.5 w-4 h-4 accent-[#0F766E] rounded cursor-pointer"
            />
            <span class="text-xs sm:text-sm font-semibold text-gray-800">
              He leído íntegramente y acepto la <strong>Política de Privacidad y Tratamiento de Datos Académicos</strong> de la Academia de Música y Arte SCALA.
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
              <Link v-else :href="buildUrl('/')" class="btn-menta-outline text-xs !py-2.5">
                <component :is="Lucide.ArrowLeft" class="w-4 h-4" />
                Volver al Portal
              </Link>
            </div>

            <button
              type="submit"
              :disabled="!casillaAceptada || form.processing"
              class="btn-menta text-xs !py-2.5 !px-6 disabled:opacity-50"
            >
              <component :is="Lucide.CheckCircle2" class="w-4 h-4" />
              <span>{{ form.processing ? 'Aceptando y enviando correo...' : 'Aceptar Política de Privacidad' }}</span>
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

function confirmarPolitica() {
  form.post(buildUrl('/politica-privacidad/aceptar'), {
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
