<template>
  <Teleport to="body">
    <div v-if="show && inscripcion" class="modal-overlay" @click.self="cerrarModal">
      <div class="modal-card">
        <!-- Header -->
        <div class="modal-header">
          <div class="modal-title-wrap">
            <span class="modal-icon">💳</span>
            <div>
              <h3 class="modal-title">Pago de Matrícula / Inscripción</h3>
              <p class="modal-subtitle">
                {{ inscripcion.alumno?.usuario?.nombres }} {{ inscripcion.alumno?.usuario?.apellidos }}
                — {{ inscripcion.grupo?.curso?.nombre }} ({{ inscripcion.grupo?.nombre }})
              </p>
            </div>
          </div>
          <button type="button" class="close-btn" @click="cerrarModal">✕</button>
        </div>

        <!-- Tabs: QR PagoFácil vs Efectivo (Efectivo solo para Propietario/Secretaria) -->
        <div class="payment-tabs" v-if="!pagoCompletado">
          <button
            type="button"
            class="tab-btn"
            :class="{ active: metodoActivo === 'QR' }"
            @click="cambiarTab('QR')"
          >
            📱 Pago con QR (PagoFácil)
          </button>
          <button
            v-if="puedeCobrarEfectivo"
            type="button"
            class="tab-btn"
            :class="{ active: metodoActivo === 'EFECTIVO' }"
            @click="cambiarTab('EFECTIVO')"
          >
            💵 Cobro en Efectivo (Caja)
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Resumen de monto -->
          <div class="amount-box" v-if="!pagoCompletado">
            <div>
              <span class="amount-label">Concepto</span>
              <div class="amount-concept">Matrícula Académica SCALA #{{ inscripcion.id }}</div>
            </div>
            <div class="amount-right">
              <span class="amount-label">Monto (Bs.)</span>
              <div v-if="puedeCobrarEfectivo" class="amount-input-wrap">
                <input
                  type="number"
                  step="0.50"
                  min="1"
                  v-model="montoEditable"
                  class="amount-input"
                />
              </div>
              <div v-else class="amount-value">Bs. {{ Number(montoEditable).toFixed(2) }}</div>
            </div>
          </div>

          <!-- Estado: PAGO COMPLETADO -->
          <div v-if="pagoCompletado" class="paid-success-box">
            <div class="paid-icon">✅</div>
            <h4 class="paid-title">¡Pago de Inscripción Confirmado!</h4>
            <p class="paid-desc">
              La matrícula del alumno ha quedado registrada como <strong>PAGADA</strong>.
            </p>
            <div class="receipt-pill" v-if="reciboConfirmado || inscripcion.numero_recibo">
              Comprobante: <strong>{{ reciboConfirmado || inscripcion.numero_recibo }}</strong>
            </div>
            <button type="button" class="btn-primary-full" @click="cerrarModal">
              Finalizar y Cerrar
            </button>
          </div>

          <!-- TAB 1: PAGO CON QR PAGOFÁCIL -->
          <div v-else-if="metodoActivo === 'QR'" class="qr-section">
            <!-- Cargando QR -->
            <div v-if="generandoQR" class="qr-loading">
              <div class="spinner"></div>
              <p>Conectando con PagoFácil MasterQR Bolivia...</p>
            </div>

            <!-- QR Generado y activo -->
            <div v-else-if="inscripcion.pagofacil_qr_base64 && !qrExpirado" class="qr-ready">
              <div class="qr-frame">
                <img
                  :src="'data:image/png;base64,' + inscripcion.pagofacil_qr_base64"
                  alt="Código QR PagoFácil SCALA"
                  class="qr-image"
                />
              </div>

              <div class="qr-meta">
                <div class="qr-badge-live">
                  <span class="pulse-dot"></span>
                  Esperando confirmación bancaria automática...
                </div>
                <div class="qr-timer" v-if="tiempoRestante">
                  ⏱ Expira en: <strong>{{ tiempoRestante }}</strong>
                </div>
                <div class="qr-ref" v-if="inscripcion.pagofacil_payment_number">
                  Ref: <code>{{ inscripcion.pagofacil_payment_number }}</code>
                </div>
              </div>

              <div class="qr-actions">
                <button
                  type="button"
                  class="btn-outline-sm"
                  :disabled="verificandoManual"
                  @click="verificarAhora"
                >
                  {{ verificandoManual ? 'Verificando...' : '🔄 Verificar estado ahora' }}
                </button>
                <button
                  type="button"
                  class="btn-outline-sm"
                  :disabled="generandoQR"
                  @click="solicitarQR(true)"
                >
                  🔁 Regenerar QR
                </button>
              </div>
            </div>

            <!-- QR Expirado o aún no generado -->
            <div v-else class="qr-empty">
              <div class="qr-placeholder-icon">📲</div>
              <p v-if="qrExpirado" class="qr-expired-msg">
                El código QR anterior ha expirado. Genera uno nuevo para continuar.
              </p>
              <p v-else class="qr-helper-msg">
                Genera un código QR interoperable (Simple QR Bolivia) para pagar desde cualquier aplicación bancaria.
              </p>
              <button
                type="button"
                class="btn-qr-generate"
                :disabled="generandoQR"
                @click="solicitarQR(true)"
              >
                ⚡ Generar Código QR PagoFácil
              </button>
            </div>
          </div>

          <!-- TAB 2: PAGO EN EFECTIVO -->
          <div v-else-if="metodoActivo === 'EFECTIVO' && puedeCobrarEfectivo" class="cash-section">
            <div class="cash-info">
              <p>
                Al confirmar el cobro en ventanilla, el sistema emitirá automáticamente el número de recibo correlativo
                (<code>REC-INS-{{ new Date().getFullYear() }}-XXXX</code>) y marcará la matrícula como <strong>PAGADO</strong>.
              </p>
            </div>
            <button
              type="button"
              class="btn-cash-confirm"
              :disabled="procesandoEfectivo"
              @click="confirmarPagoEfectivo"
            >
              {{ procesandoEfectivo ? 'Registrando cobro...' : `💵 Confirmar Cobro de Bs. ${Number(montoEditable).toFixed(2)}` }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const props = defineProps({
  show: { type: Boolean, default: false },
  inscripcion: { type: Object, default: null },
});

const emit = defineEmits(['close', 'paid']);

const page = usePage();
const puedeCobrarEfectivo = computed(() => {
  const rol = page.props.auth?.rol || page.props.auth?.user?.rol;
  return rol === 'Propietario' || rol === 'Secretaria';
});

const metodoActivo = ref('QR');
const montoEditable = ref(150.00);
const generandoQR = ref(false);
const procesandoEfectivo = ref(false);
const verificandoManual = ref(false);
const pagoCompletado = ref(false);
const reciboConfirmado = ref(null);
const qrExpirado = ref(false);
const tiempoRestante = ref('');

let pollingInterval = null;
let countdownInterval = null;

const limpiarTimers = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
  if (countdownInterval) {
    clearInterval(countdownInterval);
    countdownInterval = null;
  }
};

