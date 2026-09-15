<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Mi Perfil</h1>

      <!-- Photo card (top) -->
      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
        <h2 class="text-lg font-bold text-gray-800">Foto de perfil</h2>
        <p class="text-xs text-gray-500">Sube una imagen JPG o PNG de hasta 2MB. Se redimensionará automáticamente a 200x200.</p>

        <div v-if="flashSuccess" class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
          {{ flashSuccess }}
        </div>
        <div v-if="photoError" class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
          {{ photoError }}
        </div>

        <div class="flex items-center gap-6">
          <!-- Avatar (preview or current) -->
          <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-gray-200 bg-gray-100 flex items-center justify-center">
            <img
              v-if="previewUrl || currentFotoUrl"
              :src="previewUrl || currentFotoUrl"
              alt="Foto de perfil"
              class="w-full h-full object-cover"
              data-testid="photo-avatar"
            />
            <span v-else class="text-2xl font-bold text-gray-500" data-testid="photo-initials">
              {{ userInitials }}
            </span>
          </div>

          <div class="flex-1 space-y-3">
            <!-- Drag & drop area -->
            <div
              class="border-2 border-dashed rounded-lg px-4 py-6 text-center cursor-pointer transition-colors"
              :class="isDragging
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-300 hover:border-gray-400 bg-gray-50'"
              @click="triggerFileInput"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="onDrop"
              data-testid="dropzone"
            >
              <p class="text-sm text-gray-600">
                Arrastra una imagen o haz clic para seleccionar
              </p>
              <p class="text-xs text-gray-400 mt-1">JPG o PNG · máximo 2MB</p>
            </div>

            <input
              ref="fileInput"
              type="file"
              accept="image/jpeg,image/png"
              class="hidden"
              @change="onFileChange"
              data-testid="file-input"
            />

            <div class="flex justify-end gap-2">
              <button
                type="button"
                @click="triggerFileInput"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                data-testid="pick-button"
              >
                Seleccionar imagen
              </button>
              <button
                type="button"
                @click="submitPhoto"
                :disabled="!previewUrl || isProcessing"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm disabled:opacity-50"
                data-testid="upload-button"
              >
                {{ isProcessing ? 'Cargando...' : 'Subir foto' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Info Form -->
        <div class="md:col-span-2 bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
          <h2 class="text-lg font-bold text-gray-800">Actualizar Información Personal</h2>
          <p class="text-xs text-gray-500">Actualiza tus nombres, correo y contraseña.</p>

          <form @submit.prevent="submitProfile" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Nombres</label>
                <input
                  v-model="profileForm.nombres"
                  type="text"
                  class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                  required
                />
                <span v-if="profileForm.errors.nombres" class="text-xs text-red-500 mt-1 block">{{ profileForm.errors.nombres }}</span>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Apellidos</label>
                <input
                  v-model="profileForm.apellidos"
                  type="text"
                  class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                  required
                />
                <span v-if="profileForm.errors.apellidos" class="text-xs text-red-500 mt-1 block">{{ profileForm.errors.apellidos }}</span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-600 mb-1">Correo Electrónico</label>
              <input
                v-model="profileForm.email"
                type="email"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                required
              />
              <span v-if="profileForm.errors.email" class="text-xs text-red-500 mt-1 block">{{ profileForm.errors.email }}</span>
            </div>

            <hr class="border-gray-100" />

            <div>
              <label class="block text-sm font-semibold text-gray-600 mb-1">Nueva Contraseña (Dejar vacío para mantener la actual)</label>
              <input
                v-model="profileForm.password"
                type="password"
                placeholder="Nueva contraseña..."
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
              />
              <span v-if="profileForm.errors.password" class="text-xs text-red-500 mt-1 block">{{ profileForm.errors.password }}</span>
            </div>

            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="profileForm.processing"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm disabled:opacity-50"
              >
                {{ profileForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Account card -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4 flex flex-col justify-between">
          <div>
            <h2 class="text-lg font-bold text-gray-800">Mi Cuenta</h2>
            <div class="mt-4 space-y-2">
              <div class="text-sm text-gray-600 flex justify-between">
                <span>Rol de Usuario:</span>
                <span class="font-bold text-gray-900">{{ $page.props.auth?.rol }}</span>
              </div>
              <div class="text-sm text-gray-600 flex justify-between">
                <span>CI / Documento:</span>
                <span class="font-mono">{{ user.ci }}</span>
              </div>
            </div>
          </div>

          <div class="text-xs text-gray-400 border-t pt-4">
            Registrado desde: {{ new Date(user.created_at).toLocaleDateString('es-ES') }}
          </div>
        </div>
      </div>

      <!-- Theme Selector -->
      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-6">
        <div>
          <h2 class="text-lg font-bold text-gray-800">Personalización del Tema</h2>
          <p class="text-xs text-gray-500 mt-1">Selecciona una de las 3 categorías disponibles para personalizar la interfaz y el tamaño del texto.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
          <div
            v-for="tema in temas"
            :key="tema.id"
            class="relative rounded-xl border-2 p-4 cursor-pointer hover:shadow-md transition-all space-y-3 flex flex-col justify-between"
            :class="user.tema_id === tema.id ? 'border-blue-600 ring-2 ring-blue-100 bg-blue-50/20' : 'border-gray-200 hover:border-gray-300'"
            @click="selectTheme(tema)"
            data-testid="theme-card"
          >
            <span
              v-if="user.tema_id === tema.id"
              class="absolute top-3 right-3 bg-blue-600 text-white p-1 rounded-full text-[10px] font-bold"
            >
              ✓
            </span>

            <div>
              <span class="block font-bold text-gray-900 capitalize" data-testid="theme-name">
                {{ formatThemeName(tema.nombre) }}
              </span>
              <span class="inline-block text-[10px] uppercase font-bold text-gray-400 tracking-wider">
                Modo automático
              </span>
              <div class="text-xs text-gray-500 mt-1" data-testid="theme-font-size">
                Tamaño: {{ tema.font_size }}
              </div>
            </div>

            <!-- Day / night swatches (sourced from DB) -->
            <div class="flex items-center gap-2">
              <div class="flex flex-col items-center gap-1" title="Día">
                <div
                  class="w-6 h-6 rounded-full border border-gray-300/50 shadow-sm"
                  :style="{ backgroundColor: tema.color_primario_dia }"
                  data-testid="swatch-dia"
                ></div>
                <span class="text-[9px] text-gray-500">Día</span>
              </div>
              <div class="flex flex-col items-center gap-1" title="Noche">
                <div
                  class="w-6 h-6 rounded-full border border-gray-300/50 shadow-sm"
                  :style="{ backgroundColor: tema.color_primario_noche }"
                  data-testid="swatch-noche"
                ></div>
                <span class="text-[9px] text-gray-500">Noche</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePhotoUpload } from '@/Composables/usePhotoUpload';
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
  user: Object,
  temas: Array,
});

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const page = usePage();
const { saveTheme } = useTheme();
const { previewUrl, isProcessing, error: photoUploadError, resize, setPreviewFromFile, clear: clearPreview } = usePhotoUpload();

const fileInput = ref(null);
const isDragging = ref(false);
const photoError = ref(null);
const isUploading = ref(false);

const flashSuccess = computed(() => page.props.flash?.success);
const userInitials = computed(() => {
  const n = (props.user?.nombres?.[0] || '').toUpperCase();
  const a = (props.user?.apellidos?.[0] || '').toUpperCase();
  return n + a;
});
const currentFotoUrl = computed(() => {
  // Only use shared prop after upload success (to avoid stale value)
  return page.props.auth?.user?.foto_url || null;
});

const profileForm = useForm({
  nombres: props.user.nombres,
  apellidos: props.user.apellidos,
  email: props.user.email,
  password: '',
});

function submitProfile() {
  profileForm.put(buildUrl('/perfil'), {
    preserveScroll: true,
    onSuccess: () => {
      profileForm.password = '';
    },
  });
}

function triggerFileInput() {
  fileInput.value?.click();
}

async function onFileChange(event) {
  const file = event.target.files?.[0];
  if (file) await processFile(file);
}

async function onDrop(event) {
  isDragging.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file) await processFile(file);
}

