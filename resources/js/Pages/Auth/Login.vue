<template>
  <div class="min-h-screen flex items-center justify-center bg-[#F8FFFD] px-4 font-sans select-none">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-sm border border-[#DDEFEA]">
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

      <!-- Alert for general errors -->
      <div v-if="form.errors.general" class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600 font-semibold">
        {{ form.errors.general }}
      </div>

      <form class="mt-6 space-y-5" @submit.prevent="submit">
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
            <label for="password" class="block text-xs font-bold text-[#1F2937] mb-1 uppercase tracking-wider">
              Contraseña
            </label>
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
              <span>Primer inicio de sesión (verificar Políticas y Términos)</span>
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