const iniciarCountdown = () => {
  if (countdownInterval) clearInterval(countdownInterval);

  const actualizarReloj = () => {
    if (!props.inscripcion?.pagofacil_qr_expira) {
      tiempoRestante.value = '';
      return;
    }
    const expiraMs = new Date(props.inscripcion.pagofacil_qr_expira).getTime();
    const diff = expiraMs - Date.now();

    if (diff <= 0) {
      qrExpirado.value = true;
      tiempoRestante.value = 'Expirado';
      limpiarTimers();
      return;
    }

    qrExpirado.value = false;
    const totalSecs = Math.floor(diff / 1000);
    const mins = Math.floor(totalSecs / 60);
    const secs = totalSecs % 60;
    tiempoRestante.value = `${mins}m ${secs < 10 ? '0' : ''}${secs}s`;
  };

  actualizarReloj();
  countdownInterval = setInterval(actualizarReloj, 1000);
};

const iniciarPolling = () => {
  if (pollingInterval) clearInterval(pollingInterval);
  if (!props.inscripcion?.pagofacil_payment_number) return;

  pollingInterval = setInterval(async () => {
    await consultarEstadoPago();
  }, 5000);
};

const consultarEstadoPago = async () => {
  const paymentNumber = props.inscripcion?.pagofacil_payment_number;
  if (!paymentNumber) return;

  try {
    const response = await fetch(buildUrl(`/api/inscripciones/verificar-qr/${paymentNumber}`), {
      headers: { Accept: 'application/json' },
    });
    if (!response.ok) return;
    const data = await response.json();
    if (data.estado === 'PAGADO') {
      pagoCompletado.value = true;
      reciboConfirmado.value = data.recibo;
      limpiarTimers();
      router.reload({ only: ['inscripciones', 'inscripcion'] });
      emit('paid', data);
    }
  } catch (e) {
    // Silenciar errores transitorios de polling
  }
};

