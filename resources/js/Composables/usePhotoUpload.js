import { ref } from 'vue';

/**
 * Composable para gestionar la foto de perfil del usuario.
 * Se encarga de:
 *   1. Validar tipo MIME y tamaño del archivo seleccionado.
 *   2. Mostrar una vista previa inmediata (object URL).
 *   3. Redimensionar la imagen a 200x200 JPEG (calidad 0.85) usando un <canvas> oculto.
 *   4. Devolver un Blob listo para enviar vía FormData.
 *
 * Mensajes de error en español.
 */
export function usePhotoUpload() {
    const previewUrl = ref(null);
    const isProcessing = ref(false);
    const error = ref(null);

    const ALLOWED_MIMES = ['image/jpeg', 'image/png'];
    const MAX_BYTES = 2 * 1024 * 1024; // 2 MB
    const TARGET_SIZE = 200;
    const JPEG_QUALITY = 0.85;

    function clear() {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = null;
        }
        error.value = null;
    }

    function setPreviewFromFile(file) {
        clear();
        previewUrl.value = URL.createObjectURL(file);
    }

    function validate(file) {
        if (!ALLOWED_MIMES.includes(file.type)) {
            const msg = 'La imagen debe ser JPG o PNG.';
            error.value = msg;
            throw new Error(msg);
        }
        if (file.size > MAX_BYTES) {
            const msg = 'La imagen no debe pesar más de 2MB.';
            error.value = msg;
            throw new Error(msg);
        }
    }

    /**
     * Loads the file into a hidden <canvas> resized to 200x200 with center-cover crop,
     * then exports as JPEG. Resolves with a Blob.
     */
    function resize(file) {
        isProcessing.value = true;
        error.value = null;

        return new Promise((resolve, reject) => {
            try {
                validate(file);
            } catch (e) {
                isProcessing.value = false;
                reject(e);
                return;
            }

            const img = new Image();
            const objectUrl = URL.createObjectURL(file);
            img.onload = () => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = TARGET_SIZE;
                    canvas.height = TARGET_SIZE;
                    const ctx = canvas.getContext('2d');

                    // Center-cover crop
                    const ratio = Math.max(TARGET_SIZE / img.width, TARGET_SIZE / img.height);
                    const w = img.width * ratio;
                    const h = img.height * ratio;
                    const x = (TARGET_SIZE - w) / 2;
                    const y = (TARGET_SIZE - h) / 2;

                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillRect(0, 0, TARGET_SIZE, TARGET_SIZE);
                    ctx.drawImage(img, x, y, w, h);

                    canvas.toBlob((blob) => {
                        URL.revokeObjectURL(objectUrl);
                        isProcessing.value = false;
                        if (!blob) {
                            error.value = 'No se pudo procesar la imagen.';
                            reject(new Error(error.value));
                            return;
                        }
                        resolve(blob);
                    }, 'image/jpeg', JPEG_QUALITY);
                } catch (e) {
                    URL.revokeObjectURL(objectUrl);
                    isProcessing.value = false;
                    error.value = e.message || 'Error al procesar la imagen.';
                    reject(e);
                }
            };
            img.onerror = () => {
                URL.revokeObjectURL(objectUrl);
                isProcessing.value = false;
                error.value = 'No se pudo leer la imagen seleccionada.';
                reject(new Error(error.value));
            };
            img.src = objectUrl;
        });
    }

    return {
        previewUrl,
        isProcessing,
        error,
        resize,
        setPreviewFromFile,
        clear,
    };
}
