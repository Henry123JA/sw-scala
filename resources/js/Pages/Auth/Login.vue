<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 font-sans" :style="{ fontSize: 'var(--font-size-base, 14px)' }">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
      <div class="text-center">
        <!-- Application Title -->
        <h2 class="text-3xl font-extrabold tracking-tight" :style="{ color: 'var(--color-primary)' }">
          Academia de Música
        </h2>
        <p class="mt-2 text-sm text-gray-500">
          Ingresa tus credenciales para acceder al sistema
        </p>
      </div>

      <!-- Alert for general errors -->
      <div v-if="form.errors.general" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
        {{ form.errors.general }}
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="submit">
        <div class="space-y-4">
          <!-- Email Input -->
          <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">
              Correo Electrónico
            </label>
            <input
              id="email"
              type="email"
              required
              v-model="form.email"
              placeholder="ejemplo@academia.com"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-opacity-50 focus:outline-none transition-colors duration-200"
              :class="form.errors.email ? 'border-red-300 focus:ring-red-200' : 'focus:ring-blue-200'"
              :style="{ focusBorderColor: 'var(--color-primary)' }"
            />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 font-medium">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password Input -->
          <div>
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">
              Contraseña
            </label>
            <input
              id="password"
              type="password"
              required
              v-model="form.password"
              placeholder="••••••••"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-opacity-50 focus:outline-none transition-colors duration-200"
              :class="form.errors.password ? 'border-red-300 focus:ring-red-200' : 'focus:ring-blue-200'"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 font-medium">
              {{ form.errors.password }}
            </p>
          </div>
        </div>

        <!-- ─── Remember Me Checkbox (comentado, disponible para futura activación) ───
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember"
              type="checkbox"
              v-model="form.remember"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="remember" class="ml-2 block text-sm text-gray-700 select-none">
              Recordarme en este equipo
            </label>
          </div>
        </div>
        ────────────────────────────────────────────────────────────────── -->

        <!-- Submit Button -->
        <div>
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg text-sm font-bold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            :style="{
              backgroundColor: 'var(--color-primary)',
              color: 'var(--contrast, #ffffff)',
              boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
            }"
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
