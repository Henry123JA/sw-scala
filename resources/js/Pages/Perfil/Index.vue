<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-black text-[#1F2937] tracking-tight">Mi Perfil</h1>
          <p class="text-xs text-[#6B7280] mt-0.5">Administra tu información personal y foto de cuenta</p>
        </div>
        <span class="badge-menta">Scala Musical</span>
      </div>

      <!-- Photo card (top) -->
      <div class="bg-white p-6 rounded-2xl border border-[#DDEFEA] shadow-xs space-y-4">
        <h2 class="text-base font-extrabold text-[#1F2937]">Foto de perfil</h2>
        <p class="text-xs text-[#6B7280]">Sube una imagen JPG o PNG de hasta 2MB. Se redimensionará automáticamente a 200x200 píxeles.</p>

        <div v-if="flashSuccess" class="rounded-xl bg-[#D1FAE5] border border-[#6EE7B7] px-4 py-3 text-xs font-semibold text-[#0F766E]">
          {{ flashSuccess }}
        </div>
        <div v-if="photoError" class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs font-semibold text-red-700">
          {{ photoError }}
        </div>

        <div class="flex items-center gap-6">
          <!-- Avatar (preview or current) -->
          <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-[#DDEFEA] bg-[#F0FDF9] flex items-center justify-center shrink-0 ring-4 ring-[#D1FAE5]">
            <img
              v-if="previewUrl || currentFotoUrl"
              :src="previewUrl || currentFotoUrl"
              alt="Foto de perfil"
              class="w-full h-full object-cover"
              data-testid="photo-avatar"
            />
            <span v-else class="text-2xl font-black text-[#0F766E]" data-testid="photo-initials">
              {{ userInitials }}
            </span>
          </div>

          <div class="flex-1 space-y-3">
            <!-- Drag & drop area -->
            <div
              class="border-2 border-dashed rounded-xl px-4 py-6 text-center cursor-pointer transition-colors"
              :class="isDragging
                ? 'border-[#14B8A6] bg-[#F0FDF9]'
                : 'border-[#DDEFEA] hover:border-[#6EE7B7] bg-[#F8FFFD]'"
              @click="triggerFileInput"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="onDrop"
              data-testid="dropzone"
            >
              <p class="text-sm font-semibold text-[#1F2937]">
                Arrastra una imagen o haz clic para seleccionar
              </p>
              <p class="text-xs text-[#6B7280] mt-1">JPG o PNG · máximo 2MB</p>
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
                class="rounded-xl border border-[#DDEFEA] px-4 py-2 text-xs font-bold text-[#1F2937] hover:bg-[#F0FDF9] transition cursor-pointer"
                data-testid="pick-button"
              >
                Seleccionar imagen
              </button>
              <button
                type="button"
                @click="submitPhoto"
                :disabled="!previewUrl || isProcessing"
                class="rounded-xl bg-[#14B8A6] px-4 py-2 text-xs font-bold text-white hover:bg-[#0D9488] shadow-xs disabled:opacity-50 transition cursor-pointer"
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
        <div class="md:col-span-2 bg-white p-6 rounded-2xl border border-[#DDEFEA] shadow-xs space-y-4">
          <h2 class="text-base font-extrabold text-[#1F2937]">Información Personal</h2>
          <p class="text-xs text-[#6B7280]">Actualiza tus nombres, correo y contraseña de acceso.</p>

          <form @submit.prevent="submitProfile" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-[#1F2937] uppercase tracking-wider mb-1">Nombres</label>
                <input
                  v-model="profileForm.nombres"
                  type="text"
                  class="w-full rounded-xl border border-[#DDEFEA] px-3.5 py-2.5 text-sm shadow-2xs focus:border-[#14B8A6] focus:ring-2 focus:ring-[#14B8A6]/20 focus:outline-none"
                  required
                />
                <span v-if="profileForm.errors.nombres" class="text-xs text-red-500 mt-1 block">{{ profileForm.errors.nombres }}</span>
              </div>

              <div>
                <label class="block text-xs font-bold text-[#1F2937] uppercase tracking-wider mb-1">Apellidos</label>
                <input
                  v-model="profileForm.apellidos"
                  type="text"
                  class="w-full rounded-xl border border-[#DDEFEA] px-3.5 py-2.5 text-sm shadow-2xs focus:border-[#14B8A6] focus:ring-2 focus:ring-[#14B8A6]/20 focus:outline-none"
                  required
                />
                <span v-if="profileForm.errors.apellidos" class="text-xs text-red-500 mt-1 block">{{ profileForm.errors.apellidos }}</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-[#1F2937] uppercase tracking-wider mb-1">Correo Electrónico</label>
              <input
                v-model="profileForm.email"
                type="email"
                class="w-full rounded-xl border border-[#DDEFEA] px-3.5 py-2.5 text-sm shadow-2xs focus:border-[#14B8A6] focus:ring-2 focus:ring-[#14B8A6]/20 focus:outline-none"
                required
              />
              <span v-if="profileForm.errors.email" class="text-xs text-red-500 mt-1 block">{{ profileForm.errors.email }}</span>
            </div>

            <hr class="border-[#DDEFEA]" />

            <div>
              <label class="block text-xs font-bold text-[#1F2937] uppercase tracking-wider mb-1">
                Nueva Contraseña <span class="text-[11px] font-normal text-[#6B7280]">(Opcional: Dejar vacío para mantener la actual)</span>
              </label>
              <input
                v-model="profileForm.password"
                type="password"
                placeholder="Nueva contraseña..."
                class="w-full rounded-xl border border-[#DDEFEA] px-3.5 py-2.5 text-sm shadow-2xs focus:border-[#14B8A6] focus:ring-2 focus:ring-[#14B8A6]/20 focus:outline-none"
              />
              <span v-if="profileForm.errors.password" class="text-xs text-red-500 mt-1 block">{{ profileForm.errors.password }}</span>
            </div>

            <div class="flex justify-end pt-2">
              <button
                type="submit"
                :disabled="profileForm.processing"
                class="rounded-xl bg-[#14B8A6] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#0D9488] shadow-xs disabled:opacity-50 transition cursor-pointer"
              >
                {{ profileForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Account card -->
        <div class="bg-white p-6 rounded-2xl border border-[#DDEFEA] shadow-xs space-y-4 flex flex-col justify-between">
          <div>
            <h2 class="text-base font-extrabold text-[#1F2937]">Mi Cuenta</h2>
            <div class="mt-4 space-y-3">
              <div class="text-xs text-[#6B7280] flex justify-between items-center py-1 border-b border-[#DDEFEA]">
                <span>Rol Asignado:</span>
                <span class="font-extrabold text-[#0F766E] bg-[#D1FAE5] px-2 py-0.5 rounded-full">{{ $page.props.auth?.rol }}</span>
              </div>
              <div class="text-xs text-[#6B7280] flex justify-between items-center py-1 border-b border-[#DDEFEA]">
                <span>CI / Documento:</span>
                <span class="font-mono font-bold text-[#1F2937]">{{ user.ci }}</span>
              </div>
              <div class="text-xs text-[#6B7280] flex justify-between items-center py-1">
                <span>Tema Visual:</span>
                <span class="font-semibold text-[#0F766E]">Nota Menta Claro</span>
              </div>
            </div>
          </div>

          <div class="text-[11px] text-[#6B7280] border-t border-[#DDEFEA] pt-4">
            Registrado desde: {{ new Date(user.created_at).toLocaleDateString('es-ES') }}
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

const props = defineProps({
  user: Object,
});

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const page = usePage();
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
</script>
