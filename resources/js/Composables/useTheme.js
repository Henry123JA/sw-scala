import { ref, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const temaActual = ref('adultos');

let intervalId = null;
let visibilityHandler = null;

export function useTheme() {
    /**
     * Detecta el modo (día/noche) basado en la hora local del navegador.
     * 06:00–17:59 → día | 18:00–05:59 → noche
     */
    const detectarModo = () => {
        const hour = new Date().getHours();
        return (hour >= 6 && hour < 18) ? 'dia' : 'noche';
    };

    /**
     * Aplica el tema uniendo categoría + modo detectado.
     * @param {string} categoria — bare category name (e.g. 'ninos', 'jovenes', 'adultos')
     */
    const applyTheme = (categoria) => {
        if (!categoria) return;
        const modo = detectarModo();
        const fullTheme = categoria + '-' + modo;
        document.documentElement.dataset.tema = fullTheme;
        temaActual.value = categoria;
    };

    /**
     * Inicia el intervalo de verificación periódica y el listener de visibilidad.
     * Debe llamarse una sola vez desde AppLayout.
     */
    const startAutoUpdate = (categoria) => {
        stopAutoUpdate();

        // Re-aplica el tema cada 60s para detectar cruce de límite horario
        intervalId = setInterval(() => {
            applyTheme(temaActual.value);
        }, 60000);

        // Re-chequea al volver a la pestaña después de estar oculta
        visibilityHandler = () => {
            if (document.visibilityState === 'visible') {
                applyTheme(temaActual.value);
            }
        };
        document.addEventListener('visibilitychange', visibilityHandler);

        applyTheme(categoria);
    };

    /**
     * Detiene el intervalo y remueve el listener de visibilidad.
     */
    const stopAutoUpdate = () => {
        if (intervalId !== null) {
            clearInterval(intervalId);
            intervalId = null;
        }
        if (visibilityHandler !== null) {
            document.removeEventListener('visibilitychange', visibilityHandler);
            visibilityHandler = null;
        }
    };

    /**
     * Envía una petición POST para persistir la selección del tema del usuario en la BD.
     * @param {number} temaId — ID del tema en la base de datos
     */
    const saveTheme = (temaId) => {
        router.post(buildUrl('/perfil/tema'), {
            tema_id: temaId
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                const newTheme = page.props.temaActual;
                if (newTheme) {
                    applyTheme(newTheme);
                }
            }
        });
    };

    return {
        temaActual,
        detectarModo,
        applyTheme,
        startAutoUpdate,
        stopAutoUpdate,
        saveTheme,
    };
}
