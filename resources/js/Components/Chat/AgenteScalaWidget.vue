<template>
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- AGENTE DE ATENCIÓN SCALA IA (Opción A3 + B1: OpenAI + PostgreSQL Vivo)  -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div class="fixed bottom-5 right-5 z-50 flex flex-col items-end select-none font-sans">
    <!-- Ventana del Chat Desplegada -->
    <div
      v-if="abierto"
      class="mb-3 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-[#DDEFEA] overflow-hidden flex flex-col transition-all"
    >
      <!-- Cabecera del Agente con Logo Oficial SCALA y Estado del Motor IA -->
      <div class="bg-gradient-to-r from-[#0F766E] to-[#14B8A6] text-white px-4 py-3 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="relative shrink-0">
            <div class="w-10 h-10 rounded-xl bg-white p-0.5 border border-amber-300 shadow-xs overflow-hidden">
              <img
                :src="buildUrl('/images/academia-scala.jpg')"
                alt="Agente SCALA"
                class="w-full h-full object-cover rounded-lg"
              />
            </div>
            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-300 border-2 border-[#0F766E]"></span>
          </div>
          <div class="min-w-0">
            <h4 class="text-xs sm:text-sm font-black leading-tight truncate">
              Agente de Atención SCALA
            </h4>
            <p class="text-[10px] text-teal-100 flex items-center gap-1 truncate mt-0.5">
              <span class="w-1.5 h-1.5 rounded-full bg-amber-300 animate-pulse shrink-0"></span>
              <span>{{ etiquetaMotor }}</span>
            </p>
          </div>
        </div>

        <div class="flex items-center gap-1 shrink-0">
          <button
            type="button"
            @click="reiniciarConversacion"
            class="p-1.5 rounded-lg hover:bg-white/15 text-white/90 transition-colors cursor-pointer"
            title="Reiniciar conversación"
          >
            <component :is="Lucide.RotateCcw" class="w-3.5 h-3.5" />
          </button>
          <button
            type="button"
            @click="abierto = false"
            class="p-1.5 rounded-lg hover:bg-white/15 text-white transition-colors cursor-pointer"
            title="Minimizar asistente"
          >
            <component :is="Lucide.X" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Sub-barra de Contexto (Portal Público vs Panel Académico) -->
      <div class="bg-[#F0FDF9] border-b border-[#DDEFEA] px-3.5 py-1.5 flex items-center justify-between text-[10px] text-[#0F766E] font-bold">
        <span class="inline-flex items-center gap-1">
          <component :is="Lucide.Database" class="w-3 h-3 text-[#14B8A6]" />
          <span>Conectado a PostgreSQL en tiempo real</span>
        </span>
        <span class="px-1.5 py-0.5 rounded bg-[#D1FAE5] text-[#0F766E] uppercase tracking-wider text-[9px] font-extrabold">
          {{ modo === 'panel' ? (authUser?.rol?.nombre || 'Panel Interno') : 'Portal Público' }}
        </span>
      </div>

      <!-- Historial de Mensajes -->
      <div
        ref="contenedorMensajesRef"
        class="p-3.5 h-72 overflow-y-auto space-y-3 bg-[#F8FFFD] text-xs"
      >
        <div
          v-for="(msg, index) in mensajes"
          :key="index"
          class="flex"
          :class="msg.emisor === 'usuario' ? 'justify-end' : 'justify-start'"
        >
          <div
            class="max-w-[86%] px-3.5 py-2.5 rounded-2xl leading-relaxed shadow-2xs"
            :class="
              msg.emisor === 'usuario'
                ? 'bg-[#0F766E] text-white rounded-br-xs'
                : 'bg-white text-gray-800 border border-[#DDEFEA] rounded-bl-xs'
            "
          >
            <p class="whitespace-pre-line">{{ msg.texto }}</p>
            <div
              class="mt-1 flex items-center justify-between gap-2 text-[9px]"
              :class="msg.emisor === 'usuario' ? 'text-teal-200 justify-end' : 'text-gray-400'"
            >
              <span v-if="msg.emisor === 'bot' && msg.fuente" class="font-semibold text-[#0F766E]">
                {{ msg.fuente }}
              </span>
              <span>{{ msg.hora }}</span>
            </div>
          </div>
        </div>

        <!-- Indicador de carga ("Escribiendo...") -->
        <div v-if="cargando" class="flex justify-start">
          <div class="bg-white border border-[#DDEFEA] text-gray-600 px-3.5 py-2.5 rounded-2xl rounded-bl-xs shadow-2xs flex items-center gap-2 text-[11px]">
            <span class="flex gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-[#14B8A6] animate-bounce"></span>
              <span class="w-1.5 h-1.5 rounded-full bg-[#0F766E] animate-bounce" style="animation-delay: 150ms"></span>
              <span class="w-1.5 h-1.5 rounded-full bg-[#14B8A6] animate-bounce" style="animation-delay: 300ms"></span>
            </span>
            <span class="font-semibold text-[#0F766E]">Consultando datos SCALA...</span>
          </div>
        </div>
      </div>

      <!-- Botones de Consultas Rápidas -->
      <div class="px-3 py-2 bg-white border-t border-[#DDEFEA] flex flex-wrap gap-1.5">
        <button
          v-for="sug in sugerenciasActivas"
          :key="sug"
          type="button"
          :disabled="cargando"
          @click="enviarPregunta(sug)"
          class="px-2.5 py-1 rounded-full bg-[#F0FDF9] hover:bg-[#D1FAE5] disabled:opacity-50 text-[#0F766E] border border-[#DDEFEA] text-[10px] font-bold transition-colors cursor-pointer"
        >
          {{ sug }}
        </button>
      </div>

      <!-- Caja de Entrada de Mensaje -->
      <form @submit.prevent="enviarDesdeInput" class="p-2.5 bg-white border-t border-[#DDEFEA] flex items-center gap-2">
        <input
          v-model="inputMensaje"
          type="text"
          :disabled="cargando"
          placeholder="Escribe tu consulta al Agente SCALA..."
          class="flex-1 text-xs px-3 py-2 rounded-xl border border-[#DDEFEA] bg-[#F8FFFD] focus:bg-white focus:outline-none focus:border-[#14B8A6] disabled:opacity-60"
        />
        <button
          type="submit"
          :disabled="cargando || !inputMensaje.trim()"
          class="w-9 h-9 rounded-xl bg-[#14B8A6] hover:bg-[#0D9488] disabled:opacity-50 text-white flex items-center justify-center transition-colors cursor-pointer shrink-0"
          title="Enviar mensaje"
        >
          <component :is="Lucide.Send" class="w-4 h-4" />
        </button>
      </form>
    </div>

    <!-- Botón Flotante para Abrir/Cerrar el Agente -->
    <button
      type="button"
      @click="abierto = !abierto"
      class="group flex items-center gap-2.5 bg-gradient-to-r from-[#0F766E] to-[#14B8A6] hover:from-[#115E59] hover:to-[#0F766E] text-white pl-3 pr-4 py-2.5 rounded-full shadow-xl border-2 border-white transition-all cursor-pointer"
    >
      <div class="relative w-8 h-8 rounded-full bg-white p-0.5 overflow-hidden shrink-0">
        <img
          :src="buildUrl('/images/academia-scala.jpg')"
          alt="Agente SCALA"
          class="w-full h-full object-cover rounded-full"
        />
        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-amber-300 animate-ping"></span>
      </div>
      <div class="text-left">
        <span class="block text-xs font-extrabold tracking-wide leading-tight">
          {{ abierto ? 'Cerrar Agente IA' : 'Agente de Atención SCALA' }}
        </span>
        <span class="block text-[10px] text-teal-100 font-medium leading-none mt-0.5">
          Asistente IA · En línea
        </span>
      </div>
    </button>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  modo: {
    type: String,
    default: 'portal', // 'portal' | 'panel'
  },
  abiertoInicialmente: {
    type: Boolean,
    default: false,
  },
  agenteConfig: {
    type: Object,
    default: () => ({}),
  },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const abierto = ref(props.abiertoInicialmente);
const inputMensaje = ref('');
const cargando = ref(false);
const motorActual = ref(props.agenteConfig?.openai_configurado ? 'OPENAI' : 'LOCAL_BD');
const modeloActual = ref(props.agenteConfig?.modelo || 'gpt-4o-mini');
const contenedorMensajesRef = ref(null);

const etiquetaMotor = computed(() => {
  if (motorActual.value === 'OPENAI') {
    return `OpenAI (${modeloActual.value}) · En línea`;
  }
  return 'IA SCALA + PostgreSQL · En línea';
});

const sugerenciasActivas = computed(() => {
  if (props.modo === 'panel') {
    const rol = authUser.value?.rol?.nombre || '';
    if (rol === 'Propietario' || rol === 'Secretaria') {
      return [
        '¿Qué alumnos están en riesgo alto?',
        'Resumen ejecutivo de la academia',
        'Horarios y cupos de grupos',
        'Explicación XAI de alertas',
      ];
    }
    if (rol === 'Docente') {
      return [
        'Mis grupos y alumnos asignados',
        '¿Tengo alumnos en riesgo IA?',
        'Horarios de aulas y cursos',
      ];
    }
    return [
      '¿Cuál es mi estado académico?',
      'Mis cursos y horarios',
      '¿Cómo funciona el seguimiento IA?',
    ];
  }
  return [
    '¿Qué cursos ofrecen?',
    'Horarios y cupos libres',
    '¿Cómo me inscribo?',
    'Ubicación y Google Maps',
  ];
});

function crearMensajeBienvenida() {
  if (props.modo === 'panel' && authUser.value) {
    const nombre = authUser.value.nombres || 'Usuario';
    return {
      emisor: 'bot',
      texto: `¡Hola, ${nombre}! Soy el Agente de Atención SCALA conectado en tiempo real a la base de datos académica y OpenAI. ¿En qué puedo asistirte hoy?`,
      hora: 'En línea',
      fuente: 'SCALA IA',
    };
  }
  return {
    emisor: 'bot',
    texto: '¡Hola! Soy el Agente Virtual de la Academia de Música y Arte SCALA. Estoy conectado en tiempo real a nuestra oferta académica, horarios y cupos. ¿En qué puedo ayudarte hoy?',
    hora: 'En línea',
    fuente: 'SCALA IA',
  };
}

const mensajes = ref([crearMensajeBienvenida()]);

function scrollAlFinal() {
  nextTick(() => {
    if (contenedorMensajesRef.value) {
      contenedorMensajesRef.value.scrollTop = contenedorMensajesRef.value.scrollHeight;
    }
  });
}

function obtenerHoraActual() {
  const ahora = new Date();
  return ahora.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });
}

