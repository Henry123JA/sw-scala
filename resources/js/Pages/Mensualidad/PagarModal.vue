<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl border border-gray-100">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3">
        <h3 class="text-lg font-bold text-gray-900">{{ mostrandoQR ? 'Pago QR' : 'Registrar Pago' }}</h3>
        <button @click="cerrar" class="text-gray-400 hover:text-gray-600">&times;</button>
      </div>

      <div class="mt-4 space-y-4">
        <!-- Inscription info (hide when showing QR) -->
        <div v-if="!mostrandoQR" class="rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
          <p><strong>Estudiante:</strong> {{ inscripcion.alumno?.usuario?.nombres }} {{ inscripcion.alumno?.usuario?.apellidos }}</p>
          <p><strong>Curso:</strong> {{ inscripcion.grupo?.curso?.nombre ?? 'N/A' }}</p>
          <p><strong>Monto mensual:</strong> <span class="font-bold text-blue-600">{{ fmonto(inscripcion.monto_mensual) }} Bs</span></p>
          <p><strong>Vence:</strong> {{ inscripcion.fecha_vencimiento ? new Date(inscripcion.fecha_vencimiento).toLocaleDateString('es-ES') : '-' }}</p>
          <p><strong>Estado:</strong>
            <span :class="{ 'text-green-600': inscripcion.estado === 'ACTIVA', 'text-red-600': inscripcion.estado === 'VENCIDA', 'text-yellow-600': inscripcion.estado === 'PAUSADA' }" class="font-semibold">{{ inscripcion.estado }}</span>
          </p>
        </div>

        <!-- Meses selector (hide when showing QR) -->
        <div v-if="!mostrandoQR">
          <label class="block text-xs font-semibold text-gray-600 mb-1">¿Cuántos meses va a pagar?</label>
          <select v-model.number="meses" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option v-for="n in 12" :key="n" :value="n">{{ n }} mes(es) — {{ fmonto(inscripcion.monto_mensual * n) }} Bs</option>
          </select>
          <p class="mt-1 text-xs text-gray-400">Vencimiento después del pago: {{ fechaFutura }}</p>
        </div>

        <!-- Mode selection -->
        <div v-if="!mostrandoQR" class="flex gap-4">
          <button v-if="userRole !== 'Alumno'" @click="metodo = 'efectivo'"
            class="flex-1 rounded-lg border py-3 text-center text-sm font-semibold transition"
            :class="metodo === 'efectivo' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 hover:bg-gray-50 text-gray-600'">
            💸 Efectivo
          </button>
          <button @click="generarQR"
            class="flex-1 rounded-lg border py-3 text-center text-sm font-semibold transition"
            :class="metodo === 'qr' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 hover:bg-gray-50 text-gray-600'">
            📱 Pago QR
          </button>
        </div>

        <!-- CASH FORM -->
        <div v-if="metodo === 'efectivo' && !mostrandoQR" class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Observaciones</label>
            <textarea v-model="observaciones" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></textarea>
          </div>
          <div class="flex gap-2 justify-end pt-2">
            <button title="Cancelar" @click="cerrar" class="rounded-lg border border-gray-300 px-2 py-2 text-sm text-gray-700 hover:bg-gray-50"><component :is="Lucide.X" class="w-4 h-4" /></button>
            <button @click="pagarEfectivo" :disabled="loading" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
              Confirmar {{ fmonto(inscripcion.monto_mensual * meses) }} Bs
            </button>
          </div>
        </div>

        <!-- QR: Loading -->
        <div v-if="mostrandoQR && loadingQR" class="py-8 flex flex-col items-center justify-center">
          <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
          <p class="mt-2 text-sm text-gray-500">Generando código QR...</p>
        </div>

        <!-- QR: Error -->
        <div v-if="mostrandoQR && errorQR" class="py-8 text-center">
          <p class="text-red-600 font-semibold">❌ {{ errorQR }}</p>
          <button @click="closeQR" class="mt-4 text-sm text-blue-600 hover:underline">Volver</button>
        </div>

        <!-- QR: Active countdown -->
        <div v-if="mostrandoQR && !loadingQR && !errorQR && qrBase64 && secondsLeft > 0" class="space-y-4 text-center">
          <img :src="`data:image/png;base64,${qrBase64}`" alt="Código QR" class="mx-auto h-48 w-48 border border-gray-100 rounded-lg shadow-sm" />
          <p class="text-xs text-gray-500">Escanea este código desde la app de tu banco</p>
          <p class="text-xs text-gray-400">Pagas: {{ fmonto(inscripcion.monto_mensual * meses / 10000) }} Bs (prueba)</p>
          <div class="rounded-full px-4 py-1 text-xs font-semibold bg-yellow-50 text-yellow-700">
            Tiempo: {{ Math.floor(secondsLeft/60) }}:{{ String(secondsLeft%60).padStart(2,'0') }}
          </div>
          <p v-if="verificando" class="text-xs text-gray-400">Verificando pago...</p>
          <button title="Cerrar" @click="closeQR" class="text-sm text-blue-600 hover:underline"><component :is="Lucide.X" class="w-4 h-4" /></button>
        </div>

        <!-- QR: Expired — poller sigue corriendo por si pagó al último segundo -->
        <div v-if="mostrandoQR && !loadingQR && !errorQR && qrBase64 && secondsLeft <= 0" class="space-y-4 text-center">
          <div class="rounded-lg bg-red-50 p-4">
            <p class="text-red-700 font-semibold">⏰ QR Expirado</p>
            <p class="mt-1 text-xs text-gray-500">
              El código QR ya no es válido.
              <span class="mt-1 block text-gray-400">
                Esperando confirmación de pago...
                <span class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-blue-500 border-t-transparent align-middle ml-1"></span>
              </span>
            </p>
          </div>
          <div class="flex gap-2 justify-center">
            <button @click="generarQR" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
              Generar nuevo QR
            </button>
            <button title="Cerrar" @click="closeQR" class="rounded-lg border border-gray-300 px-2 py-2 text-sm text-gray-700 hover:bg-gray-50">
              <component :is="Lucide.X" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const props = defineProps({ show: Boolean, inscripcion: Object, userRole: String });
