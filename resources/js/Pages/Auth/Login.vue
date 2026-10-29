<template>
  <div class="min-h-screen flex items-center justify-center bg-[#F8FFFD] px-4 font-sans select-none">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-sm border border-[#DDEFEA]">
      <div class="text-center">
        <!-- Application Title with Music Badge -->
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#D1FAE5] text-[#0F766E] mb-3 shadow-xs">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 18V5l12-2v13"></path>
            <circle cx="6" cy="18" r="3"></circle>
            <circle cx="18" cy="16" r="3"></circle>
          </svg>
        </div>
        <h2 class="text-2xl font-black tracking-tight text-[#1F2937]">
          Scala Musical
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
        </div>

        <!-- Submit Button -->
        <div>
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-[#14B8A6] hover:bg-[#0D9488] shadow-xs hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-[#14B8A6]/30 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            <span v-if="form.processing">Iniciando sesión...</span>
            <span v-else>Iniciar Sesión</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

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
});

const submit = () => {
  form.post(buildUrl('/login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>
