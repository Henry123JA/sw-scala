<template>
  <div class="min-h-screen flex items-center justify-center bg-[#F8FFFD] px-4 py-8 font-sans select-none">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl shadow-sm border border-[#DDEFEA]">
      <div class="text-center">
        <!-- Application Title with Official SCALA Image -->
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white p-1 border-2 border-[#14B8A6]/40 mb-3 shadow-sm overflow-hidden">
          <img
            :src="buildUrl('/images/academia-scala.jpg')"
            alt="Academia de Música SCALA"
            class="w-full h-full object-cover rounded-xl"
          />
        </div>
        <h2 class="text-2xl font-black tracking-tight text-[#1F2937]">
          Academia de Música SCALA
        </h2>
        <p class="mt-1 text-xs text-[#6B7280]">
          Ingresa tus credenciales para acceder al sistema académico
        </p>
      </div>

      <!-- Flash Success Alert -->
      <div
        v-if="$page.props.flash?.success"
        class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-700 font-semibold"
      >
        {{ $page.props.flash.success }}
      </div>

      <!-- Alert for general errors -->
      <div
        v-if="form.errors.general || $page.props.errors?.general"
        class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600 font-semibold"
      >
        {{ form.errors.general || $page.props.errors?.general }}
      </div>

      <!-- Botón Oficial: Continuar con Google (OAuth 2.0) -->
      <div>
        <a
          :href="buildUrl('/auth/google/redirect')"
          class="w-full flex items-center justify-center gap-3 py-2.5 px-4 rounded-xl text-sm font-bold text-[#1F2937] bg-white hover:bg-[#F0FDF9] border border-[#DDEFEA] shadow-2xs hover:border-[#14B8A6] transition-all cursor-pointer"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24">
            <path
              fill="#4285F4"
              d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
              fill="#34A853"
              d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
              fill="#FBBC05"
              d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
            />
            <path
              fill="#EA4335"
              d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
            />
          </svg>
          <span>Continuar con cuenta de Google</span>
        </a>
      </div>

      <div class="relative flex items-center justify-center">
        <div class="border-t border-[#DDEFEA] w-full"></div>
        <span class="bg-white px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
          o con tu correo
        </span>
        <div class="border-t border-[#DDEFEA] w-full"></div>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-4">
          <!-- Email Input -->
          <div>
            <label for="email" class="block text-xs font-bold text-[#1F2937] mb-1 uppercase tracking-wider">
              Correo Electrónico
            </label>
            <input
              id="email"
              type="email"
              required
              v-model="form.email"
              placeholder="ejemplo@academia.com"
              class="w-full px-3.5 py-2.5 bg-white border border-[#DDEFEA] rounded-xl text-sm text-[#1F2937] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#14B8A6]/20 focus:border-[#14B8A6] transition-colors"
              :class="form.errors.email ? 'border-red-300 focus:ring-red-100' : ''"
            />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 font-medium">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password Input -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="password" class="block text-xs font-bold text-[#1F2937] uppercase tracking-wider">
                Contraseña
              </label>
              <Link
                :href="buildUrl(`/password/codigo${form.email ? '?email=' + encodeURIComponent(form.email) : ''}`)"
                class="text-xs font-bold text-[#0F766E] hover:underline"
              >
                ¿Cambiar contraseña con código al correo?
              </Link>
            </div>
            <input
              id="password"
              type="password"
              required
              v-model="form.password"
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 bg-white border border-[#DDEFEA] rounded-xl text-sm text-[#1F2937] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#14B8A6]/20 focus:border-[#14B8A6] transition-colors"
              :class="form.errors.password ? 'border-red-300 focus:ring-red-100' : ''"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 font-medium">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Opción de Primer Inicio de Sesión (Onboarding Legal Fig. 7.9 a 7.14) -->
          <div class="pt-1">
            <label class="flex items-center gap-2 text-xs text-[#6B7280] cursor-pointer">
              <input
                type="checkbox"
                v-model="form.primer_ingreso"
                class="w-3.5 h-3.5 accent-[#14B8A6] rounded cursor-pointer"
              />
              <span>Primer inicio de sesión (verificar Políticas, Términos y envío de correos)</span>
            </label>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="space-y-3">
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-[#14B8A6] hover:bg-[#0D9488] shadow-xs hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-[#14B8A6]/30 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            <span v-if="form.processing">Iniciando sesión...</span>
            <span v-else>Iniciar Sesión</span>
          </button>

          <div class="text-center pt-2 border-t border-[#DDEFEA] flex items-center justify-between text-xs text-[#6B7280]">
            <Link :href="buildUrl('/')" class="text-[#0F766E] font-bold hover:underline">
              ← Volver al portal corporativo
            </Link>
            <div class="space-x-2">
              <Link :href="buildUrl('/politica-privacidad')" class="hover:text-[#0F766E] hover:underline">
                Privacidad
              </Link>
              <span>·</span>
              <Link :href="buildUrl('/terminos-condiciones')" class="hover:text-[#0F766E] hover:underline">
                Términos
              </Link>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const form = useForm({
  email: '',
  password: '',
  remember: false,
  primer_ingreso: false,
});

const submit = () => {
  form.post(buildUrl('/login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>