const emit = defineEmits(['close', 'paid']);

const metodo = ref(props.userRole === 'Alumno' ? 'qr' : 'efectivo');
const meses = ref(1);
const observaciones = ref('');
const loading = ref(false);
const loadingQR = ref(false);
const mostrandoQR = ref(false);
const qrBase64 = ref(null);
const errorQR = ref(null);
const paymentNumber = ref(null);
const secondsLeft = ref(0);
const verificando = ref(false);

let timer = null;
let poller = null;

onUnmounted(() => { clearTimers(); });

function clearTimers() {
  if (timer) { clearInterval(timer); timer = null; }
  if (poller) { clearInterval(poller); poller = null; }
}

const fechaFutura = computed(() => {
  if (!props.inscripcion?.fecha_vencimiento) return '-';
  const d = new Date(props.inscripcion.fecha_vencimiento);
  d.setMonth(d.getMonth() + meses.value);
  return d.toLocaleDateString('es-ES');
});

function fmonto(v) { const n = parseFloat(v); return isNaN(n) ? '0.00' : n.toFixed(2); }

function cerrar() {
  clearTimers();
  emit('close');
}

function closeQR() {
  clearTimers();
  mostrandoQR.value = false;
  qrBase64.value = null;
  errorQR.value = null;
  metodo.value = 'efectivo';
  emit('close');
}

function pagarEfectivo() {
  loading.value = true;
  router.post(buildUrl(`/mensualidades/${props.inscripcion.id}/pagar-efectivo`), {
    meses: meses.value,
    observaciones: observaciones.value,
  }, { onFinish: () => { loading.value = false; cerrar(); emit('paid'); } });
}

async function generarQR() {
  clearTimers(); // limpia cualquier timer/poller previo antes de generar uno nuevo
  metodo.value = 'qr';
  loadingQR.value = true;
  mostrandoQR.value = true;
  errorQR.value = null;

  try {
    const csrf = document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1];
    const resp = await fetch(buildUrl(`/mensualidades/${props.inscripcion.id}/iniciar-pago-qr`), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': csrf ? decodeURIComponent(csrf) : '',
      },
      body: JSON.stringify({ meses: meses.value }),
    });

    const data = await resp.json();
    if (!resp.ok) throw new Error(data.error || data.message || 'Error al generar QR');

    if (data.qrBase64) {
      qrBase64.value = data.qrBase64;
      paymentNumber.value = data.paymentNumber;
      startTimer(data.expira);
      startPolling(data.paymentNumber, data.expira);
    } else {
      errorQR.value = 'No se recibió el código QR.';
    }
  } catch (e) {
    errorQR.value = e.message || 'Error al generar el QR.';
  } finally {
    loadingQR.value = false;
  }
}

function startTimer(expira) {
  clearTimers();
  if (!expira) return;

  const expTime = new Date(expira).getTime();
  if (isNaN(expTime)) {
    console.warn('Fecha de expiración inválida:', expira);
    secondsLeft.value = 120; // fallback: 2 minutos
  } else {
    secondsLeft.value = Math.max(0, Math.floor((expTime - Date.now()) / 1000));
  }

  timer = setInterval(() => {
    if (secondsLeft.value > 0) {
      secondsLeft.value--;
    } else {
      clearInterval(timer);
      timer = null;
    }
  }, 1000);
}

function startPolling(pn, expira) {
  if (!pn) return;

  // Calcular intentos según la expiración real del QR + 30s de gracia
  // Así no seguimos polling 10 min cuando el QR expira en 2 min
  let pollAttempts = 0;
  const remainingSeconds = expira ? Math.max(0, Math.floor((new Date(expira).getTime() - Date.now()) / 1000)) : 120;
  const MAX_POLL_ATTEMPTS = Math.ceil((remainingSeconds + 30) / 15);

  poller = setInterval(async () => {
    pollAttempts++;
    try {
      const resp = await fetch(buildUrl(`/api/mensualidades/verificar-qr/${pn}`), {
        headers: { 'Accept': 'application/json' },
      });
      if (!resp.ok) {
        // Stop polling if payment not found or max attempts reached
        if (resp.status === 404 || pollAttempts >= MAX_POLL_ATTEMPTS) {
          clearTimers();
        }
        return;
      }
      const data = await resp.json();
      if (data.estado === 'PAGADO') {
        clearTimers();
        closeQR();
        emit('paid');
        setTimeout(() => {
          router.visit(buildUrl('/mensualidades'), {
            data: { qr_success: 1 }
          });
        }, 600);
      }
    } catch (e) {
      // Silencio — la red puede fallar temporalmente
    }
  }, 15000);
}
</script>