const verificarAhora = async () => {
  verificandoManual.value = true;
  await consultarEstadoPago();
  verificandoManual.value = false;
};

const solicitarQR = (regenerar = false) => {
  if (!props.inscripcion) return;
  generandoQR.value = true;

  router.post(
    buildUrl(`/inscripciones/${props.inscripcion.id}/iniciar-pago-qr`),
    {
      monto_inscripcion: montoEditable.value,
      regenerar: regenerar,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        generandoQR.value = false;
        iniciarCountdown();
        iniciarPolling();
      },
    }
  );
};

const confirmarPagoEfectivo = () => {
  if (!props.inscripcion) return;
  procesandoEfectivo.value = true;

  router.post(
    buildUrl(`/inscripciones/${props.inscripcion.id}/pagar-efectivo`),
    {
      monto_inscripcion: montoEditable.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        pagoCompletado.value = true;
        limpiarTimers();
        emit('paid');
      },
      onFinish: () => {
        procesandoEfectivo.value = false;
      },
    }
  );
};

const cambiarTab = (tab) => {
  metodoActivo.value = tab;
  if (tab === 'QR' && !props.inscripcion?.pagofacil_qr_base64 && !pagoCompletado.value) {
    solicitarQR(false);
  }
};

const cerrarModal = () => {
  limpiarTimers();
  emit('close');
};

watch(
  () => [props.show, props.inscripcion],
  ([abierto, ins]) => {
    limpiarTimers();
    if (!abierto || !ins) return;

    montoEditable.value = Number(ins.monto_inscripcion || 150.00).toFixed(2);
    pagoCompletado.value = ins.estado_pago === 'PAGADO';
    reciboConfirmado.value = ins.numero_recibo || null;

    if (pagoCompletado.value) return;

    if (metodoActivo.value === 'QR') {
      const tieneQRVigente =
        ins.pagofacil_qr_base64 &&
        ins.pagofacil_qr_expira &&
        new Date(ins.pagofacil_qr_expira).getTime() > Date.now();

      if (tieneQRVigente) {
        qrExpirado.value = false;
        iniciarCountdown();
        iniciarPolling();
      } else {
        solicitarQR(false);
      }
    }
  },
  { immediate: true }
);

