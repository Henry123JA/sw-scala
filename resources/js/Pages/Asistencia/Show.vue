<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ sesion: Object, alumnos: Array });
const page = usePage();
const base = (import.meta.env.VITE_BASE_URL || '').replace(/\/$/, '');
const estados = { PRESENTE: 'Presente', FALTA: 'Falta', LICENCIA: 'Licencia', ATRASO: 'Atraso' };
const form = useForm({
  asistencias: props.alumnos.map(alumno => ({
    inscripcion_id: alumno.inscripcion_id,
    estado: alumno.estado || '',
    observacion: alumno.observacion || '',
  })),
});
</script>

<template>
  <Head title="Registrar asistencia" />
  <AppLayout>
    <div class="space-y-6">
      <Link :href="`${base}/asistencias`" class="text-teal-700">← Elegir otra sesión</Link>
      <h1 class="text-2xl font-bold">Asistencia · {{ sesion.grupo.codigo_grupo }}</h1>
      <p class="text-gray-600">{{ sesion.grupo.curso?.nombre }} · {{ sesion.fecha.slice(0, 10) }}</p>
      <p v-if="page.props.flash?.success" role="status" class="rounded bg-green-50 p-3 text-green-800">{{ page.props.flash.success }}</p>
      <p v-if="form.recentlySuccessful" role="status" class="text-green-800">Asistencia guardada correctamente.</p>
      <p v-if="!alumnos.length" class="rounded border bg-white p-6">Este grupo no tiene alumnos inscritos.</p>
      <form v-else class="space-y-4" @submit.prevent="form.put(`${base}/asistencias/${sesion.id}`, { preserveScroll: true })">
        <button type="button" class="rounded border px-4 py-2" :disabled="form.processing" @click="form.asistencias.forEach(fila => fila.estado = 'PRESENTE')">Marcar todos presentes</button>
        <div class="overflow-x-auto rounded-lg border bg-white">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50"><tr><th class="p-4">Estudiante</th><th class="p-4">Estado</th><th class="p-4">Observación</th></tr></thead>
            <tbody>
              <tr v-for="(alumno, index) in alumnos" :key="alumno.inscripcion_id" class="border-t">
                <td class="p-4">{{ alumno.nombre }}<span class="block text-xs text-gray-500">Inscripción {{ alumno.estado_inscripcion }}</span>
                  <p v-if="form.errors[`asistencias.${index}.inscripcion_id`]" class="text-red-600">{{ form.errors[`asistencias.${index}.inscripcion_id`] }}</p>
                </td>
                <td class="p-4">
                  <select v-model="form.asistencias[index].estado" :aria-label="`Estado de ${alumno.nombre}`" required :disabled="form.processing" class="rounded border p-2">
                    <option disabled value="">Sin registrar</option>
                    <option v-for="(label, value) in estados" :key="value" :value="value">{{ label }}</option>
                  </select>
                  <p v-if="form.errors[`asistencias.${index}.estado`]" class="text-red-600">{{ form.errors[`asistencias.${index}.estado`] }}</p>
                </td>
                <td class="p-4">
                  <textarea v-model="form.asistencias[index].observacion" :aria-label="`Observación de ${alumno.nombre}`" maxlength="5000" rows="2" :disabled="form.processing" class="w-full min-w-48 rounded border p-2" />
                  <p v-if="form.errors[`asistencias.${index}.observacion`]" class="text-red-600">{{ form.errors[`asistencias.${index}.observacion`] }}</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="form.errors.asistencias" role="alert" class="text-red-600">{{ form.errors.asistencias }}</p>
        <button :disabled="form.processing" class="rounded bg-teal-700 px-4 py-2 text-white disabled:opacity-50">{{ form.processing ? 'Guardando…' : 'Guardar asistencia' }}</button>
      </form>
    </div>
  </AppLayout>
</template>
