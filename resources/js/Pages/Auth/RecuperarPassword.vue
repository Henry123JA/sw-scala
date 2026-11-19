<template>
  <Head title="Cambiar Contraseña con Código Temporal - Academia SCALA" />

  <div class="min-h-screen flex items-center justify-center bg-[#F8FFFD] px-4 py-8 font-sans">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl shadow-sm border border-[#DDEFEA]">
      <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#F0FDF9] border border-[#DDEFEA] text-[#0F766E] mb-3 shadow-2xs">
          <component :is="Lucide.KeyRound" class="w-8 h-8" />
        </div>
        <h2 class="text-xl font-black tracking-tight text-[#1F2937]">
          Cambio de Contraseña por Código Temporal
        </h2>
        <p class="mt-1 text-xs text-[#6B7280] leading-relaxed">
          Enviaremos un código temporal de <strong>6 dígitos</strong> desde la cuenta del Administrador hacia tu correo electrónico registrado.
        </p>
      </div>

      <!-- Flash Success -->
      <div
        v-if="$page.props.flash?.success"
        class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-700 font-semibold"
      >
        {{ $page.props.flash.success }}
      </div>

      <!-- PASO 1: Solicitar Código Temporal al Correo -->
      <form v-if="!mostrarPasoCodigo" class="space-y-4" @submit.prevent="enviarCodigo">
        <div>
          <label class="block text-xs font-bold text-[#1F2937] mb-1 uppercase tracking-wider">
            Correo Electrónico de tu Cuenta
          </label>
          <input
            type="email"
            required
            v-model="formEnvio.email"
            placeholder="ejemplo@gmail.com"
            class="w-full px-3.5 py-2.5 bg-white border border-[#DDEFEA] rounded-xl text-sm text-[#1F2937] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#14B8A6]/20 focus:border-[#14B8A6]"
          />
          <p v-if="formEnvio.errors.email" class="mt-1 text-xs text-red-600 font-medium">
            {{ formEnvio.errors.email }}
          </p>
        </div>

        <button
          type="submit"
          :disabled="formEnvio.processing"
          class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-[#14B8A6] hover:bg-[#0D9488] shadow-xs transition-all disabled:opacity-50 cursor-pointer"
        >
          <component :is="Lucide.Send" class="w-4 h-4" />
          <span>{{ formEnvio.processing ? 'Enviando código al correo...' : 'Enviar Código Temporal (6 dígitos)' }}</span>
        </button>

        <div class="text-center">
          <button
            type="button"
            @click="irAPasoCodigo"
            class="text-xs font-semibold text-[#0F766E] hover:underline cursor-pointer"
          >
            ¿Ya recibiste un código temporal? Ingrésalo aquí →
          </button>
        </div>
      </form>

      <!-- PASO 2: Ingresar Código de 6 dígitos y Nueva Contraseña -->
      <form v-else class="space-y-4" @submit.prevent="confirmarCambio">
        <div>
          <label class="block text-xs font-bold text-[#1F2937] mb-1 uppercase tracking-wider">
            Correo Electrónico
          </label>
          <input
            type="email"
            required
            v-model="formCambio.email"
            class="w-full px-3.5 py-2 bg-[#F8FFFD] border border-[#DDEFEA] rounded-xl text-sm text-[#1F2937]"
          />
          <p v-if="formCambio.errors.email" class="mt-1 text-xs text-red-600 font-medium">
            {{ formCambio.errors.email }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#1F2937] mb-1 uppercase tracking-wider">
            Código Temporal de 6 Dígitos (Revisa tu correo)
          </label>
          <input
            type="text"
            maxlength="6"
            required
            v-model="formCambio.codigo"
            placeholder="123456"
            class="w-full px-3.5 py-2.5 bg-white border-2 border-[#14B8A6] rounded-xl text-center text-lg font-mono font-black tracking-[0.4em] text-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#14B8A6]/30"
          />
          <p v-if="formCambio.errors.codigo" class="mt-1 text-xs text-red-600 font-medium">
            {{ formCambio.errors.codigo }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#1F2937] mb-1 uppercase tracking-wider">
            Nueva Contraseña
          </label>
          <input
            type="password"
            required
            minlength="6"
            v-model="formCambio.password"
            placeholder="Mínimo 6 caracteres"
            class="w-full px-3.5 py-2.5 bg-white border border-[#DDEFEA] rounded-xl text-sm text-[#1F2937] focus:outline-none focus:border-[#14B8A6]"
          />
          <p v-if="formCambio.errors.password" class="mt-1 text-xs text-red-600 font-medium">
            {{ formCambio.errors.password }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#1F2937] mb-1 uppercase tracking-wider">
            Confirmar Nueva Contraseña
          </label>
          <input
            type="password"
            required
            minlength="6"
            v-model="formCambio.password_confirmation"
            placeholder="Repite la nueva contraseña"
            class="w-full px-3.5 py-2.5 bg-white border border-[#DDEFEA] rounded-xl text-sm text-[#1F2937] focus:outline-none focus:border-[#14B8A6]"
          />
        </div>

        <button
          type="submit"
          :disabled="formCambio.processing"
          class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-[#0F766E] hover:bg-[#115E59] shadow-xs transition-all disabled:opacity-50 cursor-pointer"
        >
          <component :is="Lucide.CheckCircle2" class="w-4 h-4" />
          <span>{{ formCambio.processing ? 'Verificando código...' : 'Verificar Código y Actualizar Contraseña' }}</span>
        </button>

        <div class="flex items-center justify-between text-xs pt-1">
          <button
            type="button"
            @click="mostrarPasoCodigo = false"
            class="text-gray-500 hover:text-[#0F766E] font-semibold cursor-pointer"
          >
            ← Solicitar otro código
          </button>
        </div>
      </form>

      <div class="pt-3 border-t border-[#DDEFEA] text-center">
        <Link :href="buildUrl('/login')" class="text-xs font-bold text-[#0F766E] hover:underline">
          ← Volver al Inicio de Sesión
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';

const props = defineProps({
  email_inicial: { type: String, default: '' },
  codigo_enviado: { type: Boolean, default: false },
});

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const mostrarPasoCodigo = ref(props.codigo_enviado);

const formEnvio = useForm({
  email: props.email_inicial || '',
});

const formCambio = useForm({
  email: props.email_inicial || '',
  codigo: '',
  password: '',
  password_confirmation: '',
});

const enviarCodigo = () => {
  formEnvio.post(buildUrl('/password/enviar-codigo'), {
    preserveScroll: true,
    onSuccess: () => {
      formCambio.email = formEnvio.email;
      mostrarPasoCodigo.value = true;
    },
  });
};

const irAPasoCodigo = () => {
  if (formEnvio.email) {
    formCambio.email = formEnvio.email;
  }
  mostrarPasoCodigo.value = true;
};

const confirmarCambio = () => {
  formCambio.post(buildUrl('/password/verificar-cambiar'), {
    preserveScroll: true,
  });
};
</script>