function reiniciarConversacion() {
  mensajes.value = [crearMensajeBienvenida()];
}

async function enviarPregunta(textoPregunta) {
  const texto = (textoPregunta || '').trim();
  if (!texto || cargando.value) return;

  const historialPrevio = mensajes.value.slice(-6).map((m) => ({
    emisor: m.emisor,
    texto: m.texto,
  }));

  mensajes.value.push({
    emisor: 'usuario',
    texto,
    hora: obtenerHoraActual(),
  });

  cargando.value = true;
  scrollAlFinal();

  try {
    const response = await fetch(buildUrl('/api/agente/chat'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        mensaje: texto,
        historial: historialPrevio,
      }),
    });

    if (response.ok) {
      const data = await response.json();
      if (data.motor) motorActual.value = data.motor;
      if (data.modelo) modeloActual.value = data.modelo;

      mensajes.value.push({
        emisor: 'bot',
        texto: data.respuesta || 'Consulta procesada correctamente.',
        hora: obtenerHoraActual(),
        fuente: data.motor === 'OPENAI' ? `OpenAI · ${data.modelo}` : 'SCALA · PostgreSQL',
      });
    } else {
      throw new Error('HTTP ' + response.status);
    }
  } catch (err) {
    mensajes.value.push({
      emisor: 'bot',
      texto: 'En este momento puedes dejarnos tus datos en el Formulario de Contacto o comunicarte al +591 700-45890 para atención inmediata de Secretaría Académica.',
      hora: obtenerHoraActual(),
      fuente: 'SCALA Local',
    });
  } finally {
    cargando.value = false;
    scrollAlFinal();
  }
}

function enviarDesdeInput() {
  const txt = inputMensaje.value.trim();
  if (!txt) return;
  inputMensaje.value = '';
  enviarPregunta(txt);
}

watch(abierto, (val) => {
  if (val) scrollAlFinal();
});

defineExpose({
  abrir: () => {
    abierto.value = true;
  },
});
</script>