onUnmounted(() => {
  limpiarTimers();
});
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.72);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}
.modal-card {
  background: var(--bg-card, #181b23);
  border: 1px solid var(--border-color, #2d3240);
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.55);
  overflow: hidden;
  color: var(--text-primary, #f3f4f6);
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.15rem 1.35rem;
  border-bottom: 1px solid var(--border-color, #2d3240);
  background: rgba(255, 255, 255, 0.02);
}
.modal-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.modal-icon {
  font-size: 1.6rem;
}
.modal-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
}
.modal-subtitle {
  margin: 0.15rem 0 0;
  font-size: 0.8rem;
  color: var(--text-secondary, #9ca3af);
}
.close-btn {
  background: transparent;
  border: none;
  color: var(--text-secondary, #9ca3af);
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.3rem 0.5rem;
  border-radius: 8px;
}
.close-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
}
.payment-tabs {
  display: flex;
  border-bottom: 1px solid var(--border-color, #2d3240);
  background: rgba(0, 0, 0, 0.18);
}
.tab-btn {
  flex: 1;
  padding: 0.8rem 0.75rem;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--text-secondary, #9ca3af);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.tab-btn.active {
  color: var(--accent-primary, #d97706);
  border-bottom-color: var(--accent-primary, #d97706);
  background: rgba(217, 119, 6, 0.08);
}
.modal-body {
  padding: 1.25rem 1.35rem 1.5rem;
}
.amount-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-color, #2d3240);
  border-radius: 12px;
  padding: 0.85rem 1rem;
  margin-bottom: 1.15rem;
}
.amount-label {
  display: block;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-secondary, #9ca3af);
}
.amount-concept {
  font-size: 0.9rem;
  font-weight: 600;
  margin-top: 0.15rem;
}
.amount-right {
  text-align: right;
}
.amount-value {
  font-size: 1.2rem;
  font-weight: 800;
  color: #10b981;
}
.amount-input {
  width: 105px;
  padding: 0.35rem 0.55rem;
  border-radius: 8px;
  border: 1px solid var(--border-color, #374151);
  background: var(--bg-input, #111319);
  color: #10b981;
  font-weight: 700;
  font-size: 1rem;
  text-align: right;
}
.qr-section {
  text-align: center;
}
.qr-loading {
  padding: 2.2rem 1rem;
  color: var(--text-secondary, #9ca3af);
}
.spinner {
  width: 38px;
  height: 38px;
  border: 3px solid rgba(217, 119, 6, 0.25);
  border-top-color: #d97706;
  border-radius: 50%;
  animation: spin 0.85s linear infinite;
  margin: 0 auto 0.85rem;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.qr-frame {
  display: inline-block;
  background: #ffffff;
  padding: 0.85rem;
  border-radius: 14px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
  margin-bottom: 0.9rem;
}
.qr-image {
  width: 230px;
  height: 230px;
  display: block;
  object-fit: contain;
}
.qr-meta {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1rem;
  font-size: 0.82rem;
}
.qr-badge-live {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  color: #fbbf24;
  font-weight: 600;
}
.pulse-dot {
  width: 8px;
  height: 8px;
  background: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.25);
}
.qr-timer {
  color: var(--text-secondary, #9ca3af);
}
.qr-ref code {
  font-size: 0.75rem;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}
.qr-actions {
  display: flex;
  gap: 0.6rem;
  justify-content: center;
}
.btn-outline-sm {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border-color, #374151);
  color: var(--text-primary, #e5e7eb);
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-outline-sm:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.1);
}
.qr-empty {
  padding: 1.5rem 0.75rem;
}
.qr-placeholder-icon {
  font-size: 2.4rem;
  margin-bottom: 0.5rem;
}
.qr-helper-msg, .qr-expired-msg {
  font-size: 0.86rem;
  color: var(--text-secondary, #9ca3af);
  margin-bottom: 1rem;
}
.qr-expired-msg {
  color: #f87171;
}
.btn-qr-generate, .btn-cash-confirm, .btn-primary-full {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  border: none;
  font-weight: 700;
  font-size: 0.92rem;
  cursor: pointer;
  background: linear-gradient(135deg, #d97706, #b45309);
  color: #fff;
  transition: opacity 0.2s;
}
.btn-cash-confirm {
  background: linear-gradient(135deg, #059669, #047857);
}
.cash-info {
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: 10px;
  padding: 0.9rem 1rem;
  font-size: 0.85rem;
  line-height: 1.5;
  margin-bottom: 1.1rem;
}
.paid-success-box {
  text-align: center;
  padding: 1.25rem 0.5rem 0.5rem;
}
.paid-icon {
  font-size: 2.8rem;
  margin-bottom: 0.5rem;
}
.paid-title {
  margin: 0 0 0.4rem;
  font-size: 1.15rem;
  color: #10b981;
}
.paid-desc {
  font-size: 0.88rem;
  color: var(--text-secondary, #9ca3af);
  margin-bottom: 0.9rem;
}
.receipt-pill {
  display: inline-block;
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.35);
  color: #34d399;
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}
</style>