async function processFile(file) {
  photoError.value = null;
  setPreviewFromFile(file);
  try {
    await resize(file);
  } catch (e) {
    photoError.value = e.message;
    clearPreview();
  }
}

function submitPhoto() {
  if (!previewUrl.value) return;
  isUploading.value = true;
  photoError.value = null;

  // Resize again to ensure we have a Blob (in case preview-only)
  // The preview is set from the file; we need a fresh Blob.
  const fileInputEl = fileInput.value;
  if (!fileInputEl || !fileInputEl.files?.[0]) {
    photoError.value = 'Selecciona una imagen primero.';
    isUploading.value = false;
    return;
  }

  resize(fileInputEl.files[0])
    .then((blob) => {
      const fd = new FormData();
      fd.append('foto', blob, 'avatar.jpg');
      fd.append('_method', 'POST');
      router.post(buildUrl('/perfil/foto'), fd, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          clearPreview();
          if (fileInputEl) fileInputEl.value = '';
        },
        onError: (errors) => {
          photoError.value = errors?.foto?.[0] || 'Error al subir la foto.';
        },
        onFinish: () => {
          isUploading.value = false;
        },
      });
    })
    .catch((e) => {
      photoError.value = e.message || 'Error al subir la foto.';
      isUploading.value = false;
    });
}

function selectTheme(tema) {
  saveTheme(tema.id);
}

function formatThemeName(name) {
  // Bare category: 'ninos' → 'Niños', 'jovenes' → 'Jóvenes', 'adultos' → 'Adultos'
  const map = { ninos: 'Niños', jovenes: 'Jóvenes', adultos: 'Adultos' };
  const key = (name || '').toLowerCase().replace(/-(dia|noche)$/i, '').trim();
  return map[key] || name;
}
</script>
